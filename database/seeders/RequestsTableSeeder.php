<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Request as RequestModel;
use Carbon\Carbon;

class RequestsTableSeeder extends Seeder
{
    public function run(): void
    {
        // Получаем всех мастеров (предполагаем, что мастера - это пользователи с ролью 'master')
        // Если у вас нет ролей, просто берем существующих пользователей
        $masters = User::where('role', 'master')->get();
        
        // Если нет мастеров, создадим несколько тестовых
        if ($masters->isEmpty()) {
            $masters = collect();
            for ($i = 1; $i <= 3; $i++) {
                $master = User::create([
                    'name' => "Мастер $i",
                    'email' => "master$i@example.com",
                    'password' => Hash::make('password'),
                    'role' => 'master',
                ]);
                $masters->push($master);
            }
        }

        // ID существующих мастеров (если они уже есть в БД)
        $masterIds = [4, 5, 6, 7];
        
        // Статусы для заявок
        $statuses = ['new', 'canceled', 'assigned'];
        
        // Массив клиентских имен для разнообразия
        $clientNames = [
            'Иван Петров', 'Петр Сидоров', 'Сергей Иванов',
            'Анна Смирнова', 'Елена Козлова', 'Дмитрий Морозов',
            'Ольга Новикова', 'Михаил Волков', 'Татьяна Соколова',
            'Алексей Лебедев'
        ];
        
        // Массив адресов
        $addresses = [
            'ул. Ленина 10, кв. 5', 'пр. Мира 25, кв. 12',
            'ул. Гагарина 7, кв. 3', 'пр. Победы 15, кв. 42',
            'ул. Советская 8, кв. 18', 'ул. Лесная 3, кв. 7',
            'пр. Октябрьский 22, кв. 31', 'ул. Садовая 14, кв. 9',
            'ул. Школьная 5, кв. 16', 'пр. Строителей 12, кв. 24'
        ];
        
        // Массив проблем
        $problems = [
            'Не работает розетка', 'Течет кран на кухне',
            'Сломался замок входной двери', 'Нет света в комнате',
            'Батарея не греет', 'Засорилась раковина',
            'Не закрывается окно', 'Сломался унитаз',
            'Трещина в стене', 'Не работает выключатель',
            'Плохой напор воды', 'Скрипит пол',
            'Отклеились обои', 'Не греет полотенцесушитель',
            'Сломался смеситель в ванной'
        ];

        // Телефоны
        $phones = [
            '+79001234567', '+79007654321', '+79009876543',
            '+79002345678', '+79003456789', '+79004567890',
            '+79005678901', '+79006789012', '+79007890123',
            '+79008901234'
        ];

        // Создаем 10 заявок
        for ($i = 0; $i < 10; $i++) {
            // Случайный статус
            $status = $statuses[array_rand($statuses)];
            
            // Определяем assignedTo в зависимости от статуса
            if ($status === 'assigned') {
                // Для статуса assigned выбираем случайного мастера из списка
                $assignedTo = $masterIds[array_rand($masterIds)];
            } else {
                // Для статусов new и canceled мастер не назначен
                $assignedTo = null;
            }
            
            // Случайные даты создания (последние 30 дней)
            $createdAt = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
            
            // Дата обновления (может быть позже создания)
            $updatedAt = $createdAt->copy()->addHours(rand(0, 48))->addMinutes(rand(0, 59));

            RequestModel::create([
                'clientName' => $clientNames[$i],
                'phone' => $phones[$i],
                'address' => $addresses[$i],
                'problemText' => $problems[array_rand($problems)],
                'status' => $status,
                'assignedTo' => $assignedTo,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
        }

        $this->command->info('10 заявок успешно созданы!');
    }
}