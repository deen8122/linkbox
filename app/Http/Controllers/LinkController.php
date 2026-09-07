<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Services\LinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LinkController extends Controller
{
    public function __construct(
        private readonly LinkService $linkService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $search = $request->filled('search')
            ? trim($request->input('search'))
            : null;

        $links = $this->linkService->paginateForUser(
            $request->user()->id,
            $search
        );

        return response()->json($links);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
        ]);

        $link = $this->linkService->create(
            $request->user()->id,
            $data['url']
        );

        return response()->json($link, 201);
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

        $link = $this->linkService->update(
            $link,
            $data['url'],
            $data['title'] ?? null,
            $data['tags'] ?? []
        );

        return response()->json($link);
    }

    public function destroy(Request $request, Link $link): JsonResponse
    {
        abort_unless(
            $link->user_id === $request->user()->id,
            403
        );

        $this->linkService->delete($link);

        return response()->json([
            'message' => 'Ссылка удалена',
        ]);
    }
}
