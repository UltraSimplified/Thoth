<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('arcana');
            $table->string('suit')->nullable();
            $table->string('rank')->nullable();
            $table->unsignedTinyInteger('number');
            $table->string('element')->nullable();
            $table->string('sub_element')->nullable();
            $table->string('hebrew_letter')->nullable();
            $table->string('planet')->nullable();
            $table->string('sign')->nullable();
            $table->unsignedTinyInteger('decan')->nullable();
            $table->string('zodiac_span_from')->nullable();
            $table->string('zodiac_span_to')->nullable();
            $table->string('quadrant')->nullable();
            $table->string('sephira')->nullable();
            $table->unsignedTinyInteger('tree_path')->nullable();
            $table->string('thothreadings_url');
            $table->text('personal_note')->nullable();
            $table->timestamps();

            $table->index(['arcana', 'suit', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
