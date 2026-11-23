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
        // Where the site's frontend listens for "content changed" pings (see
        // Site::revalidateFrontend), and the shared secret it expects.
        Schema::table('sites', function (Blueprint $table) {
            $table->string('revalidate_url', 2048)->nullable()->after('plan');
            $table->text('revalidate_secret')->nullable()->after('revalidate_url');
        });

        // A disabled user can't log in and their API tokens stop working.
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('disabled_at')->nullable()->after('access_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('disabled_at');
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['revalidate_url', 'revalidate_secret']);
        });
    }
};
