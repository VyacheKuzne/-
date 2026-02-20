<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();

            $table->string('clientName', 18);
            $table->string('phone', 12);
            $table->string('address', 40);
            $table->string('problemText', 255);

            $table->enum('status', [
                'new',
                'assigned',
                'in_progress',
                'done',
                'canceled'
            ])->default('new');

            // внешний ключ на users (мастер)
            $table->foreignId('assignedTo')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps(); // created_at, updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
