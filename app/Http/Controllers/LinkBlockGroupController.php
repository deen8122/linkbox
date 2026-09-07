<?php

namespace App\Http\Controllers;

use App\Models\LinkBlockGroup;
use App\Services\LinkBlockGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LinkBlockGroupController extends Controller
{
    public function __construct(
        private readonly LinkBlockGroupService $linkBlockGroupService
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->linkBlockGroupService->listForUser(
                $request->user()->id
            )
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $group = $this->linkBlockGroupService->create(
            $request->user(),
            $data
        );

        return response()->json($group, 201);
    }

    public function update(
        Request $request,
        LinkBlockGroup $linkBlockGroup
    ): JsonResponse {
        abort_unless(
            $linkBlockGroup->user_id === $request->user()->id,
            403
        );

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $group = $this->linkBlockGroupService->update(
            $linkBlockGroup,
            $data
        );

        return response()->json($group);
    }

    public function destroy(
        Request $request,
        LinkBlockGroup $linkBlockGroup
    ): JsonResponse {
        abort_unless(
            $linkBlockGroup->user_id === $request->user()->id,
            403
        );

        $this->linkBlockGroupService->delete($linkBlockGroup);

        return response()->json([
            'message' => 'Категория удалена',
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'groups' => [
                'required',
                'array',
            ],

            'groups.*.id' => [
                'required',
                'integer',
            ],

            'groups.*.position' => [
                'required',
                'integer',
                'min:0',
            ],
        ]);

        $this->linkBlockGroupService->reorder(
            $request->user()->id,
            $data['groups']
        );

        return response()->json([
            'message' => 'Порядок сохранён',
        ]);
    }
}
