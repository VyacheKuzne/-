<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Database\Eloquent\Model;
use App\Enums\RequestStatus;
use Illuminate\Support\Facades\Log;

class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'clientName',
        'phone',
        'address',
        'problemText',
        'status',
        'assignedTo',
    ];

    protected $casts = [
        'status' => RequestStatus::class,
    ];

    /**
     * Связь: заявка принадлежит мастеру
     */
    public function master()
    {
        return $this->belongsTo(User::class, 'assignedTo');
    }

    /**
     * Связь с аудитом
     */
    public function audits()
    {
        return $this->hasMany(RequestAudit::class, 'request_id');
    }

    /**
     * Бутстрап модели
     */
    protected static function booted()
    {
        static::created(function ($request) {
            // Метод recordCreated() нужно будет добавить
        });

        static::updated(function ($request) {
            if ($request->isDirty('status')) {
                // Исправление: преобразуем Enum в строку через ->value или ->name
                $oldStatus = $request->getOriginal('status');
                $newStatus = $request->status;
                
                Log::info('Status changed', [
                    'request_id' => $request->id,
                    'from' => $oldStatus instanceof RequestStatus ? $oldStatus->value : $oldStatus,
                    'to' => $newStatus instanceof RequestStatus ? $newStatus->value : $newStatus
                ]);
            }
        });
    }
}