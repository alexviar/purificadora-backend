<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        return response()->json(Notification::where('user_id', $user->id)->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'mensaje' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $notificacion = Notification::create($request->all());

        return response()->json([
            'message'      => 'Notificación creada correctamente',
            'notificacion' => $notificacion
        ], 201);
    }

    public function show(Notification $notification)
    {
        return response()->json($notification);
    }

    public function update(Request $request, Notification $notification)
    {
        $validator = Validator::make($request->all(), [
            'mensaje' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $notification->update($request->all());

        return response()->json([
            'message'      => 'Notificación actualizada correctamente',
            'notificacion' => $notification
        ]);
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();

        return response()->json(['message' => 'Notificación eliminada correctamente']);
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        Notification::where('user_id', $user->id)
            ->whereNull('fecha_leido')
            ->update(['fecha_leido' => Carbon::now()]);

        return response()->json(['message' => 'Todas las notificaciones marcadas como leídas']);
    }
}
