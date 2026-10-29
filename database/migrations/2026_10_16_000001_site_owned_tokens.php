<?php

use App\Models\Site;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * API tokens used to be owned by the user who created them; they are now
     * owned by the site they work in. Existing site tokens are re-pointed at
     * their site, so they keep working; tokens with no site never worked.
     */
    public function up(): void
    {
        DB::table('personal_access_tokens')->whereNull('site_id')->delete();

        DB::table('personal_access_tokens')
            ->whereNotNull('site_id')
            ->update([
                'tokenable_type' => Site::class,
                'tokenable_id' => DB::raw('site_id'),
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * Not reversible: the user who created each token is no longer recorded.
     */
    public function down(): void
    {
        DB::table('personal_access_tokens')->where('tokenable_type', Site::class)->delete();
    }
};
