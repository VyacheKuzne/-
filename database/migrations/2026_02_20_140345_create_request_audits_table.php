<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // database/migrations/xxxx_xx_xx_create_request_audits_table.php
public function up()
{
    Schema::create('request_audits', function (Blueprint $table) {
        $table->id();
        $table->foreignId('request_id')->constrained('requests')->onDelete('cascade');
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->string('action'); // take, complete, cancel, assign
        $table->string('old_status')->nullable();
        $table->string('new_status')->nullable();
        $table->timestamps();
    });
}

    public function down()
    {
        Schema::dropIfExists('request_audits');
    }
};