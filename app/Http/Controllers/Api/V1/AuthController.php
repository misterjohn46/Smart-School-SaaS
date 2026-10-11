<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RoleName;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;

class AuthController extends BaseApiController
{
    /**
     * Register a new user and issue a Sanctum token.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation failed',
                $validator->errors(),
                422
            );
        }

        $user = User::create($validator->validated());
        $user->assignRole(Role::findOrCreate(RoleName::User->value));
        $user->load('roles');

        $token = $user->createToken(
            'auth_token',
            ['*'],
            now()->addMinutes((int) config('sanctum.expiration')),
        )->plainTextToken;

        return $this->successResponse([
            'user' => UserResource::make($user)->resolve(),
            'token' => $token,
        ], 'User registered successfully', 201);
    }

    /**
     * Authenticate user and issue a Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'lowercase', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation failed',
                $validator->errors(),
                422
            );
        }

        if (! Auth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse('Invalid credentials', null, 401);
        }

        /** @var User $user */
        $user = Auth::user();
        $user->tokens()->delete();
        $token = $user->createToken(
            'auth_token',
            ['*'],
            now()->addMinutes((int) config('sanctum.expiration')),
        )->plainTextToken;
        $user->load('roles');

        return $this->successResponse([
            'user' => UserResource::make($user)->resolve(),
            'token' => $token,
        ], 'Login successful');
    }

    /**
     * Revoke the current access token (logout).
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $accessToken = $user->currentAccessToken();

        if ($accessToken instanceof PersonalAccessToken) {
            $accessToken->delete();
        }

        return $this->successResponse(null, 'Logged out successfully');
    }
}
