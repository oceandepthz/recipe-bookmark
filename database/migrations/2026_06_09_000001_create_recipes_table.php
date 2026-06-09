<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('url', 2048)->unique();
            $table->string('domain')->index();
            $table->string('title');
            $table->string('site_name')->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content_html')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
