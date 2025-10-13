<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Usuario;
//use App\Models\Inventariado\Oficina;
use App\Models\ActiveSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use App\Traits\ApiResponse;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'dni' => 'required|string|min:8|max:8|unique:users',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&.]+$/',
            'telefono' => 'required|string|min:7|max:15|regex:/^[0-9\s\-\+\(\)]+$/',
            'oficina_id' => 'nullable|integer|exists:oficinas,id',
            'role_id' => 'nullable|integer|exists:roles,id'
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no debe exceder los 255 caracteres.',
            'dni.required' => 'El DNI es obligatorio.',
            'dni.min' => 'El DNI debe tener al menos 8 caracteres.',
            'dni.max' => 'El DNI no debe exceder los 8 caracteres.',
            'dni.unique' => 'Este DNI ya está registrado.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un correo electrónico válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.regex' => 'La contraseña debe contener al menos: 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.min' => 'El teléfono debe tener al menos 7 caracteres.',
            'telefono.max' => 'El teléfono no debe exceder los 20 caracteres.',
            'telefono.regex' => 'El teléfono sólo puede contener números, espacios, guiones, paréntesis y el signo +.',
            'oficina_id.exists' => 'La oficina seleccionada no es válida.',
            'role_id.exists' => 'El rol seleccionado no es válido.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $validator->errors()->toArray()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'dni' => $request->dni,
                'password' => Hash::make($request->password),
                'telefono' => $request->telefono,
                //'oficina_id' => $request->oficina_id, // Eliminar esto
                'role_id' => $request->role_id,
            ]);
            if ($request->has('oficinas')) {
                $user->oficinas()->sync($request->oficinas);
            }
            //$user->assignRole('user');

            $user->sendEmailVerificationNotification();

            Log::info('Nuevo usuario registrado', ['user_id' => $user->id]);

            return $this->successResponse([
                'user' => $user->only(['id', 'name', 'dni', 'email', 'telefono'])
            ], 'Usuario registrado con éxito. Por favor verifica tu email.', 201);

        } catch (\Exception $e) {
            Log::error('Error al registrar usuario', ['error' => $e->getMessage()]);
            
            return $this->errorResponse('Error al registrar el usuario', ['error' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'sometimes|string|max:255'
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'device_name.string' => 'El nombre del dispositivo debe ser texto.',
            'device_name.max' => 'El nombre del dispositivo no debe exceder 255 caracteres.'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse( $validator->errors()->toArray(), 'Errores de validación');
        }

        $user = User::where('email', $request->email)->with('role')->first();
        if(!$user){
            $user=Usuario::where('email', $request->email)->with('role')->first();
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::warning('Intento de inicio de sesión fallido', ['email' => $request->email]);
            return $this->errorResponse('Credenciales inválidas');
        }

        //if (!$user->hasVerifiedEmail()) {
        //    return $this->errorResponse('Por favor verifica tu dirección de email antes de iniciar sesión', ['resend_url' => url('/api/auth/email/verify/resend')]);
        //}

        $tokenName = $request->device_name ?? 'auth-token';
        $token = $user->createToken($tokenName)->plainTextToken;
        
        $accessToken = $user->tokens()->latest()->first();
        $this->logSession($user, $request, $token);

        Log::info('Inicio de sesión exitoso', ['user_id' => $user->id]);

        return $this->successResponse([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $accessToken->created_at->addMinutes((int) config('sanctum.expiration'))->diffInSeconds(now()),
            'expires_at' => $accessToken->created_at->addMinutes((int) config('sanctum.expiration'))->toDateTimeString(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at
            ],
            'role' => $user->role
        ]);
    }

    public function logout(Request $request)
    {
        try {
            $tokenId = $request->user()->currentAccessToken()->id;
            
            ActiveSession::where('token_id', $tokenId)->delete();
            $request->user()->currentAccessToken()->delete();

            Log::info('Cierre de sesión exitoso', ['user_id' => $request->user()->id]);

            return $this->successResponse([], 'Sesión cerrada correctamente');
        } catch (\Exception $e) {
            Log::error('Error al cerrar sesión', ['user_id' => $request->user()->id, 'error' => $e->getMessage()]);
            
            return $this->errorResponse('Error al cerrar sesión', null, 500);
        }
    }

    protected function logSession($user, $request, $token)
    {
        try {
            $tokenId = $user->tokens()
                ->where('token', hash('sha256', explode('|', $token)[1]))
                ->first()->id;
            
            ActiveSession::updateOrCreate(
                ['token_id' => $tokenId],
                [
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'last_activity' => now(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error al registrar sesión', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    public function userProfile(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return $this->errorResponse('Usuario no autenticado', [], 401);
        }
        
        $user->load(['activeSessions', 'oficinas']);
        return $this->successResponse([
            'user' => $user
        ], 'Perfil de usuario obtenido correctamente');
    }

    public function usuarioProfile(Request $request){
        $usuario = $this->getAuthenticatedUser($request);
        if (!$usuario) {
            return $this->errorResponse('Usuario no autenticado', [], 401);
        }
        
        $usuario->activeSessions = collect([]);
        return $this->successResponse([
            'usuario' => $usuario
        ], 'Perfil de usuario obtenido correctamente');
    }

    /**
     * Obtiene el usuario autenticado desde cualquiera de las dos tablas
     */
    private function getAuthenticatedUser(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return null;
        }

        // Buscar el token en ambas tablas
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        if (!$personalAccessToken) {
            return null;
        }

        // Determinar de qué tabla es el usuario
        $tokenableType = $personalAccessToken->tokenable_type;
        
        if ($tokenableType === User::class) {
            return User::find($personalAccessToken->tokenable_id);
        } elseif ($tokenableType === Usuario::class) {
            return Usuario::find($personalAccessToken->tokenable_id);
        }

        return null;
    }

    public function updateProfile(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return $this->errorResponse('Usuario no autenticado', [], 401);
        }

        // Determinar la tabla para la validación unique
        $table = $user instanceof User ? 'users' : 'usuarios';
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:'.$table.',email,'.$user->id,
            'current_password' => 'required_with:password|string|min:8',
            'password' => 'nullable|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
        ], [
            'name.max' => 'El nombre no debe exceder los 255 caracteres.',
            'email.unique' => 'Este correo electrónico ya está en uso.',
            'current_password.required' => 'La contraseña actual es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        try {
            if ($request->has('password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La contraseña actual no es correcta'
                    ], 401);
                }
                $user->password = Hash::make($request->password);
            }

            $user->fill($request->only(['name', 'email']));
            $user->save();

            return $this->successResponse([
                'user' => $user->fresh()
            ], 'Perfil actualizado con éxito');
        } catch (\Exception $e) {
            Log::error('Error al actualizar perfil', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return $this->errorResponse('Error al actualizar el perfil', [], 500);
        }
    }

    public function activeSessions(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return $this->errorResponse('Usuario no autenticado', [], 401);
        }
        $sessions = $user->activeSessions()->with('token')->get()->map(function ($session) {
            return [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_activity' => $session->last_activity,
                'device' => $session->token->name ?? 'Desconocido',
            ];
        });

        return $this->successResponse([
            'sessions' => $sessions
        ], 'Sesiones activas obtenidas correctamente');
    }

    public function revokeSession(Request $request, $id)
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            if (!$user) {
                return $this->errorResponse('Usuario no autenticado', [], 401);
            }

            $session = ActiveSession::where('user_id', $user->id)
                ->findOrFail($id);

            PersonalAccessToken::find($session->token_id)?->delete();
            $session->delete();

            return $this->successResponse([], 'Sesión revocada correctamente');
        } catch (\Exception $e) {
            return $this->errorResponse('No se pudo revocar la sesión', []);
        }
    }

    public function revokeAllSessions(Request $request)
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            if (!$user) {
                return $this->errorResponse('Usuario no autenticado', [], 401);
            }

            $user->tokens()->delete();
            ActiveSession::where('user_id', $user->id)->delete();

            return $this->successResponse([], 'Todas las sesiones han sido revocadas');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al revocar las sesiones', [], 500);
        }
    }

    public function forgotPassword(Request $request) {
        $request->validate(['email' => 'required|email']);
        $status = Password::sendResetLink($request->only('email'));
        return $status === Password::RESET_LINK_SENT
            ? response()->json(['success' => true, 'message' => __($status)])
            : response()->json(['success' => false, 'message' => __($status)], 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status == Password::PASSWORD_RESET
            ? response()->json(['success' => true, 'message' => __($status)])
            : response()->json(['success' => false, 'message' => __($status)], 400);
    }

    public function verifyEmail(Request $request, $id, $hash)
    {
        if (!$request->hasValidSignature()) {
            return $this->errorResponse('Enlace de verificación inválido o expirado', [], 403);
        }

        $user = User::findOrFail($id);

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return $this->successResponse([], 'Emil verificado con éxito');
    }

    public function resendVerification(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return $this->errorResponse('Usuario no autenticado', [], 401);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->errorResponse('El email ya ha sido verificado', [], 400);
        }

        $user->sendEmailVerificationNotification();

        return $this->successResponse([], 'Email de verificación reenviado');
    }

    public function usersAll(Request $request){
        if ($request->has('dni')){
            return User::with('oficinas')
                ->where('dni', $request->dni)
                ->get();
        }

        // Si no hay filtro, traer todo con relaciones
        return User::with('oficinas')->get();
    }
}
