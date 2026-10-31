<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('theory_questions', function (Blueprint $table) {
            $table->string('image_path', 2048)->nullable()->after('media_path');
            $table->string('video_path', 2048)->nullable()->after('image_path');
            $table->string('audio_path', 2048)->nullable()->after('video_path');
        });
    }

    public function down(): void
    {
        Schema::table('theory_questions', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'video_path', 'audio_path']);
        });
    }
};
