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
        Schema::create('post_reports', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('reported_post_id');
            $table->foreign('reported_post_id')->references('id')->on('post')->onDelete('cascade');
         
            $table->unsignedBigInteger('reporting_user_id');
            $table->foreign('reporting_user_id')->references('id')->on('users')->onDelete('cascade');

            $table->text('post_report_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_reports');
    }
};
