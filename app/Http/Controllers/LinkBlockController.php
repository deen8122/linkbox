<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderLinkBlockRequest;
use App\Http\Requests\StoreLinkBlockRequest;
use App\Http\Requests\UpdateLinkBlockRequest;
use App\Models\LinkBlock;
use App\Services\LinkBlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LinkBlockController extends Controller
{
    public function __construct(
        private readonly LinkBlockService $linkBlockService
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->linkBlockService->listForUser(
                $request->user()->id
            )
        );
    }

    public function reorder(ReorderLinkBlockRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->linkBlockService->reorder(
            $request->user()->id,
            $data['blocks']
        );

        return response()->json([
            'message' => 'Порядок сохранён',
        ]);
    }

    public function store(StoreLinkBlockRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (
            $request->file('image')
            && $this->linkBlockService->countImages($request->user()->id) >= LinkBlockService::MAX_IMAGES_PER_USER
        ) {
            return response()->json([
                'message' => 'Достигнут лимит загруженных фото: максимум '
                    . LinkBlockService::MAX_IMAGES_PER_USER,
            ], 422);
        }

        $block = $this->linkBlockService->create(
            $request->user(),
            $data,
            $request->file('image')
        );

        return response()->json($block, 201);
    }

    public function update(
        UpdateLinkBlockRequest $request,
        LinkBlock $linkBlock
    ): JsonResponse {
        abort_unless(
            $linkBlock->user_id === $request->user()->id,
            403
        );

        $data = $request->validated();

        if (
            $request->file('image')
            && !$linkBlock->image
            && $this->linkBlockService->countImages($request->user()->id) >= LinkBlockService::MAX_IMAGES_PER_USER
        ) {
            return response()->json([
                'message' => 'Достигнут лимит загруженных фото: максимум '
                    . LinkBlockService::MAX_IMAGES_PER_USER,
            ], 422);
        }

        $linkBlock = $this->linkBlockService->update(
            $linkBlock,
            $data,
            $request->file('image'),
            !empty($data['remove_image'])
        );

        return response()->json($linkBlock);
    }

    public function destroy(
        Request $request,
        LinkBlock $linkBlock
    ): JsonResponse {
        abort_unless(
            $linkBlock->user_id === $request->user()->id,
            403
        );

        $this->linkBlockService->delete($linkBlock);

        return response()->json([
            'message' => 'Ссылка удалена',
        ]);
    }
}
