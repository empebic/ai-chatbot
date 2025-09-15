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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->enum('visibility', ['public', 'subscribers', 'paid'])->default('subscribers')->index();
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('comments_enabled')->default(true);
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
