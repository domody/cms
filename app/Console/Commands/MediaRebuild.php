<?php

namespace App\Console\Commands;

use App\Models\CollectionEntry;
use App\Models\Media;
use App\Models\Page;
use App\Models\Site;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MediaRebuild extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cms:media-rebuild
        {--site= : Only this site (slug)}
        {--variants : Also recreate resized copies that already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill media hashes, sizes and resized copies, and rebuild where each image is used';

    public function handle(): int
    {
        $sites = Site::query()->when($this->option('site'), fn ($query, $slug) => $query->where('slug', $slug))->get();

        if ($sites->isEmpty()) {
            $this->error('No matching site.');

            return self::FAILURE;
        }

        foreach ($sites as $site) {
            $this->info("{$site->slug}");

            $this->rebuildFiles($site);
            $this->rebuildUsages($site);
        }

        return self::SUCCESS;
    }

    /**
     * Fill in what uploads made before the media library existed lack. Files
     * missing from storage are reported and left alone.
     */
    private function rebuildFiles(Site $site): void
    {
        $fixed = 0;
        $missing = 0;
        $hashes = [];

        Media::withoutGlobalScopes()->where('site_id', $site->id)->orderBy('id')->each(function (Media $media) use (&$fixed, &$missing, &$hashes): void {
            $storage = Storage::disk($media->disk);

            if (! $storage->exists($media->path)) {
                $missing++;
                $this->warn("  #{$media->id} {$media->original_name}: file is missing from storage");

                return;
            }

            $changes = [];

            if ($media->hash === null) {
                $changes['hash'] = hash('sha256', $storage->get($media->path));
            }

            if ($media->width === null && $info = @getimagesizefromstring($storage->get($media->path))) {
                $changes += ['width' => $info[0], 'height' => $info[1]];
            }

            if ($media->variants === null || $this->option('variants')) {
                $storage->delete(array_column($media->variants ?? [], 'path'));
                $variants = Media::makeVariants($media->disk, $media->path);
                $changes['variants'] = $variants ?: null;
                $changes['stored_bytes'] = $media->size + array_sum(array_column($variants, 'size'));
            }

            if ($changes !== []) {
                $media->forceFill($changes)->saveQuietly();
                $fixed++;
            }

            $hash = $changes['hash'] ?? $media->hash;

            if (isset($hashes[$hash])) {
                $this->warn("  #{$media->id} {$media->original_name} is identical to #{$hashes[$hash]}");
            }

            $hashes[$hash] ??= $media->id;
        });

        $this->line("  files: {$fixed} updated, {$missing} missing");
    }

    private function rebuildUsages(Site $site): void
    {
        $subjects = 0;

        Page::withoutGlobalScopes()->where('site_id', $site->id)->each(function (Page $page) use (&$subjects): void {
            $page->syncMediaUsage();
            $subjects++;
        });

        CollectionEntry::withoutGlobalScopes()->where('site_id', $site->id)->each(function (CollectionEntry $entry) use (&$subjects): void {
            $entry->syncMediaUsage();
            $subjects++;
        });

        $this->line("  usages: {$subjects} pages and entries scanned");
    }
}
