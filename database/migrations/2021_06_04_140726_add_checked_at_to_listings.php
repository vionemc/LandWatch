<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCheckedAtToListings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->timestamp('checked_at')->nullable()->after('updated_at');
        });

        DB::table('listings')->update(['checked_at' => DB::raw('`updated_at`')]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('checked_at');
        });
    }
}
