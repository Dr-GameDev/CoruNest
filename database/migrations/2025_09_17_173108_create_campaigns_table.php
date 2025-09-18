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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary');
            $table->longText('body');
            $table->decimal('target_amount', 12, 2)->default(0);
            $table->decimal('current_amount', 12, 2)->default(0);
            $table->enum('goal_type', ['currency', 'item'])->default('currency');
            $table->enum('status', ['draft', 'active', 'completed', 'archived'])->default('draft');
            $table->datetime('start_at')->nullable();
            $table->datetime('end_at')->nullable();
            $table->boolean('featured')->default(false);
            $table->string('image_path')->nullable();
            $table->json('images')->nullable(); // Multiple images
            $table->json('metadata')->nullable(); // Additional campaign data
            $table->string('category')->nullable();
            $table->integer('donor_count')->default(0);
            $table->decimal('average_donation', 8, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for performance
            $table->index(['status', 'featured']);
            $table->index(['start_at', 'end_at']);
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
