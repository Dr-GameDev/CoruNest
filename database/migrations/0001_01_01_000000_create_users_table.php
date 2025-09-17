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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->string('phone')->nullable()->after('email');
            $table->enum('role', ['donor', 'volunteer', 'admin', 'staff'])->default('donor')->after('phone');
            $table->json('profile')->nullable()->after('role'); // Additional profile data
            $table->timestamp('last_donation_at')->nullable()->after('profile');
            $table->timestamp('last_volunteer_at')->nullable()->after('last_donation_at');
            $table->boolean('email_notifications')->default(true)->after('last_volunteer_at');
            $table->boolean('sms_notifications')->default(false)->after('email_notifications');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'role',
                'profile',
                'last_donation_at',
                'last_volunteer_at',
                'email_notifications',
                'sms_notifications'
            ]);
        });
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
