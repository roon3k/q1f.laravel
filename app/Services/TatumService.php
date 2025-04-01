<?php

namespace App\Services;

use App\Models\TronWallet;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TatumService
{
    private $apiKey;
    private $baseUrl;
    private $usdtContractAddress;

    public function __construct()
    {
        $this->apiKey = config('services.tatum.api_key');
        $this->baseUrl = config('services.tatum.base_url');
        $this->usdtContractAddress = config('services.tatum.usdt_contract_address');
    }

    /**
     * Create master wallet
     *
     * @return TronWallet|null
     */
    public function createMasterWallet(): ?TronWallet
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/tron/wallet');

            if ($response->successful()) {
                $walletData = $response->json();
                
                return TronWallet::create([
                    'address' => $walletData['address'],
                    'private_key' => $walletData['privateKey'],
                    'public_key' => $walletData['publicKey'],
                    'hex_address' => $walletData['hexAddress'],
                    'base58_address' => $walletData['base58Address'],
                    'is_master' => true,
                ]);
            }

            Log::error('Failed to create master wallet', [
                'error' => $response->json(),
                'status' => $response->status()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception while creating master wallet', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * Generate multiple derived wallets from master wallet
     *
     * @param int $count Number of wallets to generate
     * @return array Array of generated wallets
     */
    public function generateDerivedWallets(int $count = 1): array
    {
        $masterWallet = TronWallet::where('is_master', true)->first();
        
        if (!$masterWallet) {
            Log::error('Master wallet not found');
            return [];
        }

        $wallets = [];
        
        for ($i = 0; $i < $count; $i++) {
            try {
                $response = Http::withHeaders([
                    'x-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post($this->baseUrl . '/tron/wallet/derived', [
                    'masterWallet' => $masterWallet->address,
                    'index' => $i,
                ]);

                if ($response->successful()) {
                    $walletData = $response->json();
                    
                    $wallet = TronWallet::create([
                        'address' => $walletData['address'],
                        'private_key' => $walletData['privateKey'],
                        'public_key' => $walletData['publicKey'],
                        'hex_address' => $walletData['hexAddress'],
                        'base58_address' => $walletData['base58Address'],
                        'is_master' => false,
                    ]);

                    $wallets[] = $wallet;
                } else {
                    Log::error('Failed to generate derived wallet', [
                        'error' => $response->json(),
                        'status' => $response->status()
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Exception while generating derived wallet', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        return $wallets;
    }

    /**
     * Assign wallet to user
     *
     * @param TronWallet $wallet
     * @param User $user
     * @return bool
     */
    public function assignWalletToUser(TronWallet $wallet, User $user): bool
    {
        if ($wallet->isAssigned()) {
            Log::error('Wallet is already assigned to user', [
                'wallet_address' => $wallet->address,
                'user_id' => $user->id
            ]);
            return false;
        }

        $wallet->update(['user_id' => $user->id]);
        return true;
    }

    /**
     * Get available wallet for user
     *
     * @return TronWallet|null
     */
    public function getAvailableWallet(): ?TronWallet
    {
        return TronWallet::where('is_active', true)
            ->where('is_master', false)
            ->whereNull('user_id')
            ->first();
    }

    /**
     * Get wallet balance
     *
     * @param string $address TRON wallet address
     * @return array Balance information
     */
    public function getBalance(string $address): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/tron/account/balance/' . $address);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to get TRON wallet balance', [
                'address' => $address,
                'error' => $response->json(),
                'status' => $response->status()
            ]);

            return [
                'balance' => 0,
                'usdt_balance' => 0
            ];
        } catch (\Exception $e) {
            Log::error('Exception while getting TRON wallet balance', [
                'address' => $address,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'balance' => 0,
                'usdt_balance' => 0
            ];
        }
    }

    /**
     * Get USDT balance for a wallet
     *
     * @param string $address TRON wallet address
     * @return float USDT balance
     */
    public function getUsdtBalance(string $address): float
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/tron/trc20/balance/' . $this->usdtContractAddress . '/' . $address);

            if ($response->successful()) {
                $data = $response->json();
                return $data['balance'] ?? 0;
            }

            Log::error('Failed to get USDT balance', [
                'address' => $address,
                'error' => $response->json(),
                'status' => $response->status()
            ]);

            return 0;
        } catch (\Exception $e) {
            Log::error('Exception while getting USDT balance', [
                'address' => $address,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 0;
        }
    }

    /**
     * Update all wallets balances
     */
    public function updateAllBalances(): void
    {
        $wallets = TronWallet::where('is_active', true)->get();

        foreach ($wallets as $wallet) {
            $balance = $this->getBalance($wallet->address);
            $usdtBalance = $this->getUsdtBalance($wallet->address);

            $wallet->update([
                'balance' => $balance['balance'] ?? 0,
                'usdt_balance' => $usdtBalance,
                'last_balance_check' => now(),
            ]);
        }
    }
} 