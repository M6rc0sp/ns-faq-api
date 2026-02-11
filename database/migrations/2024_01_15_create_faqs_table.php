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
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('store_id');
            $table->string('title');
            $table->boolean('active')->default(true);
            $table->boolean('show_on_homepage')->default(false);
            $table->timestamps();

            $table->index('store_id');
            $table->index('active');
            $table->index('show_on_homepage');
            $table->foreign('store_id')->references('store_id')->on('stores')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
