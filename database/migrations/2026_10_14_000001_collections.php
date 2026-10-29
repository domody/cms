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
        Schema::create('collection_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->unsignedInteger('version')->default(1);
            $table->jsonb('schema');
            $table->timestamps();

            $table->unique(['site_id', 'key']);
        });

        Schema::create('collection_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('collection_key');
            $table->string('slug');
            $table->string('title');
            $table->string('status')->default('draft');
            $table->unsignedInteger('position')->default(0);
            $table->jsonb('data');
            $table->timestamps();

            $table->unique(['site_id', 'collection_key', 'slug']);
            $table->index(['site_id', 'collection_key', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_entries');
        Schema::dropIfExists('collection_definitions');
    }
};
