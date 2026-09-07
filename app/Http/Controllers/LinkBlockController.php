<?php

namespace App\Http\Controllers;

use App\Models\LinkBlock;
use App\Services\LinkFaviconService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;

class LinkBlockController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            LinkBlock::query()
                ->where('user_id', $request->user()->id)
                ->orderBy('position')
                ->get()
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

        $userId = $request->user()->id;

        foreach ($data['blocks'] as $blockData) {
            LinkBlock::query()
                ->where('id', $blockData['id'])
                ->where('user_id', $userId)
                ->update([
                    'position' => $blockData['position'],
                ]);
        }

        return response()->json([
            'message' => 'Порядок сохранён',
        ]);
    }
    public function store(Request $request, LinkFaviconService $faviconService): JsonResponse
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

        $user = $request->user();

        $imagePath = null;


        if ($request->hasFile('image')) {
            $file = $request->file('image');

            $image = Image::decode($file);

            $image->cover(300, 260);

            $filename = uniqid() . '.webp';

            $path = 'link-blocks/' .$user->id.'/'. $filename;
            $encoded = $image->encode(
                new WebpEncoder(quality: 100)
            );
            Storage::disk('public')->put(
                $path,
                $encoded
            );

            $imagePath = $path;
        }

        $faviconPath = $faviconService->getOrDownload(
            $data['url']
        );
        $block = LinkBlock::create([
            'user_id' => $user->id,
            'url' => $data['url'],
            'title' => $data['title'] ?? $data['url'],
            'image' => $imagePath,
            'favicon_path' => $faviconPath,
            'position' => $this->getNextPosition($user->id),
        ]);

        return response()->json($block, 201);
    }

    public function update(
        Request $request,
        LinkBlock $linkBlock,
        LinkFaviconService $faviconService
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

        if (
            !empty($data['remove_image']) &&
            $linkBlock->image
        ) {
            Storage::disk('public')->delete(
                $linkBlock->image
            );

            $linkBlock->image = null;
        }

        if ($request->hasFile('image')) {
            if ($linkBlock->image_path) {
                Storage::disk('public')->delete(
                    $linkBlock->image_path
                );
            }

            $image = Image::decode(
                $request->file('image')
            );

            $image->cover(300, 260);

            $filename = uniqid() . '.webp';

            $path = 'link-blocks/' .$request->user()->id.'/'.$filename;
            $encoded = $image->encode(
                new WebpEncoder(quality: 100)
            );
            Storage::disk('public')->put(
                $path,
                $encoded
            );

            $linkBlock->image = $path;
        }

        $faviconPath = $faviconService->getOrDownload(
            $data['url']
        );
        if ($faviconPath) {
            $linkBlock->favicon_path = $faviconPath;
        }
        $linkBlock->url = $data['url'];
        $linkBlock->title = $data['title'] ?? $data['url'];

        $linkBlock->save();

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

        if ($linkBlock->image) {
            Storage::disk('public')->delete(
                $linkBlock->image
            );
        }

        $linkBlock->delete();

        return response()->json([
            'message' => 'Ссылка удалена',
        ]);
    }

    private function getNextPosition(int $userId): int
    {
        return (int) LinkBlock::query()
                ->where('user_id', $userId)
                ->max('position') + 1;
    }
}
