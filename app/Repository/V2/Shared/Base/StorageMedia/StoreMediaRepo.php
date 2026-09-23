<?php

namespace App\Repository\V2\Shared\Base\StorageMedia;

use App\Models\Media\StorageMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class StoreMediaRepo
{
    /**
     * @param Request $request
     * @param string|null $path
     * @return Builder|Model|null
     */
    public static function run(Request $request, string $path = null): Builder|Model|null
    {

        if (!$path) {
            $path = auth()->id();
        }

        if ($request->file('media')) {
            $basePath = '/storage/media/' . $path . '/';
            $file = $request->file('media');
            $ext = $file->getClientOriginalExtension();
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $filename = $basePath . fake()->uuid() . '-' .  Str::slug($originalName) . '.' . $ext;
            $filenameThumb = $basePath . 'thumb-' . fake()->uuid() . '-' .  Str::slug($originalName) . '.' . $ext;
            // Check if the directory already exists
            if (!Storage::exists($storage = 'public/media/' . $path)) {
                // Create the directory
                Storage::makeDirectory($storage, 0777, true, true);
                // Set permissions to 777
                // Command to set permissions
                $command = 'chmod -R ugo+rwx ./storage/';

                // Execute the command
                exec($command, $output, $return_var);
            }

            $file->move(public_path('storage/media/' . $path), $filename);

            return StorageMedia::query()->create([
                'path' => $filename,
                'thumb' => $ext !== 'pdf' ? $filenameThumb : '',
                'user_id' => auth()->id(),
                'name' => $originalName,
                'type' => $ext,
            ]);
        }
        return null;
    }
}
