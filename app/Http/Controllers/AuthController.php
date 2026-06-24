<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Register a new user
     *
     * Creates a new user account and returns an API token.
     *
     * @unauthenticated
     *
     * @bodyParam name string required The user's full name. Example: John Doe
     * @bodyParam email string required The user's email address. Example: john@example.com
     * @bodyParam password string required The user's password (min 8 characters). Example: password123
     * @bodyParam password_confirmation string required Must match password. Example: password123
     *
     * @response 201 {
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "created_at": "2026-06-23T12:00:00.000000Z",
     *     "updated_at": "2026-06-23T12:00:00.000000Z"
     *   },
     *   "token": "1|abc123..."
     * }
     * @response 422 {
     *   "message": "The email has already been taken. (and 2 more errors)",
     *   "errors": {
     *     "email": ["The email has already been taken."],
     *     "password": ["The password field confirmation does not match."]
     *   }
     * }
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Log in an existing user
     *
     * Authenticates an existing user and returns an API token.
     *
     * @unauthenticated
     *
     * @bodyParam email string required The user's email address. Example: john@example.com
     * @bodyParam password string required The user's password. Example: password123
     *
     * @response {
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "created_at": "2026-06-23T12:00:00.000000Z",
     *     "updated_at": "2026-06-23T12:00:00.000000Z"
     *   },
     *   "token": "1|abc123..."
     * }
     * @response 401 {
     *   "message": "Invalid credentials"
     * }
     */
    public function login(LoginRequest $request, AuthService $authService): JsonResponse
    {
        $user = $authService->attemptLogin($request->only('email', 'password'));

        if (!$user) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $token = $authService->createToken($user);

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * Log out the current user
     *
     * Revokes the current API token. The token must be included in the Authorization header.
     *
     * @authenticated
     *
     * @response {
     *   "message": "Logged out successfully"
     * }
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
