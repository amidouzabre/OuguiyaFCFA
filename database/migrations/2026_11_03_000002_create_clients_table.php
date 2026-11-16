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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('telephone')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('solde_ouguiya', 16, 2)->default(0.00);
            $table->decimal('solde_fcfa', 16, 2)->default(0.00);
            $table->timestamps();

            $table->index('name');
            $table->index('telephone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
