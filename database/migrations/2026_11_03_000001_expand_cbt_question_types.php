<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cbt_questions', function (Blueprint $table) {
            $table->string('question_type', 50)->default('multiple_choice')->change();
        });
    }

    public function down(): void
    {
        Schema::table('cbt_questions', function (Blueprint $table) {
            $table->string('question_type', 50)->default('multiple_choice')->change();
        });
    }
};
