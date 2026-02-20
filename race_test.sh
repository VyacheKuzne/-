#!/bin/bash

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Конфигурация
BASE_URL="http://127.0.0.1:8000"
COOKIE_FILE="cookies.txt"
RESULTS_FILE="race_results.json"
LOG_FILE="race_test_$(date +%Y%m%d_%H%M%S).log"

# Параметры по умолчанию
REQUEST_ID=${1:-$(php artisan tinker --execute="echo App\\Models\\RequestForMaster::where('status', 'assigned')->inRandomOrder()->first()?->id ?? 1;")}
CONCURRENCY=${2:-5}

# Функция для выполнения команд в Laravel
laravel_command() {
    php artisan tinker --execute="$1" 2>/dev/null | grep -v "^>>>" | grep -v "^..." | grep -v "^[[:space:]]*$" | tail -n 1
}

echo -e "${BLUE}==============================================${NC}"
echo -e "${BLUE}        RACE CONDITION TEST SCRIPT            ${NC}"
echo -e "${BLUE}==============================================${NC}"
echo ""

# Получаем реальных мастеров из БД
echo -e "${YELLOW}Получение списка мастеров из базы данных...${NC}"

# Получаем ID мастеров (пользователей с ролью master или всех пользователей)
MASTER_IDS_JSON=$(laravel_command "echo json_encode(App\\Models\\User::where('role', 'master')->pluck('id')->toArray() ?: App\\Models\\User::pluck('id')->toArray());")
MASTER_IDS=($(echo $MASTER_IDS_JSON | jq -r '.[]' 2>/dev/null))

if [ ${#MASTER_IDS[@]} -eq 0 ]; then
    echo -e "${RED}Не удалось получить список мастеров. Использую тестовые ID 4-9${NC}"
    MASTER_IDS=(4 5 6 7 8 9)
fi

# Получаем случайную заявку со статусом 'assigned'
if [ "$1" = "random" ] || [ -z "$1" ]; then
    REQUEST_ID=$(laravel_command "echo App\\Models\\RequestForMaster::where('status', 'assigned')->inRandomOrder()->first()?->id ?? App\\Models\\RequestForMaster::first()?->id;")
fi

# Получаем информацию о заявке
REQUEST_INFO=$(laravel_command "
\$request = App\\Models\\RequestForMaster::find($REQUEST_ID);
if (\$request) {
    echo 'ID: ' . \$request->id . ' | Статус: ' . (\$request->status ?? 'null') . ' | Мастер: ' . (\$request->master_id ?? 'не назначен');
} else {
    echo 'Заявка не найдена';
}
")

echo -e "${GREEN}Тестируемая заявка:${NC} $REQUEST_INFO"
echo -e "${GREEN}Доступные мастера:${NC} ${MASTER_IDS[*]}"
echo -e "${GREEN}Конкурентность:${NC} $CONCURRENCY"
echo -e "${GREEN}Лог файл:${NC} $LOG_FILE"
echo ""

# Функция для логирования
log() {
    echo -e "$1" | tee -a "$LOG_FILE"
}

# Функция для логина через браузер
login_instructions() {
    log "${YELLOW}========================================${NC}"
    log "${YELLOW}    ИНСТРУКЦИЯ ПО ПОДГОТОВКЕ    ${NC}"
    log "${YELLOW}========================================${NC}"
    log "1. Откройте браузер и залогиньтесь как любой мастер"
    log "2. Откройте инструменты разработчика (F12)"
    log "3. Выполните в консоли браузера:"
    log "${GREEN}console.log(document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content'))${NC}"
    log "${YELLOW}========================================${NC}"
}

# Функция для взятия заявки
take_request() {
    local master_id=$1
    local csrf_token=$2
    local request_id=$3
    local delay=${4:-0}
    
    if [ "$delay" -gt 0 ]; then
        sleep "$delay"
    fi
    
    local start_time=$(date +%s%N)
    log "${YELLOW}➤ Мастер $master_id пытается взять заявку $request_id${NC}"
    
    # Отправляем PUT запрос
    local response=$(curl -s -w "\n%{http_code}" \
        -X PUT \
        -H "X-CSRF-TOKEN: $csrf_token" \
        -H "Accept: application/json" \
        -H "X-Requested-With: XMLHttpRequest" \
        -H "Content-Type: application/json" \
        -H "Referer: $BASE_URL/requestsForMaster" \
        -b "$COOKIE_FILE" \
        -c "$COOKIE_FILE" \
        "$BASE_URL/requestsForMaster/$request_id/take" 2>&1)
    
    local end_time=$(date +%s%N)
    local duration=$(( ($end_time - $start_time) / 1000000 ))
    
    local http_code=$(echo "$response" | tail -n1)
    local body=$(echo "$response" | sed '$d')
    
    # Определяем результат
    if [ "$http_code" -eq 200 ] || [ "$http_code" -eq 302 ] || [ "$http_code" -eq 204 ]; then
        if echo "$body" | grep -qi "success\|взята\|успех"; then
            log "${GREEN}  ✓ Мастер $master_id: заявка взята (HTTP $http_code, ${duration}ms)${NC}"
            echo "success:$master_id"
        else
            log "${RED}  ✗ Мастер $master_id: отказано (HTTP $http_code, ${duration}ms)${NC}"
            echo "failed:$master_id"
        fi
    else
        log "${RED}  ✗ Мастер $master_id: ошибка HTTP $http_code (${duration}ms)${NC}"
        echo "error:$master_id"
    fi
    
    # Логируем ответ сервера
    echo "$body" | grep -o '"message":"[^"]*"\|"error":"[^"]*"' | while read -r msg; do
        log "    $msg"
    done >> "$LOG_FILE"
}

# Функция для сброса статуса заявки
reset_status() {
    local request_id=$1
    log "${RED}========================================${NC}"
    log "${RED}СБРОС СТАТУСА ЗАЯВКИ${NC}"
    log "${YELLOW}Выполните SQL запрос:${NC}"
    log "${PURPLE}UPDATE requests_for_master SET status = 'assigned', master_id = NULL, started_at = NULL WHERE id = $request_id;${NC}"
    log "${RED}========================================${NC}"
    log "${YELLOW}Нажмите Enter когда сбросите статус...${NC}"
    read -r
    
    # Проверяем, что статус сброшен
    local current_status=$(laravel_command "echo App\\Models\\RequestForMaster::find($request_id)?->status;")
    log "${GREEN}Текущий статус заявки: $current_status${NC}"
}

# Функция для проверки финального статуса
check_final_status() {
    local request_id=$1
    
    log "${CYAN}Проверка финального статуса заявки $request_id:${NC}"
    
    # Получаем полную информацию о заявке
    local request_data=$(laravel_command "
    \$r = App\\Models\\RequestForMaster::find($request_id);
    if (\$r) {
        echo 'Статус: ' . \$r->status . ' | Мастер: ' . (\$r->master_id ?? 'не назначен') . ' | Время начала: ' . (\$r->started_at ?? 'не задано');
    } else {
        echo 'Заявка не найдена';
    }
    ")
    
    log "${GREEN}$request_data${NC}"
}

# Основная функция
main() {
    # Показываем инструкцию
    login_instructions
    
    # Запрашиваем CSRF токен
    log ""
    log "${YELLOW}Введите CSRF токен из браузера:${NC}"
    read -r CSRF_TOKEN
    
    if [ -z "$CSRF_TOKEN" ]; then
        log "${RED}Токен не введен. Выход.${NC}"
        exit 1
    fi
    
    log "${GREEN}Токен получен: ${CSRF_TOKEN:0:20}...${NC}"
    log ""
    
    # Получаем куки
    curl -s -c "$COOKIE_FILE" "$BASE_URL" > /dev/null
    
    # Тест 1: Один мастер дважды
    log "\n${YELLOW}═══════════════════════════════════════════${NC}"
    log "${YELLOW}ТЕСТ 1: Один мастер пытается взять заявку дважды${NC}"
    log "${YELLOW}═══════════════════════════════════════════${NC}"
    reset_status "$REQUEST_ID"
    
    # Первая попытка
    local result1=$(take_request "${MASTER_IDS[0]}" "$CSRF_TOKEN" "$REQUEST_ID" 0)
    
    # Вторая попытка (должна быть ошибка)
    local result2=$(take_request "${MASTER_IDS[0]}" "$CSRF_TOKEN" "$REQUEST_ID" 1)
    
    # Проверяем результаты
    if [[ "$result1" == success* ]] && [[ "$result2" == failed* ]]; then
        log "${GREEN}✓ Тест 1 пройден: повторное взятие отклонено${NC}"
    else
        log "${RED}✗ Тест 1 не пройден: ожидалось success + failed, получено: $result1, $result2${NC}"
    fi
    
    # Тест 2: Race condition
    log "\n${YELLOW}═══════════════════════════════════════════${NC}"
    log "${YELLOW}ТЕСТ 2: Race condition (параллельные запросы)${NC}"
    log "${YELLOW}═══════════════════════════════════════════${NC}"
    reset_status "$REQUEST_ID"
    
    log "${YELLOW}Запускаем $CONCURRENCY параллельных запросов...${NC}"
    
    # Массивы для результатов
    declare -a results=()
    declare -a pids=()
    
    # Запускаем параллельные запросы
    for ((i=0; i<$CONCURRENCY; i++)); do
        local master_id=${MASTER_IDS[$((i % ${#MASTER_IDS[@]}))]}
        
        # Запускаем в фоне и сохраняем результат во временный файл
        (
            local res=$(take_request "$master_id" "$CSRF_TOKEN" "$REQUEST_ID" 0)
            echo "$res" > "/tmp/race_result_$i.txt"
        ) &
        pids[$i]=$!
        
        # Небольшая случайная задержка
        sleep 0.$(printf "%03d" $((RANDOM % 50)))
    done
    
    # Ждем завершения всех процессов
    for pid in ${pids[*]}; do
        wait $pid
    done
    
    # Собираем результаты
    local success_count=0
    local failed_count=0
    local error_count=0
    local success_masters=()
    
    for ((i=0; i<$CONCURRENCY; i++)); do
        if [ -f "/tmp/race_result_$i.txt" ]; then
            local res=$(cat "/tmp/race_result_$i.txt")
            rm -f "/tmp/race_result_$i.txt"
            
            case $res in
                success*)
                    ((success_count++))
                    success_masters+=(${res#success:})
                    ;;
                failed*)
                    ((failed_count++))
                    ;;
                error*)
                    ((error_count++))
                    ;;
            esac
        fi
    done
    
    # Анализ результатов
    log "\n${BLUE}═══════════════════════════════════════════${NC}"
    log "${BLUE}РЕЗУЛЬТАТЫ ТЕСТА${NC}"
    log "${BLUE}═══════════════════════════════════════════${NC}"
    log "Всего запросов: $CONCURRENCY"
    log "${GREEN}Успешных взятий: $success_count${NC}"
    log "${RED}Отказов: $failed_count${NC}"
    log "${RED}Ошибок: $error_count${NC}"
    
    # Проверка на race condition
    log "\n${YELLOW}ПРОВЕРКА НА RACE CONDITION:${NC}"
    
    if [ $success_count -eq 0 ]; then
        log "${RED}⚠️ Заявка не была взята никем!${NC}"
    elif [ $success_count -eq 1 ]; then
        log "${GREEN}✅ ЗАЩИТА РАБОТАЕТ: заявка взята ровно 1 раз мастером ${success_masters[0]}${NC}"
    else
        log "${RED}⚠️ RACE CONDITION ОБНАРУЖЕНА!${NC}"
        log "${RED}   Заявка взята $success_count раз(а) мастерами: ${success_masters[*]}${NC}"
    fi
    
    # Проверяем финальный статус
    log "\n${CYAN}ФИНАЛЬНЫЙ СТАТУС ЗАЯВКИ:${NC}"
    check_final_status "$REQUEST_ID"
    
    # Очистка
    rm -f "$COOKIE_FILE" /tmp/race_result_*.txt
    
    log "\n${BLUE}═══════════════════════════════════════════${NC}"
    log "${BLUE}Тестирование завершено! Лог сохранен в $LOG_FILE${NC}"
    log "${BLUE}═══════════════════════════════════════════${NC}"
}

# Запуск
main