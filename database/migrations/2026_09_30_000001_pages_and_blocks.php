<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('block_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->unsignedInteger('version')->default(1);
            $table->jsonb('schema');
            $table->timestamps();

            $table->unique(['tenant_id', 'key']);
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('page_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('block_key');
            $table->unsignedInteger('position');
            $table->boolean('is_visible')->default(true);
            $table->jsonb('content');
            $table->unsignedInteger('schema_version')->default(1);
            $table->timestamps();

            $table->index(['page_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_blocks');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('block_definitions');
    }
};
