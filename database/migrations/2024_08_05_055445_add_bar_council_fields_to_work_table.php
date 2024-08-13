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
        Schema::table('work', function (Blueprint $table) {
            $table->string('bar_council_no')->after('title');
            $table->string('bar_council_id')->nullable()->after('bar_council_no');
            $table->string('specialization')->nullable()->after('bar_council_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work', function (Blueprint $table) {
            $table->dropColumn('bar_council_no');
            $table->dropColumn('bar_council_id');
            $table->dropColumn('specialization');
        });
    }
};
