<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table): void {
            $table->json('theme_config')->nullable()->after('template');
            $table->string('custom_domain')->nullable()->unique()->after('slug');
            $table->timestamp('domain_verified_at')->nullable()->after('custom_domain');
        });

        Schema::table('enquiries', function (Blueprint $table): void {
            $table->string('status', 32)->default('new')->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table): void {
            $table->dropColumn(['theme_config', 'custom_domain', 'domain_verified_at']);
        });

        Schema::table('enquiries', function (Blueprint $table): void {
            $table->dropColumn('status');
        });
    }
};
