<?php

namespace App\Http\Controllers;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // Listado de usuarios con filtro por rol
    public function index(Request $request)
    {
        $query = User::with('roles');
    
        if ($request->has('role')) {
            $role = $request->input('role');
            $query->whereHas('roles', function($q) use ($role) {
                $q->where('name', $role);
            });
        }
    
        $users = $query->get();
        return response()->json($users);
    }

    // Crear un nuevo usuario (solo accesible para el superusuario o admin, pero admin solo puede crear técnicos)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role'     => 'required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        // Si el usuario autenticado es admin y NO es superadmin, solo se permiten técnicos.
        if ($currentUser->hasRole('admin') && !$currentUser->hasRole('superadmin')) {
            if ($request->role !== 'tecnico') {
                return response()->json(['error' => 'Los administradores solo pueden crear técnicos'], 403);
            }
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Asigna el rol indicado
        $user->assignRole($request->role);

        return response()->json([
            'message' => 'Usuario creado correctamente',
            'user'    => $user->load('roles')
        ], 201);
    }

    // Mostrar información de un usuario
    public function show(User $user)
    {
        return response()->json($user->load('roles'));
    }

    // Actualizar datos de un usuario
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|required|string|max:255',
            'email'    => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'telefono' => 'nullable|string|max:20',
            'current_password' => 'nullable|string|required_with:password',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Verificar la contraseña actual si se intenta cambiar la contraseña
        if ($request->filled('current_password') && $request->filled('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['error' => 'La contraseña actual es incorrecta'], 422);
            }
            // Actualizar contraseña
            $user->password = Hash::make($request->password);
        }

        // Actualizar campos básicos de usuario, incluyendo el teléfono
        $user->update($request->only(['name', 'email', 'telefono']));
        
        return response()->json([
            'message' => 'Usuario actualizado correctamente',
            'user'    => $user->load('roles')
        ]);
    }

    // Actualizar el perfil del usuario autenticado
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|required|string|max:255',
            'email'    => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'telefono' => 'nullable|string|max:20',
            'current_password' => 'nullable|string|required_with:password',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Verificar la contraseña actual si se intenta cambiar la contraseña
        if ($request->filled('current_password') && $request->filled('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['error' => 'La contraseña actual es incorrecta'], 422);
            }
            // Actualizar contraseña
            $user->password = Hash::make($request->password);
            $user->save();
        }

        // Actualizar campos básicos de usuario, incluyendo el teléfono
        $user->update($request->only(['name', 'email', 'telefono']));
        
        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'user'    => $user->load('roles')
        ]);
    }

    // Eliminar un usuario (no se permite eliminar a un superadmin)
    public function destroy(User $user)
    {
        if ($user->hasRole('superadmin')) {
            return response()->json(['message' => 'No se puede eliminar un usuario superadmin'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'Usuario eliminado correctamente']);
    }

    // Asignar un rol a un usuario
    public function assignRole(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|string|exists:roles,name',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
    
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if ($user->roles->contains('name', 'superadmin') && !$currentUser->roles->contains('name', 'superadmin')) {
            return response()->json(['message' => 'No tienes permiso para modificar este usuario'], 403);
        }
    
        $user->syncRoles([$request->role]);
    
        return response()->json([
            'message' => 'Rol asignado correctamente',
            'user'    => $user->load('roles')
        ]);
    }
}
