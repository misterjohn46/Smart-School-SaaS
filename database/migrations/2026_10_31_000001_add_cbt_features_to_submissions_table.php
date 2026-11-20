<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cbt_submissions', function (Blueprint $table) {
            $table->unsignedInteger('violations_count')->default(0)->after('status');
            $table->json('doubtful_answers')->nullable()->after('answers');
            $table->json('essay_scores')->nullable()->after('total_score');
            $table->text('teacher_notes')->nullable()->after('essay_scores');
        });
    }

    public function down(): void
    {
        Schema::table('cbt_submissions', function (Blueprint $table) {
            $table->dropColumn(['violations_count', 'doubtful_answers', 'essay_scores', 'teacher_notes']);
        });
    }
};
