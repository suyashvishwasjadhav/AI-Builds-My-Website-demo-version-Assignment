<?php

use App\Enums\FactConfidence;
use App\Enums\FactStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('key');
            $table->text('value');
            $table->string('status')->default(FactStatus::Candidate->value);
            $table->string('confidence')->default(FactConfidence::Medium->value);
            $table->timestamps();

            $table->index(['site_id', 'key', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facts');
    }
};
