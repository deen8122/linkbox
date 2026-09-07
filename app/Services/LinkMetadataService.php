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

            $body = $this->toUtf8(
                $response->body(),
                $response->header('Content-Type')
            );

            if (preg_match(
                '/<title[^>]*>(.*?)<\/title>/is',
                $body,
                $matches
            )) {
                $title = trim(
                    html_entity_decode(
                        strip_tags($matches[1]),
                        ENT_QUOTES | ENT_HTML5,
                        'UTF-8'
                    )
                );

                return $this->sanitizeUtf8($title);
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    /**
     * Страницы часто отдают HTML не в UTF-8 (например, windows-1251
     * у старых рунет-сайтов), а Content-Type/<meta charset> — это
     * единственный способ узнать реальную кодировку тела ответа.
     */
    private function toUtf8(string $body, ?string $contentTypeHeader): string
    {
        $charset = $this->detectCharset($body, $contentTypeHeader);

        if (!$charset || strcasecmp($charset, 'UTF-8') === 0) {
            return $body;
        }

        $converted = @mb_convert_encoding($body, 'UTF-8', $charset);

        return $converted !== false ? $converted : $body;
    }

    private function detectCharset(string $body, ?string $contentTypeHeader): ?string
    {
        if ($contentTypeHeader && preg_match(
            '/charset=["\']?([a-zA-Z0-9_\-]+)/i',
            $contentTypeHeader,
            $matches
        )) {
            return $matches[1];
        }

        if (preg_match(
            '/<meta[^>]+charset=["\']?([a-zA-Z0-9_\-]+)/i',
            substr($body, 0, 4096),
            $matches
        )) {
            return $matches[1];
        }

        return null;
    }

    private function sanitizeUtf8(string $value): string
    {
        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        return mb_scrub($value, 'UTF-8');
    }
}
