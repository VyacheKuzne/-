<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Request as RequestModel;
use App\Models\User;
use App\Enums\RequestStatus;
use Illuminate\Support\Facades\DB;
use App\Models\RequestAudit; 
class RequestController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'clientName' => ['required', 'string', 'max:18'],
            'phone' => ['required', 'string', 'max:12', 'min:12'],
            'address' => ['required', 'string', 'max:40'],
            'problemText' => ['required', 'string', 'max:255'],
        ]);

        $newRequest = RequestModel::create([
            ...$validated,
            'status' => RequestStatus::NEW,
        ]);

        return response()->json([
            'message' => 'Заявка успешно создана',
            'data' => $newRequest
        ]);
    }

public function index(Request $request)
{
    $query = RequestModel::query();

    if ($request->status && $request->status !== 'all') {
        $query->where('status', $request->status);
    }

    return Inertia::render('RequestsPage', [
        'requests' => $query->latest()->get(),
        'masters' => User::where('role', 1)->get(),
        'filters' => [
            'status' => $request->status ?? 'all'
        ]
    ]);
}

public function cancel(RequestModel $request)
{
    $userId = Auth::id();
    
    // Проверяем, что статус существует
    $oldStatus = $request->status?->value ?? $request->status;
    
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
    
    return back()->with('success', 'Заявка отменена');
}

public function assign(Request $httpRequest, RequestModel $request)
{
    $httpRequest->validate([
        'master_id' => ['required', 'exists:users,id']
    ]);

    return DB::transaction(function () use ($request, $httpRequest) {
        $userId = Auth::id();
        $oldStatus = $request->status->value;
        $oldAssignedTo = $request->assignedTo;
        
        $request->update([
            'assignedTo' => $httpRequest->master_id,
            'status' => RequestStatus::ASSIGNED
        ]);
        
        // Запись в аудит
        RequestAudit::create([
            'request_id' => $request->id,
            'user_id' => $userId,
            'action' => 'assign',
            'old_status' => $oldStatus,
            'new_status' => RequestStatus::ASSIGNED->value,
            'data' => json_encode([
                'old_master' => $oldAssignedTo,
                'new_master' => $httpRequest->master_id
            ])
        ]);
        
        return back()->with('success', 'Master assigned successfully');
    });

}
}