<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_changes', function (Blueprint $table) {
            $table->id();
            $table->string('change_code', 32)->unique();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('author');
            $table->enum('status', ['pending', 'reviewing', 'approved', 'rejected'])->default('pending');
            $table->text('ai_summary')->nullable();
            $table->json('ai_raw_response')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('change_impacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_change_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained();
            $table->enum('strength', ['high', 'medium', 'low']);
            $table->string('relation');
            $table->integer('depth')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_impacts');
        Schema::dropIfExists('design_changes');
    }
};