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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('domain')->index();
            $table->string('title');
            $table->string('site_name')->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content_html')->nullable();
            $table->timestamps();

            // 同じURLでも別ユーザなら登録可（ユーザ単位の一意制約）
            $table->unique(['user_id', 'url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
