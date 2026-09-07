<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('link_blocks', function (Blueprint $table) {
            $table->foreignId('link_block_group_id')
                ->nullable()
                ->after('user_id')
                ->constrained('link_block_groups')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('link_blocks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('link_block_group_id');
        });
    }
};
