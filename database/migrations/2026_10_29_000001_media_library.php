<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Media library: folders, a content hash (duplicate detection), resized
     * variants, the bytes each file occupies (plan quota), and where each
     * image is used. Existing rows are filled in by `php artisan cms:media-rebuild`.
     */
    public function up(): void
    {
        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->index(['site_id', 'parent_id']);
        });

        Schema::table('media', function (Blueprint $table) {
            $table->foreignId('folder_id')->nullable()->after('site_id')->constrained('media_folders')->nullOnDelete();
            $table->string('hash', 64)->nullable()->after('size');
            $table->json('variants')->nullable()->after('height');
            $table->unsignedBigInteger('stored_bytes')->default(0)->after('variants');

            $table->index(['site_id', 'folder_id']);
            $table->index(['site_id', 'hash']);
        });

        DB::table('media')->update(['stored_bytes' => DB::raw('size')]);

        // Which pages and collection entries hold each image. subject_id is
        // not a foreign key (it points at pages or entries), so the models
        // that own the content keep these rows in step.
        Schema::create('media_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('subject_type', 32);
            $table->unsignedBigInteger('subject_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['media_id', 'subject_type', 'subject_id']);
            $table->index(['site_id', 'subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_usages');

        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex(['site_id', 'folder_id']);
            $table->dropIndex(['site_id', 'hash']);
            $table->dropConstrainedForeignId('folder_id');
            $table->dropColumn(['hash', 'variants', 'stored_bytes']);
        });

        Schema::dropIfExists('media_folders');
    }
};
