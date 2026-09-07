<?php

namespace App\Services;

use App\Models\Link;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LinkService
{
    private const PER_PAGE = 5;

    public function __construct(
        private readonly LinkMetadataService $metadataService,
        private readonly TagService $tagService
    ) {}

    public function paginateForUser(int $userId, ?string $search): LengthAwarePaginator
    {
        $query = Link::query()
            ->with('tags')
            ->where('user_id', $userId);

        if ($search !== null) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        return $query
            ->latest()
            ->paginate(self::PER_PAGE);
    }

    public function create(int $userId, string $url): Link
    {
        $domain = $this->metadataService->getDomain($url);

        $youtubeData = $this->metadataService->isYoutube($url)
            ? $this->metadataService->getYoutubeData($url)
            : null;

        $title = $youtubeData['title']
            ?? $this->metadataService->getPageTitle($url);

        $link = Link::create([
            'url' => $url,
            'title' => $title,
            'user_id' => $userId,
        ]);

        $tags = [
            $domain,
        ];

        if ($youtubeData && !empty($youtubeData['author_name'])) {
            $tags[] = $youtubeData['author_name'];
        }

        foreach ($tags as $tagName) {
            $tag = $this->tagService->getOrCreate($tagName, $userId);

            $link->tags()->attach($tag);
        }

        return $link->load('tags');
    }

    public function update(Link $link, string $url, ?string $title, array $tagNames): Link
    {
        $link->update([
            'url' => $url,
            'title' => $title ?: null,
        ]);

        $tagIds = [];

        foreach ($tagNames as $tagName) {
            $tagName = trim($tagName);

            if ($tagName === '') {
                continue;
            }

            $tag = $this->tagService->getOrCreate($tagName, $link->user_id);

            $tagIds[] = $tag->id;
        }

        $link->tags()->sync($tagIds);

        return $link->load('tags');
    }

    public function delete(Link $link): void
    {
        $link->delete();
    }
}
