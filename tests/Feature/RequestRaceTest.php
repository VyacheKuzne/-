<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RequestRaceTest extends TestCase
{
    use RefreshDatabase;



    public function test_cannot_take_request_with_invalid_status(): void
    {
        $master = User::factory()->create(['role' => 1]);
        $this->actingAs($master);

        $request = Request::factory()->create([
            'status' => 'done',
            'assignedTo' => $master->id,
            'clientName' => 'Тест',
            'phone' => '1234567890',
            'address' => 'Адрес',
            'problemText' => 'Проблема'
        ]);

        $response = $this->putJson("/api/requests/{$request->id}/take");
        
        $response->assertStatus(409);
        $response->assertJson([
            'error' => 'Request cannot be taken',
            'current_status' => 'done'
        ]);
        
        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'status' => 'done'
        ]);
    }

    public function test_master_cannot_take_others_request(): void
    {
        $master1 = User::factory()->create(['role' => 1]);
        $master2 = User::factory()->create(['role' => 1]);
        
        $this->actingAs($master2);

        $request = Request::factory()->create([
            'status' => 'assigned',
            'assignedTo' => $master1->id,
            'clientName' => 'Тест',
            'phone' => '1234567890',
            'address' => 'Адрес',
            'problemText' => 'Проблема'
        ]);

        $response = $this->putJson("/api/requests/{$request->id}/take");
        
        $response->assertStatus(403);
        $response->assertJson(['error' => 'Request not assigned to you']);
        
        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'status' => 'assigned',
            'assignedTo' => $master1->id
        ]);
    }

public function test_master_can_take_own_request(): void
{
    $master = User::factory()->create(['role' => 1]);
    $this->actingAs($master);

    $request = Request::factory()->create([
        'status' => 'assigned',  // Здесь строка
        'assignedTo' => $master->id,
        'clientName' => 'Тест',
        'phone' => '1234567890',
        'address' => 'Адрес',
        'problemText' => 'Проблема'
    ]);

    $this->assertDatabaseHas('requests', [
        'id' => $request->id,
        'status' => 'assigned'  // Сравниваем со строкой
    ]);

    $response = $this->putJson("/api/requests/{$request->id}/take");
    
    if ($response->status() === 409) {
        dump('Response status: ' . $response->status());
        dump('Response content: ' . $response->content());
        
        $currentRequest = Request::find($request->id);
        // Преобразуем Enum в строку для вывода
        dump('Current status in DB: ' . $currentRequest->status->value); // Используем ->value
    }
    
    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'Request taken successfully',
        'status' => 'in_progress'
    ]);
    
    $this->assertDatabaseHas('requests', [
        'id' => $request->id,
        'status' => 'in_progress'
    ]);
}

public function test_race_condition_when_taking_request(): void
{
    $master = User::factory()->create(['role' => 1]);
    $this->actingAs($master);

    $request = Request::factory()->create([
        'clientName' => 'Иван Петров',
        'phone' => '+79001234567',
        'address' => 'ул. Ленина 10',
        'problemText' => 'Не работает розетка',
        'status' => 'assigned',
        'assignedTo' => $master->id
    ]);

    $attempts = 5;
    $successCount = 0;

    for ($i = 0; $i < $attempts; $i++) {
        $response = DB::transaction(function () use ($request, $i) {
            usleep(rand(10000, 50000));
            return $this->putJson("/api/requests/{$request->id}/take");
        });

        if ($response->status() === 200) {
            $successCount++;
            $response->assertJson([
                'message' => 'Request taken successfully',
                'status' => 'in_progress'
            ]);
        } else {
            $response->assertStatus(409);
            $json = $response->json();
            
            // Проверяем что это ошибка
            $this->assertArrayHasKey('error', $json);
            
            // Может быть два типа ошибок в зависимости от момента
            if (isset($json['current_status'])) {
                // Если статус указан - это ошибка "cannot take"
                $this->assertContains($json['current_status'], ['assigned', 'in_progress']);
            } else {
                // Если статус не указан - это "already taken"
                $this->assertEquals('Request already taken', $json['error']);
            }
        }
    }

    $this->assertEquals(1, $successCount);
    $this->assertDatabaseHas('requests', [
        'id' => $request->id,
        'status' => 'in_progress'
    ]);
}
}