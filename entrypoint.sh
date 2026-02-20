#!/bin/sh

# Ожидание доступности MySQL
echo "Waiting for MySQL to be ready..."
while ! nc -z mysql 3306; do
  sleep 1
done
echo "MySQL is ready!"

# Генерация ключа если его нет
php artisan key:generate --force

# Запуск миграций и сидов
php artisan migrate --force
php artisan db:seed --force

# Очистка кэша
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Запуск основного процесса
exec "$@"