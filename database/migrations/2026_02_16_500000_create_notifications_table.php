<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_change_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained();
            $table->string('team');
            $table->enum('strength', ['high', 'medium', 'low']);
            $table->text('message');
            $table->enum('status', ['unread', 'read', 'actioned'])->default('unread');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_notifications');
    }
};