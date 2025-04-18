<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Events\ServiceAssigned;
use Illuminate\Support\Facades\Storage;

class ServiceRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceRequest::with(['planta', 'tecnico', 'cliente']);

        if ($request->has('tecnico_id')) {
            $query->where('tecnico_id', $request->input('tecnico_id'));
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        $user = Auth::user();

        if ($user->hasRole('tecnico')) {
            $query->where('tecnico_id', $user->id);
        } elseif ($user->hasRole('cliente')) {
            $query->whereHas('planta', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'planta_id'        => 'required|exists:plants,id',
            'tipo_servicio'    => 'required|in:cambio_sedimentos,suministro_sal,cambio_carbon,mantenimiento_preventivo',
            'fecha_programada' => 'required|date',
            'comentarios'      => 'nullable|string',
            // No es obligatorio que suban archivos, pero se permite si vienen
            'fotos_antes'      => 'nullable',
            'fotos_despues'    => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = Auth::user();
        if ($user->hasRole('cliente')) {
            $plant = Plant::find($request->planta_id);
            if (!$plant || $plant->user_id !== $user->id) {
                return response()->json(['message' => 'No puedes solicitar servicio para esta planta'], 403);
            }
        }

        $data = $request->only(['planta_id', 'tipo_servicio', 'fecha_programada', 'comentarios']);
        $data['cliente_id'] = $user->id;
        $data['estado'] = 'pendiente';

        // Procesar archivos de fotos antes
        if ($request->hasFile('fotos_antes')) {
            $fotosAntes = [];
            foreach ($request->file('fotos_antes') as $file) {
                $ruta = $file->store('service_requests/fotos_antes', 'public');
                $fotosAntes[] = $ruta;
            }
            $data['fotos_antes'] = json_encode($fotosAntes);
        }
        // Procesar archivos de fotos después
        if ($request->hasFile('fotos_despues')) {
            $fotosDespues = [];
            foreach ($request->file('fotos_despues') as $file) {
                $ruta = $file->store('service_requests/fotos_despues', 'public');
                $fotosDespues[] = $ruta;
            }
            $data['fotos_despues'] = json_encode($fotosDespues);
        }

        $serviceRequest = ServiceRequest::create($data);

        return response()->json([
            'message' => 'Solicitud de servicio creada exitosamente',
            'request' => $serviceRequest
        ], 201);
    }

    public function show(ServiceRequest $serviceRequest)
    {
        $user = Auth::user();

        if ($user->hasRole('tecnico') && $serviceRequest->tecnico_id !== $user->id) {
            return response()->json(['message' => 'No puedes ver solicitudes que no te fueron asignadas'], 403);
        }

        if ($user->hasRole('cliente') && $serviceRequest->planta->user_id !== $user->id) {
            return response()->json(['message' => 'No puedes ver solicitudes de otras plantas'], 403);
        }

        return response()->json($serviceRequest->load(['planta', 'tecnico', 'cliente']));
    }

    public function update(Request $request, ServiceRequest $serviceRequest)
    {
        // Verificar si se recibió status_id y mapearlo adecuadamente
        if ($request->has('status_id')) {
            // Usamos status_id directamente ya que es el campo en la base de datos
            $request->merge(['status_id' => (int)$request->status_id]);
        }
        // Para compatibilidad con código anterior que usa 'estado'
        elseif ($request->has('estado')) {
            $estadoMap = [
                'pendiente' => 1,
                'en_proceso' => 2,
                'completado' => 3,
                // También permitimos valores numéricos
                '1' => 1,
                '2' => 2,
                '3' => 3,
                1 => 1,
                2 => 2,
                3 => 3
            ];
            
            if (isset($estadoMap[$request->estado])) {
                $request->merge(['status_id' => $estadoMap[$request->estado]]);
            }
        }
        
        $validator = Validator::make($request->all(), [
            'tecnico_id'    => 'sometimes|exists:users,id',
            'status_id'     => 'sometimes|integer|in:1,2,3',
            'comentarios'   => 'nullable|string',
            'fotos_antes'   => 'nullable',
            'fotos_despues' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = Auth::user();
        if ($user->hasRole('tecnico') && $serviceRequest->tecnico_id !== $user->id) {
            return response()->json(['message' => 'No puedes actualizar solicitudes que no te fueron asignadas'], 403);
        }
        if ($user->hasRole('cliente') && $serviceRequest->planta->user_id !== $user->id) {
            return response()->json(['message' => 'No puedes actualizar solicitudes de otras plantas'], 403);
        }

        $data = $request->only(['tecnico_id', 'comentarios', 'status_id']);

        // Procesar archivos en actualización para fotos antes
        if ($request->hasFile('fotos_antes')) {
            $fotosAntes = [];
            foreach ($request->file('fotos_antes') as $file) {
                $ruta = $file->store('service_requests/fotos_antes', 'public');
                $fotosAntes[] = $ruta;
            }
            $data['fotos_antes'] = json_encode($fotosAntes);
        }
        // Procesar archivos en actualización para fotos después
        if ($request->hasFile('fotos_despues')) {
            $fotosDespues = [];
            foreach ($request->file('fotos_despues') as $file) {
                $ruta = $file->store('service_requests/fotos_despues', 'public');
                $fotosDespues[] = $ruta;
            }
            $data['fotos_despues'] = json_encode($fotosDespues);
        }

        $serviceRequest->update($data);

        return response()->json([
            'message' => 'Solicitud de servicio actualizada exitosamente',
            'request' => $serviceRequest->load(['planta', 'tecnico', 'cliente'])
        ]);
    }

    public function assignTechnician(Request $request, ServiceRequest $serviceRequest)
    {
        $validator = Validator::make($request->all(), [
            'tecnico_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $tecnico = User::find($request->tecnico_id);
        if (!$tecnico || !$tecnico->hasRole('tecnico')) {
            return response()->json(['message' => 'El usuario asignado no tiene el rol de técnico'], 422);
        }

        $serviceRequest->update(['tecnico_id' => $request->tecnico_id]);
        event(new ServiceAssigned($serviceRequest));

        return response()->json([
            'message' => 'Técnico asignado exitosamente',
            'request' => $serviceRequest
        ]);
    }

    public function destroy(ServiceRequest $serviceRequest)
    {
        $serviceRequest->delete();
        return response()->json(['message' => 'Solicitud de servicio eliminada correctamente']);
    }
}
