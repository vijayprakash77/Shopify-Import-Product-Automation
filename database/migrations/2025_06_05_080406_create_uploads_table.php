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
        Schema::create('uploads', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->nullable();
            $table->string('file_path')->nullable();
            $table->string('total_rows')->nullable();
            $table->string('processed_rows')->nullable();
            $table->string('successful_rows')->nullable();
            $table->string('failed_rows')->nullable();
            $table->string('status')->nullable();
            $table->string('errors')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploads');
    }
};
