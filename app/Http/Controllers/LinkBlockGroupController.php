<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderLinkBlockGroupRequest;
use App\Http\Requests\StoreLinkBlockGroupRequest;
use App\Http\Requests\UpdateLinkBlockGroupRequest;
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

    public function store(StoreLinkBlockGroupRequest $request): JsonResponse
    {
        $data = $request->validated();

        $group = $this->linkBlockGroupService->create(
            $request->user(),
            $data
        );

        return response()->json($group, 201);
    }

    public function update(
        UpdateLinkBlockGroupRequest $request,
        LinkBlockGroup $linkBlockGroup
    ): JsonResponse {
        abort_unless(
            $linkBlockGroup->user_id === $request->user()->id,
            403
        );

        $data = $request->validated();

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

    public function reorder(ReorderLinkBlockGroupRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->linkBlockGroupService->reorder(
            $request->user()->id,
            $data['groups']
        );

        return response()->json([
            'message' => 'Порядок сохранён',
        ]);
    }
}
