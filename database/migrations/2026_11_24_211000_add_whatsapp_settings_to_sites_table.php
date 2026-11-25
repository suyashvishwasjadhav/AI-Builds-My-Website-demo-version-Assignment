<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table): void {
            $table->boolean('whatsapp_enabled')->default(true)->after('language');
            $table->string('whatsapp_greeting', 255)->nullable()->after('whatsapp_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table): void {
            $table->dropColumn(['whatsapp_enabled', 'whatsapp_greeting']);
        });
    }
};
