<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('event', 32)->index();
            $table->string('email', 254)->nullable()->index();
            // Identitas pengguna berasal dari API, bukan tabel users Laravel.
            $table->unsignedBigInteger('api_user_id')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
