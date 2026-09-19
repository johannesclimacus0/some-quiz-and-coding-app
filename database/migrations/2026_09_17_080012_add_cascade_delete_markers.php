<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->boolean('deleted_by_parent')->default(false);
        });

        Schema::table('answers', function (Blueprint $table): void {
            $table->boolean('deleted_by_parent')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table): void {
            $table->dropColumn('deleted_by_parent');
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->dropColumn('deleted_by_parent');
        });
    }
};
