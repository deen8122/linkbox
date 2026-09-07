<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LinkController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Link::query()
            ->with('tags')
            ->where('user_id', $request->user()->id);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where('title', 'like', '%' . $search . '%');
        }

        $links = $query
            ->latest()
            ->paginate(5);

        return response()->json($links);
    }
    public function update(Request $request, Link $link): JsonResponse
    {
        abort_unless(
            $link->user_id === $request->user()->id,
            403
        );

        $data = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'title' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
        ]);

        $link->update([
            'url' => $data['url'],
            'title' => $data['title'] ?: null,
        ]);

        /*
         * Обновляем теги.
         */
        $tagIds = [];

        foreach ($data['tags'] ?? [] as $tagName) {
            $tagName = trim($tagName);

            if ($tagName === '') {
                continue;
            }

            $tag = $this->getOrCreateTag($tagName, $request->user()->id);

            $tagIds[] = $tag->id;
        }

        $link->tags()->sync($tagIds);

        return response()->json(
            $link->load('tags')
        );
    }
    public function destroy(Request $request, Link $link): JsonResponse
    {
        abort_unless(
            $link->user_id === $request->user()->id,
            403
        );

        $link->delete();

        return response()->json([
            'message' => 'Ссылка удалена',
        ]);
    }
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
        ]);

        $url = $data['url'];

        $domain = $this->getDomain($url);

        $youtubeData = $this->isYoutube($url)
            ? $this->getYoutubeData($url)
            : null;

        $title = $youtubeData['title']
            ?? $this->getPageTitle($url);

        $link = Link::create([
            'url' => $url,
            'title' => $title,
            'user_id' => $request->user()->id,
        ]);

        $tags = [
            $domain,
        ];

        if ($youtubeData && !empty($youtubeData['author_name'])) {
            $tags[] = $youtubeData['author_name'];
        }

        foreach ($tags as $tagName) {
            $tag = $this->getOrCreateTag($tagName,$request->user()->id);

            $link->tags()->attach($tag);
        }

        return response()->json(
            $link->load('tags'),
            201
        );
    }

    private function getOrCreateTag(
        string $name,
        int $userId
    ): Tag {
        return Tag::firstOrCreate(
            [
                'user_id' => $userId,
                'slug' => Str::slug($name),
            ],
            [
                'name' => $name,
            ],
        );
    }

    private function getPageTitle(string $url): ?string
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0',
                ])
                ->get($url);

            if (!$response->successful()) {
                return null;
            }

            if (preg_match(
                '/<title[^>]*>(.*?)<\/title>/is',
                $response->body(),
                $matches
            )) {
                return trim(
                    html_entity_decode(
                        strip_tags($matches[1]),
                        ENT_QUOTES | ENT_HTML5,
                        'UTF-8'
                    )
                );
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function isYoutube(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return in_array(
            strtolower(preg_replace('/^www\./', '', $host)),
            ['youtube.com', 'youtu.be'],
            true
        );
    }

    private function getYoutubeData(string $url): ?array
    {
        try {
            $response = Http::timeout(10)
                ->get('https://www.youtube.com/oembed', [
                    'url' => $url,
                    'format' => 'json',
                ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Throwable) {
            //
        }

        return null;
    }
    private function getDomain(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return strtolower(
            preg_replace('/^www\./', '', $host)
        );
    }
}
