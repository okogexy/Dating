<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('birthday');
            $table->string('gender');
            $table->string('looking_for')->default('all');
            $table->string('city')->default('Москва');
            $table->string('goal')->default('Отношения');
            $table->text('bio')->nullable();
            $table->json('interests');
            $table->json('photos');
            $table->boolean('hidden')->default(false);
            $table->boolean('demo')->default(false);
            $table->timestamps();
        });
        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_id')->constrained('users')->cascadeOnDelete();
            $table->string('kind');
            $table->timestamps();
            $table->unique(['user_id', 'target_id']);
        });
        Schema::create('connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('first_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('second_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['first_id', 'second_id']);
        });
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['user_id', 'target_id']);
        });
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 1000);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['reports', 'blocks', 'messages', 'connections', 'reactions', 'profiles'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
