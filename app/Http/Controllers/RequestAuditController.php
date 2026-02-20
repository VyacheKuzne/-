<?php

namespace App\Http\Controllers;

use App\Models\Request as RequestModel; // Алиас для модели
use Illuminate\Http\Request;

class RequestAuditController extends Controller
{
    public function index(RequestModel $request) // Здесь RequestModel - это ваша модель
    {
        $audits = $request->audits()
            ->with('user')
            ->latest()
            ->paginate(20);

        // Для Inertia возвращаем компонент
        return inertia('RequestHistory', [
            'request' => $request,
            'audits' => $audits
        ]);
    }
}