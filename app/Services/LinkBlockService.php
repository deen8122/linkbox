<?php

namespace App\Services;

use App\Models\LinkBlock;
use App\Models\LinkBlockGroup;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;

class LinkBlockService
{
    private const IMAGE_DIRECTORY = 'link-blocks';

    private const CACHE_TTL_DAYS = 30;

    public const MAX_IMAGES_PER_USER = 50;

    public function __construct(
        private readonly LinkFaviconService $faviconService
    ) {}

    public function listForUser(int $userId): array
    {
        return Cache::store('file')->remember(
            $this->cacheKey($userId),
            now()->addDays(self::CACHE_TTL_DAYS),
            fn () => LinkBlock::query()
                ->where('user_id', $userId)
                ->orderBy('position')
                ->get()
                ->toArray()
        );
    }

    public function countImages(int $userId): int
    {
        return LinkBlock::query()
            ->where('user_id', $userId)
            ->whereNotNull('image')
            ->count();
    }

    public function reorder(int $userId, array $blocks): void
    {
        $groupIds = collect($blocks)
            ->pluck('link_block_group_id')
            ->filter()
            ->unique();

        $validGroupIds = LinkBlockGroup::query()
            ->where('user_id', $userId)
            ->whereIn('id', $groupIds)
            ->pluck('id');

        foreach ($blocks as $blockData) {
            $update = [
                'position' => $blockData['position'],
            ];

            if (array_key_exists('link_block_group_id', $blockData)) {
                $groupId = $blockData['link_block_group_id'];

                $update['link_block_group_id'] = $groupId && $validGroupIds->contains($groupId)
                    ? $groupId
                    : null;
            }

            LinkBlock::query()
                ->where('id', $blockData['id'])
                ->where('user_id', $userId)
                ->update($update);
        }

        $this->forgetCache($userId);
    }

    public function create(User $user, array $data, ?UploadedFile $image): LinkBlock
    {
        $block = LinkBlock::create([
            'user_id' => $user->id,
            'link_block_group_id' => $this->resolveGroupId($user->id, $data),
            'url' => $data['url'],
            'title' => $data['title'] ?? $data['url'],
            'image' => $image ? $this->storeImage($image, $user->id) : null,
            'favicon_path' => $this->faviconService->getOrDownload($data['url']),
            'position' => $this->getNextPosition($user->id),
        ]);

        $this->forgetCache($user->id);

        return $block;
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
        $linkBlock->link_block_group_id = $this->resolveGroupId($linkBlock->user_id, $data);

        $linkBlock->save();

        $this->forgetCache($linkBlock->user_id);

        return $linkBlock;
    }

    public function delete(LinkBlock $linkBlock): void
    {
        if ($linkBlock->image) {
            $this->deleteImage($linkBlock->image);
        }

        $linkBlock->delete();

        $this->forgetCache($linkBlock->user_id);
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

    private function resolveGroupId(int $userId, array $data): ?int
    {
        if (empty($data['link_block_group_id'])) {
            return null;
        }

        $exists = LinkBlockGroup::query()
            ->where('id', $data['link_block_group_id'])
            ->where('user_id', $userId)
            ->exists();

        return $exists ? (int) $data['link_block_group_id'] : null;
    }

    private function getNextPosition(int $userId): int
    {
        return (int) LinkBlock::query()
                ->where('user_id', $userId)
                ->max('position') + 1;
    }

    private function cacheKey(int $userId): string
    {
        return "link-blocks:user:{$userId}";
    }

    private function forgetCache(int $userId): void
    {
        Cache::store('file')->forget($this->cacheKey($userId));
    }
}
