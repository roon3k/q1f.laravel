<?php

namespace App\Services;

use App\Models\TronWallet;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TronTransactionMonitor
{
    private $apiKey;
    private $baseUrl;
    private $usdtContractAddress;
    private $tatumService;

    public function __construct(TatumService $tatumService)
    {
        $this->apiKey = config('services.tatum.api_key');
        $this->baseUrl = config('services.tatum.base_url');
        $this->usdtContractAddress = config('services.tatum.usdt_contract_address');
        $this->tatumService = $tatumService;
    }

    /**
     * Monitor transactions for all derived wallets
     */
    public function monitorTransactions(): void
    {
        $wallets = TronWallet::where('is_master', false)
            ->where('is_active', true)
            ->get();

        foreach ($wallets as $wallet) {
            $this->processWalletTransactions($wallet);
        }
    }

    /**
     * Process transactions for a specific wallet
     */
    private function processWalletTransactions(TronWallet $wallet): void
    {
        try {
            // Get recent transactions
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/tron/transaction/account/' . $wallet->address);

            if (!$response->successful()) {
                Log::error('Failed to get transactions', [
                    'wallet' => $wallet->address,
                    'error' => $response->json()
                ]);
                return;
            }

            $transactions = $response->json();
            $masterWallet = TronWallet::where('is_master', true)->first();

            if (!$masterWallet) {
                Log::error('Master wallet not found');
                return;
            }

            foreach ($transactions as $transaction) {
                // Skip if transaction is already processed
                if ($this->isTransactionProcessed($transaction['txID'])) {
                    continue;
                }

                // Process TRX transactions
                if ($transaction['type'] === 'TRX') {
                    $this->transferTrxToMaster($wallet, $masterWallet);
                }

                // Process USDT transactions
                if ($transaction['type'] === 'TRC20' && $transaction['contractAddress'] === $this->usdtContractAddress) {
                    $this->transferUsdtToMaster($wallet, $masterWallet);
                }

                // Mark transaction as processed
                $this->markTransactionProcessed($transaction['txID']);
            }
        } catch (\Exception $e) {
            Log::error('Error processing wallet transactions', [
                'wallet' => $wallet->address,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Transfer TRX to master wallet
     */
    private function transferTrxToMaster(TronWallet $sourceWallet, TronWallet $masterWallet): void
    {
        try {
            $balance = $this->tatumService->getBalance($sourceWallet->address);
            
            if (empty($balance['balance']) || $balance['balance'] <= 0) {
                return;
            }

            // Leave some TRX for transaction fees
            $transferAmount = $balance['balance'] - 1;

            if ($transferAmount <= 0) {
                return;
            }

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/tron/transaction', [
                'from' => $sourceWallet->address,
                'to' => $masterWallet->address,
                'amount' => $transferAmount,
                'privateKey' => $sourceWallet->private_key,
            ]);

            if (!$response->successful()) {
                Log::error('Failed to transfer TRX to master wallet', [
                    'from' => $sourceWallet->address,
                    'to' => $masterWallet->address,
                    'error' => $response->json()
                ]);
                return;
            }

            Log::info('TRX transferred to master wallet', [
                'from' => $sourceWallet->address,
                'to' => $masterWallet->address,
                'amount' => $transferAmount
            ]);
        } catch (\Exception $e) {
            Log::error('Error transferring TRX to master wallet', [
                'from' => $sourceWallet->address,
                'to' => $masterWallet->address,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Transfer USDT to master wallet
     */
    private function transferUsdtToMaster(TronWallet $sourceWallet, TronWallet $masterWallet): void
    {
        try {
            $usdtBalance = $this->tatumService->getUsdtBalance($sourceWallet->address);
            
            if ($usdtBalance <= 0) {
                return;
            }

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/tron/trc20/transaction', [
                'from' => $sourceWallet->address,
                'to' => $masterWallet->address,
                'amount' => $usdtBalance,
                'contractAddress' => $this->usdtContractAddress,
                'privateKey' => $sourceWallet->private_key,
            ]);

            if (!$response->successful()) {
                Log::error('Failed to transfer USDT to master wallet', [
                    'from' => $sourceWallet->address,
                    'to' => $masterWallet->address,
                    'error' => $response->json()
                ]);
                return;
            }

            Log::info('USDT transferred to master wallet', [
                'from' => $sourceWallet->address,
                'to' => $masterWallet->address,
                'amount' => $usdtBalance
            ]);
        } catch (\Exception $e) {
            Log::error('Error transferring USDT to master wallet', [
                'from' => $sourceWallet->address,
                'to' => $masterWallet->address,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if transaction was already processed
     */
    private function isTransactionProcessed(string $txId): bool
    {
        return Cache::has('processed_transaction_' . $txId);
    }

    /**
     * Mark transaction as processed
     */
    private function markTransactionProcessed(string $txId): void
    {
        Cache::put('processed_transaction_' . $txId, true, now()->addDays(7));
    }
} 