<?php

namespace App\Http\Controllers;
use Inertia\Inertia;

use Illuminate\Http\Request;
use App\Models\Request as RequestModel;
use Illuminate\Support\Facades\Auth;
use App\Enums\RequestStatus;
use Illuminate\Support\Facades\DB;
use App\Models\RequestAudit;


class MasterRequestController extends Controller
{
 

 public function index(Request $request)
    {
        $query = RequestModel::query();
        
        if (Auth::check()) {
            $userId = Auth::id();
            $user = Auth::user();
            
            // Если пользователь - мастер (role = 1)
            if ($user->role == 1) {
                $query->where('assignedTo', $userId);
            }
            // Если пользователь - администратор (role = 2) - показывает все заявки
            else if ($user->role == 2) {
                // Админ видит всё, никаких дополнительных фильтров
            }
        } else {
            return Inertia::render('MasterPage', [
                'requests' => [],
                'filters' => [
                    'status' => $request->status ?? 'all'
                ]
            ]);
        }

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Сортируем прямо в БД
        $requests = $query->orderByRaw("
            CASE 
                WHEN status = 'in_progress' THEN 1
                WHEN status = 'assigned' THEN 2
                WHEN status = 'done' THEN 3
                ELSE 4
            END
        ")->latest()->get();

        return Inertia::render('MasterPage', [
            'requests' => $requests,
            'filters' => [
                'status' => $request->status ?? 'all'
            ]
        ]);
    }

public function take($id)
{
    return DB::transaction(function () use ($id) {
        $userId = Auth::id();
        
        $request = RequestModel::where('id', $id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($request->status !== RequestStatus::ASSIGNED) {
            return back()->with('error', 'Заявка уже взята или завершена');
        }

        if ($request->assignedTo !== $userId) {
            abort(403, 'Вы не назначены на эту заявку');
        }

        $oldStatus = $request->status->value;
        
        $request->update([
            'status' => RequestStatus::IN_PROGRESS
        ]);

        // Запись в аудит
        RequestAudit::create([
            'request_id' => $request->id,
            'user_id' => $userId,
            'action' => 'take',
            'old_status' => $oldStatus,
            'new_status' => RequestStatus::IN_PROGRESS->value
        ]);

        return back()->with('success', 'Задача взята в работу');
    });
}

public function complete($id)
{
    $userId = Auth::id();
    $request = RequestModel::findOrFail($id);
    
    $oldStatus = $request->status->value;
    
    $request->update(['status' => RequestStatus::DONE]);
    
    // Запись в аудит
    RequestAudit::create([
        'request_id' => $request->id,
        'user_id' => $userId,
        'action' => 'complete',
        'old_status' => $oldStatus,
        'new_status' => RequestStatus::DONE->value
    ]);
    
    return back()->with('success', 'Задача завершена');
}

public function cancel(Request $request)
{
    $userId = Auth::id();
    $oldStatus = $request->status->value;
    
    $request->update([
        'status' => RequestStatus::CANCELED
    ]);
    
    // Запись в аудит
    RequestAudit::create([
        'request_id' => $request->id,
        'user_id' => $userId,
        'action' => 'cancel',
        'old_status' => $oldStatus,
        'new_status' => RequestStatus::CANCELED->value
    ]);
    
    return back();
}
/**
     * API версия для тестов (возвращает JSON)
     */
  public function takeApi($id)
{
    return DB::transaction(function () use ($id) {
        $request = RequestModel::where('id', $id)
            ->lockForUpdate()
            ->first();
        
        if (!$request) {
            return response()->json(['error' => 'Request not found'], 404);
        }

        // Проверяем принадлежность мастеру
        if ($request->assignedTo !== Auth::id()) {
            return response()->json(['error' => 'Request not assigned to you'], 403);
        }
        
        // Проверяем статус (используем value для Enum)
        if ($request->status->value !== 'assigned') {
            return response()->json([
                'error' => 'Request cannot be taken',
                'current_status' => $request->status->value
            ], 409);
        }
        
        // Обновляем ТОЛЬКО статус, без started_at
        $updated = RequestModel::where('id', $id)
            ->where('status', 'assigned')
            ->update([
                'status' => 'in_progress'
            ]);
        
        if ($updated === 0) {
            return response()->json([
                'error' => 'Request already taken'
            ], 409);
        }
        
        return response()->json([
            'message' => 'Request taken successfully',
            'status' => 'in_progress'
        ]);
    });
}

}
