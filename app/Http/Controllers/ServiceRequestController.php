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
            'tipo_servicio'    => 'required|in:cambio_sedimentos,suministro_sal,cambio_carbon,mantenimiento_preventivo,otros',
            'fecha_programada' => 'required|date',
            'comentarios'      => 'nullable|string',
            'comentarios_tecnico' => 'nullable|string',
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
                return response()->json(['message' => 'No puedes solicitar servicio para esta planta', 'data' => ['plant' => $plant, 'user' => $user]], 403);
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
            'comentarios_tecnico' => 'nullable|string',
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

        $data = $request->only(['tecnico_id', 'comentarios', 'status_id', 'comentarios_tecnico']);

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

    /**
     * Obtener las solicitudes de servicio del cliente autenticado actualmente
     */
    public function myRequests(Request $request)
    {
        $user = Auth::user();

        // Obtener solicitudes de servicio relacionadas con plantas asignadas al cliente
        $requests = ServiceRequest::with(['planta', 'tecnico', 'status'])
            ->whereHas('planta', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    /**
     * Obtener estadísticas de mantenimientos realizados por estado y tipo
     */
    public function maintenanceStats(Request $request)
    {
        $year = $request->input('year', date('Y')); // Por defecto el año actual

        try {
            \DB::connection()->getPdo();

            // Total de mantenimientos por estado
            $statsByStatus = ServiceRequest::whereYear('created_at', $year)
                ->select('status_id', \DB::raw('count(*) as total'))
                ->groupBy('status_id')
                ->get();

            // Convertir a array asociativo con nombres de estado
            $statusMap = [
                1 => 'pendiente',
                2 => 'en_proceso',
                3 => 'completado'
            ];

            $statusData = [
                'pendiente' => 0,
                'en_proceso' => 0,
                'completado' => 0
            ];

            foreach ($statsByStatus as $item) {
                $statusName = isset($statusMap[$item->status_id]) ? $statusMap[$item->status_id] : 'pendiente';
                $statusData[$statusName] = $item->total;
            }

            // Total de mantenimientos por tipo
            $typeData = [];
            $statsByType = ServiceRequest::whereYear('created_at', $year)
                ->whereNotNull('tipo_servicio')
                ->select('tipo_servicio', \DB::raw('count(*) as total'))
                ->groupBy('tipo_servicio')
                ->get();

            foreach ($statsByType as $item) {
                $typeData[$item->tipo_servicio] = $item->total;
            }

            // Asegurar que siempre tenemos todos los tipos de servicio en el resultado
            $tiposServicio = ['cambio_sedimentos', 'suministro_sal', 'cambio_carbon', 'mantenimiento_preventivo'];
            foreach ($tiposServicio as $tipo) {
                if (!isset($typeData[$tipo])) {
                    $typeData[$tipo] = 0;
                }
            }

            // Mantenimientos completados por mes
            $completedByMonth = ServiceRequest::whereYear('created_at', $year)
                ->where('status_id', 3) // Completados
                ->select(
                    \DB::raw('MONTH(created_at) as month'),
                    \DB::raw('count(*) as total')
                )
                ->groupBy(\DB::raw('MONTH(created_at)'))
                ->get();

            // Inicializar array con todos los meses
            $monthlyStats = array_fill(1, 12, 0);

            // Rellenar con datos reales
            foreach ($completedByMonth as $stat) {
                $monthlyStats[$stat->month] = $stat->total;
            }

            return response()->json([
                'year' => $year,
                'by_status' => $statusData,
                'by_type' => $typeData,
                'monthly_completed' => array_values($monthlyStats),
                'total_completed' => $statusData['completado'],
                'total_all' => array_sum(array_values($statusData))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener estadísticas: ' . $e->getMessage(),
                'year' => $year,
                'by_status' => [
                    'pendiente' => 0,
                    'en_proceso' => 0,
                    'completado' => 0
                ],
                'by_type' => [
                    'cambio_sedimentos' => 0,
                    'suministro_sal' => 0,
                    'cambio_carbon' => 0,
                    'mantenimiento_preventivo' => 0
                ],
                'monthly_completed' => array_fill(0, 12, 0),
                'total_completed' => 0,
                'total_all' => 0
            ], 500);
        }
    }
}
