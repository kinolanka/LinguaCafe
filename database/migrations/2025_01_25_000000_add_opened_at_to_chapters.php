<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->timestamp('opened_at')->nullable()->after('updated_at');
            $table->index(['user_id', 'language', 'opened_at'], 'chapters_recent_index');
        });
    }

    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->dropIndex('chapters_recent_index');
            $table->dropColumn('opened_at');
        });
    }
};
