<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('listings', static function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['state']);
            $table->dropIndex(['price']);
            $table->dropIndex(['county']);
            $table->dropIndex(['city']);
            $table->dropIndex(['county', 'state']);
            $table->index(['state', 'county', 'area']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('listings', static function (Blueprint $table) {
            $table->dropIndex(['state', 'county', 'area']);
            $table->index('status');
            $table->index('state');
            $table->index('price');
            $table->index('county');
            $table->index('city');
            $table->index(['county', 'state']);

        });
    }
}
