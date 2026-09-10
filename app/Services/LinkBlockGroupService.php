<?php

namespace App\Services;

use App\Models\LinkBlockGroup;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class LinkBlockGroupService
{
    private const CACHE_TTL_DAYS = 30;

    public function listForUser(int $userId): array
    {
        return Cache::store('file')->remember(
            $this->cacheKey($userId),
            now()->addDays(self::CACHE_TTL_DAYS),
            fn () => LinkBlockGroup::query()
                ->where('user_id', $userId)
                ->orderBy('position')
                ->get()
                ->toArray()
        );
    }

    public function create(User $user, array $data): LinkBlockGroup
    {
        $group = LinkBlockGroup::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'color' => $data['color'] ?? null,
            'background_color' => $data['background_color'] ?? null,
            'position' => $this->getNextPosition($user->id),
        ]);

        $this->forgetCache($user->id);

        return $group;
    }

    public function update(LinkBlockGroup $group, array $data): LinkBlockGroup
    {
        $group->name = $data['name'];
        $group->color = $data['color'] ?? null;
        $group->background_color = $data['background_color'] ?? null;
        $group->save();

        $this->forgetCache($group->user_id);

        return $group;
    }

    public function delete(LinkBlockGroup $group): void
    {
        $group->delete();

        $this->forgetCache($group->user_id);
    }

    public function reorder(int $userId, array $groups): void
    {
        foreach ($groups as $groupData) {
            LinkBlockGroup::query()
                ->where('id', $groupData['id'])
                ->where('user_id', $userId)
                ->update([
                    'position' => $groupData['position'],
                ]);
        }

        $this->forgetCache($userId);
    }

    private function getNextPosition(int $userId): int
    {
        return (int) LinkBlockGroup::query()
                ->where('user_id', $userId)
                ->max('position') + 1;
    }

    private function cacheKey(int $userId): string
    {
        return "link-block-groups:user:{$userId}";
    }

    private function forgetCache(int $userId): void
    {
        Cache::store('file')->forget($this->cacheKey($userId));
    }
}
