<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class LinkFaviconService
{
    private const DIRECTORY = 'favicons';

    public function getOrDownload(string $url): ?string
    {
        $domain = $this->getDomain($url);

        if (!$domain) {
            return null;
        }

        $existingPath = $this->findExisting($domain);

        if ($existingPath) {
            return $existingPath;
        }

        return $this->download($domain);
    }

    private function getDomain(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (!$host) {
            return null;
        }

        return strtolower(
            preg_replace('/^www\./', '', $host)
        );
    }

    private function findExisting(string $domain): ?string
    {
        $files = Storage::disk('public')->files(
            self::DIRECTORY
        );

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_FILENAME) === $domain) {
                return $file;
            }
        }

        return null;
    }

    private function download(string $domain): ?string
    {
        $faviconUrl = "https://{$domain}/favicon.ico";

        try {
            $response = Http::timeout(2)
                ->connectTimeout(2)
                ->withoutVerifying()
                ->get($faviconUrl);

            if (!$response->successful()) {
                return null;
            }

            $content = $response->body();

            if (!$content) {
                return null;
            }

            $contentType = strtolower(
                $response->header('Content-Type', '')
            );

            if (
                !str_contains($contentType, 'image/') &&
                !str_contains($contentType, 'icon')
            ) {
                return null;
            }

            $extension = $this->getExtension($contentType);

            $path = self::DIRECTORY . '/' . $domain . '.' . $extension;

            Storage::disk('public')->put(
                $path,
                $content
            );

            return $path;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    private function getExtension(string $contentType): string
    {
        return match (true) {
            str_contains($contentType, 'png') => 'png',
            str_contains($contentType, 'jpeg'),
            str_contains($contentType, 'jpg') => 'jpg',
            str_contains($contentType, 'webp') => 'webp',
            str_contains($contentType, 'svg') => 'svg',
            default => 'ico',
        };
    }
}
