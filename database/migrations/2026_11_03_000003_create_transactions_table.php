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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->date('date_operation');
            $table->unsignedInteger('numero_operation')->default(1);
            $table->string('type_operation')->default('vente'); // vente, achat, remboursement_fcfa, remboursement_ouguiya, depot, autre
            $table->text('description')->nullable();
            
            // Ouguiya (MRU) amounts
            $table->decimal('ouguiya', 16, 2)->default(0.00)->nullable();
            $table->decimal('payement_ouguiya', 16, 2)->default(0.00)->nullable();
            $table->decimal('solde_ouguiya_ligne', 16, 2)->default(0.00);

            // FCFA (XOF) amounts
            $table->decimal('fcfa', 16, 2)->default(0.00)->nullable();
            $table->decimal('payement_fcfa', 16, 2)->default(0.00)->nullable();
            $table->decimal('solde_fcfa_ligne', 16, 2)->default(0.00);

            // Rate and Gains
            $table->decimal('rate', 16, 4)->nullable(); // e.g. FCFA / Ouguiya (e.g. 16.50)
            $table->decimal('taux_achat', 16, 4)->nullable(); // Base purchase rate for margin
            $table->decimal('gain', 16, 2)->default(0.00)->nullable(); // Profit generated

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['client_id', 'date_operation']);
            $table->index('date_operation');
            $table->index('type_operation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
