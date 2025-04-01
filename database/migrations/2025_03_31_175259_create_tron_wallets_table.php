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
        Schema::create('tron_wallets', function (Blueprint $table) {
            $table->id();
            $table->string('address')->unique();
            $table->string('private_key');
            $table->string('public_key');
            $table->string('hex_address');
            $table->string('base58_address');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_master')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('balance', 18, 6)->default(0);
            $table->decimal('usdt_balance', 18, 6)->default(0);
            $table->timestamp('last_balance_check')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tron_wallets');
    }
};
