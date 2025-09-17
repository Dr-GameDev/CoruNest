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
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('ZAR');
            $table->enum('payment_provider', ['yoco', 'ozow'])->default('yoco');
            $table->string('transaction_id')->unique();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded', 'cancelled'])->default('pending');
            $table->json('metadata')->nullable(); // Payment provider specific data
            $table->string('donor_name')->nullable(); // For anonymous donations
            $table->string('donor_email')->nullable();
            $table->string('donor_phone')->nullable();
            $table->text('donor_message')->nullable();
            $table->boolean('anonymous')->default(false);
            $table->boolean('recurring')->default(false);
            $table->string('receipt_number')->unique()->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['status', 'completed_at']);
            $table->index(['campaign_id', 'status']);
            $table->index('payment_provider');
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
