<?php

namespace App\Services;

use App\Models\LinkBlockGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class LinkBlockGroupService
{
    public function listForUser(int $userId): Collection
    {
        return LinkBlockGroup::query()
            ->where('user_id', $userId)
            ->orderBy('position')
            ->get();
    }

    public function create(User $user, array $data): LinkBlockGroup
    {
        return LinkBlockGroup::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'color' => $data['color'] ?? null,
            'background_color' => $data['background_color'] ?? null,
            'position' => $this->getNextPosition($user->id),
        ]);
    }

    public function update(LinkBlockGroup $group, array $data): LinkBlockGroup
    {
        $group->name = $data['name'];
        $group->color = $data['color'] ?? null;
        $group->background_color = $data['background_color'] ?? null;
        $group->save();

        return $group;
    }

    public function delete(LinkBlockGroup $group): void
    {
        $group->delete();
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
    }

    private function getNextPosition(int $userId): int
    {
        return (int) LinkBlockGroup::query()
                ->where('user_id', $userId)
                ->max('position') + 1;
    }
}
