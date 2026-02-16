<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('doc_type');
            $table->string('file_path')->nullable();
            $table->string('version', 32)->default('1.0');
            $table->enum('status', ['draft', 'active', 'archived'])->default('active');
            $table->timestamps();
        });

        Schema::create('component_document', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['component_id', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_document');
        Schema::dropIfExists('documents');
    }
};