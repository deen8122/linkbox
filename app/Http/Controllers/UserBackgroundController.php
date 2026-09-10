<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserBackgroundRequest;
use App\Services\UserBackgroundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserBackgroundController extends Controller
{
    public function __construct(
        private readonly UserBackgroundService $userBackgroundService
    ) {}

    public function update(UpdateUserBackgroundRequest $request): JsonResponse
    {
        $user = $this->userBackgroundService->update(
            $request->user(),
            $request->file('image')
        );

        return response()->json($user);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $this->userBackgroundService->remove(
            $request->user()
        );

        return response()->json($user);
    }
}
