<?php

namespace App\Http\Controllers;

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

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'blocks' => [
                'required',
                'array',
            ],

            'blocks.*.id' => [
                'required',
                'integer',
            ],

            'blocks.*.position' => [
                'required',
                'integer',
                'min:0',
            ],
        ]);

        $this->linkBlockService->reorder(
            $request->user()->id,
            $data['blocks']
        );

        return response()->json([
            'message' => 'Порядок сохранён',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url' => [
                'required',
                'url',
                'max:2048',
            ],
            'title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp,gif',
                'max:5120',
            ],
        ]);

        $block = $this->linkBlockService->create(
            $request->user(),
            $data,
            $request->file('image')
        );

        return response()->json($block, 201);
    }

    public function update(
        Request $request,
        LinkBlock $linkBlock
    ): JsonResponse {
        abort_unless(
            $linkBlock->user_id === $request->user()->id,
            403
        );

        $data = $request->validate([
            'url' => [
                'required',
                'url',
                'max:2048',
            ],
            'title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp,gif',
                'max:5120',
            ],
            'remove_image' => [
                'boolean',
            ],
        ]);

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
