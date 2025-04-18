<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TrainingVideo;
use App\Models\UserTrainingVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserTrainingVideoController extends Controller
{
    /**
     * Obtener los videos asignados a un usuario específico
     */
    public function getUserVideos(User $user)
    {
        // Obtener las asignaciones de videos con la relación completa a trainingVideo
        $userVideos = UserTrainingVideo::where('user_id', $user->id)
            ->with('trainingVideo') // Asegurar que se carga la relación completa
            ->get();

        // Transformar la respuesta para incluir todos los detalles del video
        $formattedUserVideos = $userVideos->map(function ($userVideo) {
            return [
                'id' => $userVideo->id,
                'user_id' => $userVideo->user_id,
                'training_video_id' => $userVideo->training_video_id,
                'created_at' => $userVideo->created_at,
                'trainingVideo' => $userVideo->trainingVideo ? [
                    'id' => $userVideo->trainingVideo->id,
                    'titulo' => $userVideo->trainingVideo->titulo,
                    'descripcion' => $userVideo->trainingVideo->descripcion,
                    'url' => $userVideo->trainingVideo->url
                ] : null
            ];
        });

        return response()->json($formattedUserVideos);
    }

    /**
     * Asignar un video a un usuario
     */
    public function assignVideo(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'training_video_id' => 'required|exists:training_videos,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Verificar que no exista ya la asignación
        $existingAssignment = UserTrainingVideo::where('user_id', $user->id)
            ->where('training_video_id', $request->training_video_id)
            ->first();

        if ($existingAssignment) {
            return response()->json([
                'message' => 'El video ya está asignado a este usuario'
            ], 422);
        }

        // Crear la asignación
        $userVideo = UserTrainingVideo::create([
            'user_id' => $user->id,
            'training_video_id' => $request->training_video_id,
        ]);

        // Cargar la relación completa del video
        $userVideo->load('trainingVideo');
        
        // Formato consistente con getUserVideos
        $formattedUserVideo = [
            'id' => $userVideo->id,
            'user_id' => $userVideo->user_id,
            'training_video_id' => $userVideo->training_video_id,
            'created_at' => $userVideo->created_at,
            'trainingVideo' => $userVideo->trainingVideo ? [
                'id' => $userVideo->trainingVideo->id,
                'titulo' => $userVideo->trainingVideo->titulo,
                'descripcion' => $userVideo->trainingVideo->descripcion,
                'url' => $userVideo->trainingVideo->url
            ] : null
        ];

        return response()->json([
            'message' => 'Video asignado correctamente',
            'user_video' => $formattedUserVideo
        ]);
    }

    /**
     * Eliminar una asignación de video a usuario
     */
    public function removeUserVideo(UserTrainingVideo $userVideo)
    {
        $userVideo->delete();
        
        return response()->json([
            'message' => 'Asignación de video eliminada correctamente'
        ]);
    }
}
