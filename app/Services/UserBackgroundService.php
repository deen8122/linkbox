<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;

class UserBackgroundService
{
    private const DIRECTORY = 'backgrounds';

    public function update(User $user, UploadedFile $file): User
    {
        if ($user->background_path) {
            $this->deleteImage($user->background_path);
        }

        $user->background_path = $this->storeImage($file, $user->id);
        $user->save();

        return $user;
    }

    public function remove(User $user): User
    {
        if ($user->background_path) {
            $this->deleteImage($user->background_path);

            $user->background_path = null;
            $user->save();
        }

        return $user;
    }

    private function storeImage(UploadedFile $file, int $userId): string
    {
        $image = Image::decode($file);
        $image->scaleDown(1920, 1920);

        $path = self::DIRECTORY . '/' . $userId . '/' . uniqid() . '.webp';

        Storage::disk('public')->put(
            $path,
            $image->encode(new WebpEncoder(quality: 85))
        );

        return $path;
    }

    private function deleteImage(string $path): void
    {
        Storage::disk('public')->delete($path);
    }
}
