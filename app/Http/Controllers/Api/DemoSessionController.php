<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DemoSessionController extends Controller
{
    public function __invoke(): JsonResponse
    {
        abort_unless(config('lunch.hosted_demo'), 404);

        // The lock serializes the check and insert across web workers. The
        // disposable database is the source of truth, even after a cache reset.
        return Cache::lock('hosted-demo:visitor-creation', 5)->block(2, function (): JsonResponse {
            if (User::query()->where('email', 'like', 'demo-%@example.invalid')->count() >= 200) {
                return response()->json(['message' => 'Демо временно заполнено. Попробуйте позже.'], 429);
            }

            $id = (string) Str::uuid();
            $user = User::query()->create([
                'name' => 'Гость демо',
                'email' => "demo-{$id}@example.invalid",
                'password' => null,
                'role' => UserRole::User,
                'is_active' => true,
            ]);

            return response()->json([
                'data' => [
                    'token' => $user->createToken('hosted-demo')->plainTextToken,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'role' => $user->role->value,
                    ],
                ],
            ], 201);
        });
    }
}
