<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $query = Alert::query();

        if ($request->has('fecha_envio')) {
            $query->whereDate('fecha_envio', $request->input('fecha_envio'));
        }

        if ($request->has('status_id')) {
            $query->where('status_id', $request->input('status_id'));
        }

        return response()->json($query->with(['user', 'status'])->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titulo'      => 'required|string|max:255',
            'mensaje'     => 'required|string',
            'fecha_envio' => 'required|date',
            'user_id'     => 'nullable|exists:users,id',
            'status_id'   => 'nullable|exists:alert_statuses,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $alert = Alert::create($request->all());

        return response()->json([
            'message' => 'Alerta creada correctamente',
            'alert'   => $alert
        ], 201);
    }

    public function show(Alert $alert)
    {
        return response()->json($alert->load(['user', 'status']));
    }

    public function update(Request $request, Alert $alert)
    {
        $validator = Validator::make($request->all(), [
            'titulo'      => 'sometimes|required|string|max:255',
            'mensaje'     => 'sometimes|required|string',
            'fecha_envio' => 'sometimes|required|date',
            'user_id'     => 'nullable|exists:users,id',
            'status_id'   => 'nullable|exists:alert_statuses,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $alert->update($request->all());

        return response()->json([
            'message' => 'Alerta actualizada correctamente',
            'alert'   => $alert
        ]);
    }

    public function destroy(Alert $alert)
    {
        $alert->delete();

        return response()->json(['message' => 'Alerta eliminada correctamente']);
    }
}
