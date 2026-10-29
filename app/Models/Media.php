<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\LogsActivity;
use App\Models\Scopes\CurrentSite;
use App\Models\Scopes\NoCurrentSiteException;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * An uploaded image belonging to one site. Fields of type "image" store a
 * media id; the API resolves it to {url, alt, width, height, variants, srcset}.
 *
 * Each upload also gets resized WebP copies (`variants`, see VARIANT_WIDTHS)
 * for images wider than them, a sha256 `hash` (identical files are refused),
 * and `stored_bytes` (original plus copies) which counts against the plan's
 * storage limit.
 */
#[Fillable(['site_id', 'folder_id', 'disk', 'path', 'original_name', 'mime_type', 'size', 'hash', 'width', 'height', 'variants', 'stored_bytes', 'alt'])]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use BelongsToSite, HasFactory, LogsActivity;

    /**
     * Allowed upload extensions. SVG is deliberately excluded: served from
     * the CMS origin it could carry script.
     *
     * @var array<int, string>
     */
    const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /**
     * Maximum upload size in kilobytes.
     *
     * @var int
     */
    const MAX_KILOBYTES = 5120;

    /**
     * Longest side accepted, in pixels, so a small file can't decompress
     * into an enormous bitmap while being resized.
     *
     * @var int
     */
    const MAX_DIMENSION = 8000;

    /**
     * Widths of the resized copies, by name. Only widths below the original's
     * are made. Animated GIFs get none.
     *
     * @var array<string, int>
     */
    const VARIANT_WIDTHS = ['thumb' => 320, 'medium' => 768, 'large' => 1600];

    private const VARIANT_QUALITY = 82;

    private const MAX_PIXELS = 40_000_000;

    protected $table = 'media';

    public function activityLabel(): string
    {
        return $this->original_name;
    }

    /**
     * File bookkeeping isn't something an editor did; replacing a file is
     * logged as its own event.
     *
     * @return array<int, string>
     */
    public function activityIgnored(): array
    {
        return ['path', 'mime_type', 'size', 'hash', 'width', 'height', 'variants', 'stored_bytes', 'disk'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'site_id' => 'integer',
            'folder_id' => 'integer',
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'variants' => 'array',
            'stored_bytes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (Media $media): void {
            Storage::disk($media->disk)->delete($media->filePaths());
        });
    }

    /**
     * @return BelongsTo<MediaFolder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    /**
     * @return HasMany<MediaUsage, $this>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class);
    }

    /**
     * Validation rules for an uploaded image file.
     *
     * @return array<int, string>
     */
    public static function fileRules(): array
    {
        return [
            'required',
            'file',
            'mimes:'.implode(',', self::EXTENSIONS),
            'max:'.self::MAX_KILOBYTES,
            'dimensions:max_width='.self::MAX_DIMENSION.',max_height='.self::MAX_DIMENSION,
        ];
    }

    /**
     * Store an uploaded file under the current site and record it.
     *
     * @throws DuplicateMediaException when the site already has this exact file
     * @throws ValidationException when the plan's storage limit would be exceeded
     */
    public static function storeUpload(UploadedFile $file, string $alt, ?int $folderId = null): self
    {
        $siteId = app(CurrentSite::class)->id() ?? throw NoCurrentSiteException::forModel(self::class);
        $hash = hash_file('sha256', $file->getRealPath());

        if ($existing = self::where('hash', $hash)->first()) {
            throw new DuplicateMediaException($existing);
        }

        self::ensureRoomFor($file->getSize());

        return self::create(self::ingest($file, $siteId, $hash) + [
            'site_id' => $siteId,
            // Only a folder of this site counts; anything else lands in the root.
            'folder_id' => $folderId !== null && MediaFolder::whereKey($folderId)->exists() ? $folderId : null,
            'alt' => $alt,
        ]);
    }

    /**
     * Swap the stored file for a new one, keeping the id (so every page using
     * the image picks it up), the alt text and the folder.
     *
     * @throws DuplicateMediaException when another image is already this exact file
     * @throws ValidationException when the plan's storage limit would be exceeded
     */
    public function replaceFile(UploadedFile $file): void
    {
        $hash = hash_file('sha256', $file->getRealPath());

        if ($other = self::where('hash', $hash)->whereKeyNot($this->id)->first()) {
            throw new DuplicateMediaException($other);
        }

        self::ensureRoomFor(max(0, $file->getSize() - $this->stored_bytes));

        $disk = $this->disk;
        $oldPaths = $this->filePaths();

        $this->update(self::ingest($file, $this->site_id, $hash));

        Storage::disk($disk)->delete($oldPaths);
    }

    /**
     * Bytes the current site's library occupies.
     */
    public static function storageUsed(): int
    {
        return (int) self::sum('stored_bytes');
    }

    /**
     * @throws ValidationException when $bytes more would pass the plan's limit
     */
    public static function ensureRoomFor(int $bytes): void
    {
        $limit = app(CurrentSite::class)->get()?->storageLimit();
        $used = self::storageUsed();

        if ($limit !== null && $used + $bytes > $limit) {
            throw ValidationException::withMessages([
                'file' => 'The storage limit is reached ('.self::formatBytes($used).' of '.self::formatBytes($limit).' used). Delete unused images to free space.',
            ]);
        }
    }

    public static function formatBytes(int $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $i => $unit) {
            if ($bytes < 1024 ** ($i + 1) || $unit === 'GB') {
                return ($i === 0 ? $bytes : round($bytes / 1024 ** $i, 1)).' '.$unit;
            }
        }

        return $bytes.' B';
    }

    /**
     * Store the file and its resized copies, returning the columns to save.
     *
     * @return array<string, mixed>
     */
    private static function ingest(UploadedFile $file, int $siteId, string $hash): array
    {
        $disk = config('filesystems.media_disk');
        $dimensions = @getimagesize($file->getRealPath()) ?: [null, null];
        $path = $file->store("sites/{$siteId}", $disk);
        $variants = self::makeVariants($disk, $path);

        return [
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
            'hash' => $hash,
            'width' => $dimensions[0],
            'height' => $dimensions[1],
            'variants' => $variants ?: null,
            'stored_bytes' => $file->getSize() + array_sum(array_column($variants, 'size')),
        ];
    }

    /**
     * Write WebP copies of the stored image at each VARIANT_WIDTHS width below
     * its own. Never throws: an image that can't be resized is simply served
     * as is.
     *
     * @return array<string, array{path: string, width: int, height: int, size: int}>
     */
    public static function makeVariants(string $disk, string $path): array
    {
        if (! function_exists('imagewebp')) {
            return [];
        }

        try {
            $storage = Storage::disk($disk);
            $bytes = $storage->get($path);
            $info = @getimagesizefromstring($bytes);

            if (! $info || $info[2] === IMAGETYPE_GIF || $info[0] * $info[1] > self::MAX_PIXELS) {
                return [];
            }

            $image = @imagecreatefromstring($bytes);

            if (! $image) {
                return [];
            }

            imagepalettetotruecolor($image);
            $image = self::applyExifOrientation($image, $bytes, $info[2]);

            $variants = [];

            foreach (self::VARIANT_WIDTHS as $name => $width) {
                if ($width >= imagesx($image)) {
                    continue;
                }

                $scaled = imagescale($image, $width, -1, IMG_BICUBIC);

                if (! $scaled) {
                    continue;
                }

                imagealphablending($scaled, false);
                imagesavealpha($scaled, true);

                ob_start();
                imagewebp($scaled, null, self::VARIANT_QUALITY);
                $webp = ob_get_clean();

                $variantPath = dirname($path).'/'.pathinfo($path, PATHINFO_FILENAME)."-{$width}w.webp";
                $storage->put($variantPath, $webp);

                $variants[$name] = ['path' => $variantPath, 'width' => $width, 'height' => imagesy($scaled), 'size' => strlen($webp)];
            }

            return $variants;
        } catch (Throwable $e) {
            Log::warning("Could not resize media [{$path}]: {$e->getMessage()}");

            return [];
        }
    }

    /**
     * Browsers show a JPEG upright using its EXIF orientation; the resized
     * copies carry none, so turn the pixels instead.
     */
    private static function applyExifOrientation(\GdImage $image, string $bytes, int $type): \GdImage
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = @exif_read_data('data://image/jpeg;base64,'.base64_encode($bytes))['Orientation'] ?? 1;

        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : (imagerotate($image, $angle, 0) ?: $image);
    }

    /**
     * Every stored file of this image: the original and its copies.
     *
     * @return array<int, string>
     */
    public function filePaths(): array
    {
        return [$this->path, ...array_column($this->variants ?? [], 'path')];
    }

    /**
     * The media id held by an image field's stored value, or null. Values
     * from before uploads existed (URL strings) count as no image.
     */
    public static function idFrom(mixed $value): ?int
    {
        return is_int($value) || (is_string($value) && ctype_digit($value)) ? (int) $value : null;
    }

    /**
     * Replace the named image fields of each content array with their media
     * payload (null when unset or not in this site's library), using a single
     * query for all of them. Array keys are preserved.
     *
     * @param  array<int|string, array{content: array<string, mixed>, images: array<int, string>}>  $items
     * @return array<int|string, array<string, mixed>>
     */
    public static function resolveImages(array $items): array
    {
        $ids = collect($items)
            ->flatMap(fn (array $item) => array_map(fn (string $name) => self::idFrom($item['content'][$name] ?? null), $item['images']))
            ->filter()
            ->unique()
            ->values();

        $media = $ids->isEmpty() ? collect() : self::whereIn('id', $ids)->get()->keyBy('id');

        return array_map(function (array $item) use ($media): array {
            $content = $item['content'];

            foreach ($item['images'] as $name) {
                $id = self::idFrom($content[$name] ?? null);
                $content[$name] = $id === null ? null : $media->get($id)?->toPayload();
            }

            return $content;
        }, $items);
    }

    /**
     * Absolute public URL of the stored file.
     */
    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * URL of a resized copy, or of the original when there is no such copy
     * (the image is narrower than it).
     */
    public function variantUrl(string $name): string
    {
        $variant = $this->variants[$name] ?? null;

        return $variant === null ? $this->url() : Storage::disk($this->disk)->url($variant['path']);
    }

    /**
     * The shape handed to frontends. `srcset` lists the resized copies and
     * the original, ready for an <img>; null when there are no copies.
     *
     * @return array{url: string, alt: string, width: int|null, height: int|null, variants: array<int, array{width: int, url: string}>, srcset: string|null}
     */
    public function toPayload(): array
    {
        $storage = Storage::disk($this->disk);

        $variants = collect($this->variants ?? [])
            ->map(fn (array $variant) => ['width' => $variant['width'], 'url' => $storage->url($variant['path'])])
            ->sortBy('width')
            ->values();

        return [
            'url' => $this->url(),
            'alt' => $this->alt,
            'width' => $this->width,
            'height' => $this->height,
            'variants' => $variants->all(),
            'srcset' => $variants->isEmpty() || $this->width === null
                ? null
                : $variants->map(fn (array $variant) => "{$variant['url']} {$variant['width']}w")->push("{$this->url()} {$this->width}w")->join(', '),
        ];
    }
}
