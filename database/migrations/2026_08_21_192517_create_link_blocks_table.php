<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_blocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('url', 2048);
            $table->string('title');
            $table->string('image')->nullable();

            $table->unsignedInteger('position');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_blocks');
    }
};
