<?php

namespace App\Repository\V2\Shared\Base\StorageMedia;

use App\Models\Media\StorageMedia;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use function App\Repository\Global\StorageMedia\str_starts_with;

class RenameMediaRepo
{
    /**
     * @param string $title
     * @param string $storageMediaId
     * @param string|null $path
     * @return bool
     */
    public static function run(string $title, string $storageMediaId, string $path = null)
    {

        $storageMedia = StorageMedia::query()->find($storageMediaId);
        if (!$path) {
            $path = auth()->id();
        }
        if (str_starts_with($storageMedia->path, 'http')) {
            return $storageMedia->update([
                'name' => $title
            ]);
        }

        $filename = 'storage/media/' . $path . '/' . fake()->uuid() . '-' . Str::slug($title) . '.' . $storageMedia->type;
        $pathX = ltrim($storageMedia?->path, '/');

        File::move(public_path($pathX), public_path($filename));

        return $storageMedia->update([
            'path' => '/' . $filename,
            'name' => $title,
        ]);
    }
}
