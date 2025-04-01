<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TronWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'address',
        'private_key',
        'public_key',
        'hex_address',
        'base58_address',
        'is_active',
        'is_master',
        'user_id',
        'balance',
        'usdt_balance',
        'last_balance_check',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_master' => 'boolean',
        'balance' => 'decimal:6',
        'usdt_balance' => 'decimal:6',
        'last_balance_check' => 'datetime',
    ];

    protected $hidden = [
        'private_key',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isMaster(): bool
    {
        return $this->is_master;
    }

    public function isAssigned(): bool
    {
        return $this->user_id !== null;
    }
}
