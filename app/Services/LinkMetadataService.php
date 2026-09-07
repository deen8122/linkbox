<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LinkMetadataService
{
    public function getDomain(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return strtolower(
            preg_replace('/^www\./', '', $host)
        );
    }

    public function isYoutube(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return in_array(
            strtolower(preg_replace('/^www\./', '', $host)),
            ['youtube.com', 'youtu.be'],
            true
        );
    }

    public function getYoutubeData(string $url): ?array
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

    public function getPageTitle(string $url): ?string
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
}
