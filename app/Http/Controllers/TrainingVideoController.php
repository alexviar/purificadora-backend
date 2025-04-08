<?php

namespace App\Http\Controllers;

use App\Models\TrainingVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TrainingVideoController extends Controller
{
    public function index()
    {
        return response()->json(TrainingVideo::all());
    }

    public function store(Request $request)
    {
        // Se permite enviar ya sea una URL o un archivo de video
        $validator = Validator::make($request->all(), [
            'titulo'      => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'url'         => 'nullable|url',
            'video'       => 'nullable|mimes:mp4,avi,mov|max:51200'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Si se sube un archivo, se almacena y se utiliza su ruta; de lo contrario, se usa el campo 'url'
        if ($request->hasFile('video')) {
            $rutaVideo = $request->file('video')->store('training_videos', 'public');
        } else {
            $rutaVideo = $request->input('url');
        }

        $video = TrainingVideo::create([
            'titulo'      => $request->titulo,
            'descripcion' => $request->descripcion,
            'url'         => $rutaVideo,
        ]);

        return response()->json([
            'message' => 'Video de capacitación creado correctamente',
            'video'   => $video
        ], 201);
    }

    public function show(TrainingVideo $trainingVideo)
    {
        return response()->json($trainingVideo);
    }

    public function update(Request $request, TrainingVideo $trainingVideo)
    {
        $validator = Validator::make($request->all(), [
            'titulo'      => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'url'         => 'nullable|url',
            'video'       => 'nullable|mimes:mp4,avi,mov|max:51200'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->only(['titulo', 'descripcion', 'url']);

        if ($request->hasFile('video')) {
            $rutaVideo = $request->file('video')->store('training_videos', 'public');
            $data['url'] = $rutaVideo;
        } elseif ($request->filled('url')) {
            $data['url'] = $request->input('url');
        }

        $trainingVideo->update($data);

        return response()->json([
            'message' => 'Video actualizado correctamente',
            'video'   => $trainingVideo
        ]);
    }

    public function destroy(TrainingVideo $trainingVideo)
    {
        $trainingVideo->delete();
        return response()->json(['message' => 'Video eliminado correctamente']);
    }
}
