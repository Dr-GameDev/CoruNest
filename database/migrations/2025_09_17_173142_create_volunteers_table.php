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
        Schema::create('volunteers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'])->default('pending');
            $table->text('notes')->nullable();
            $table->string('volunteer_name')->nullable(); // For non-registered volunteers
            $table->string('volunteer_email')->nullable();
            $table->string('volunteer_phone')->nullable();
            $table->json('skills')->nullable(); // What skills they bring
            $table->json('availability')->nullable(); // Time slots they can help
            $table->text('message')->nullable(); // Personal message/motivation
            $table->boolean('has_transport')->default(false);
            $table->boolean('emergency_contact_provided')->default(false);
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Prevent duplicate signups
            $table->unique(['user_id', 'event_id']);
            
            // Indexes
            $table->index(['event_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteers');
    }
};
