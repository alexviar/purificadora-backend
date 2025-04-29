<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserTrainingVideo;
use App\Models\TrainingVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserTrainingVideoController extends Controller
{
    /**
     * Obtener los videos asignados al usuario actual
     */
    public function getCurrentUserVideos()
    {
        $user = Auth::user();
        
        // Cargar los videos con la información completa
        $videos = $user->userTrainingVideos()
            ->with('trainingVideo')
            ->get();
        
        return response()->json($videos);
    }

    /**
     * Obtener los videos asignados a un usuario específico
     */
    public function getUserVideos(User $user)
    {
        // Solo admin/superadmin pueden ver los videos de otros usuarios
        if (!Auth::user()->hasRole(['admin', 'superadmin']) && Auth::id() !== $user->id) {
            return response()->json(['message' => 'No autorizado para ver los videos de este usuario'], 403);
        }
        
        // Cargar los videos con la información completa
        $videos = $user->userTrainingVideos()
            ->with('trainingVideo') // Esta es la clave: cargar la relación con el video completo
            ->get();
        
        return response()->json($videos);
    }

    /**
     * Asignar un video a un usuario
     */
    public function assignVideo(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'training_video_id' => 'required|exists:training_videos,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Verificar si ya está asignado
        $exists = $user->trainingVideos()->where('training_video_id', $request->training_video_id)->exists();
        
        if ($exists) {
            return response()->json(['message' => 'El video ya está asignado a este usuario'], 422);
        }

        // Asignar el video al usuario
        $user->trainingVideos()->attach($request->training_video_id);

        return response()->json(['message' => 'Video asignado correctamente al usuario']);
    }

    /**
     * Eliminar la asignación de un video a un usuario
     */
    public function removeUserVideo(UserTrainingVideo $userVideo)
    {
        $userVideo->delete();
        return response()->json(['message' => 'Asignación de video eliminada correctamente']);
    }
}
