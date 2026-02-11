<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_category_bindings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('faq_id');
            $table->string('category_id');
            $table->string('category_handle')->nullable();
            $table->timestamps();

            $table->foreign('faq_id')->references('id')->on('faqs')->onDelete('cascade');
            $table->unique(['faq_id', 'category_id']);
            $table->index('category_id');
            $table->index('category_handle');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_category_bindings');
    }
};
