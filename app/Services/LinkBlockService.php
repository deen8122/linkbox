<?php

namespace App\Services;

use App\Models\LinkBlock;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;

class LinkBlockService
{
    private const IMAGE_DIRECTORY = 'link-blocks';

    public function __construct(
        private readonly LinkFaviconService $faviconService
    ) {}

    public function listForUser(int $userId): Collection
    {
        return LinkBlock::query()
            ->where('user_id', $userId)
            ->orderBy('position')
            ->get();
    }

    public function reorder(int $userId, array $blocks): void
    {
        foreach ($blocks as $blockData) {
            LinkBlock::query()
                ->where('id', $blockData['id'])
                ->where('user_id', $userId)
                ->update([
                    'position' => $blockData['position'],
                ]);
        }
    }

    public function create(User $user, array $data, ?UploadedFile $image): LinkBlock
    {
        return LinkBlock::create([
            'user_id' => $user->id,
            'url' => $data['url'],
            'title' => $data['title'] ?? $data['url'],
            'image' => $image ? $this->storeImage($image, $user->id) : null,
            'favicon_path' => $this->faviconService->getOrDownload($data['url']),
            'position' => $this->getNextPosition($user->id),
        ]);
    }

    public function update(
        LinkBlock $linkBlock,
        array $data,
        ?UploadedFile $image,
        bool $removeImage
    ): LinkBlock {
        if ($removeImage && $linkBlock->image) {
            $this->deleteImage($linkBlock->image);

            $linkBlock->image = null;
        }

        if ($image) {
            if ($linkBlock->image) {
                $this->deleteImage($linkBlock->image);
            }

            $linkBlock->image = $this->storeImage($image, $linkBlock->user_id);
        }

        $faviconPath = $this->faviconService->getOrDownload($data['url']);

        if ($faviconPath) {
            $linkBlock->favicon_path = $faviconPath;
        }

        $linkBlock->url = $data['url'];
        $linkBlock->title = $data['title'] ?? $data['url'];

        $linkBlock->save();

        return $linkBlock;
    }

    public function delete(LinkBlock $linkBlock): void
    {
        if ($linkBlock->image) {
            $this->deleteImage($linkBlock->image);
        }

        $linkBlock->delete();
    }

    private function storeImage(UploadedFile $file, int $userId): string
    {
        $image = Image::decode($file);
        $image->cover(300, 260);

        $path = self::IMAGE_DIRECTORY . '/' . $userId . '/' . uniqid() . '.webp';

        Storage::disk('public')->put(
            $path,
            $image->encode(new WebpEncoder(quality: 100))
        );

        return $path;
    }

    private function deleteImage(string $path): void
    {
        Storage::disk('public')->delete($path);
    }

    private function getNextPosition(int $userId): int
    {
        return (int) LinkBlock::query()
                ->where('user_id', $userId)
                ->max('position') + 1;
    }
}
