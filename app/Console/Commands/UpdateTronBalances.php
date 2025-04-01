<?php

namespace App\Console\Commands;

use App\Models\TronWallet;
use App\Services\TatumService;
use Illuminate\Console\Command;

class UpdateTronBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tron:update-balances {--address= : Specific wallet address to update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update TRON wallet balances using Tatum API';

    private TatumService $tatumService;

    public function __construct(TatumService $tatumService)
    {
        parent::__construct();
        $this->tatumService = $tatumService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $address = $this->option('address');

        if ($address) {
            $this->updateSingleWallet($address);
        } else {
            $this->updateAllWallets();
        }
    }

    private function updateSingleWallet(string $address): void
    {
        $wallet = TronWallet::where('address', $address)->first();

        if (!$wallet) {
            $this->error("Wallet with address {$address} not found");
            return;
        }

        $this->info("Updating balance for wallet {$address}...");
        
        $balance = $this->tatumService->getBalance($address);
        $usdtBalance = $this->tatumService->getUsdtBalance($address);

        $wallet->update([
            'balance' => $balance['balance'] ?? 0,
            'usdt_balance' => $usdtBalance,
            'last_balance_check' => now(),
        ]);

        $this->info('Balance updated successfully:');
        $this->line("TRX Balance: {$wallet->balance}");
        $this->line("USDT Balance: {$wallet->usdt_balance}");
    }

    private function updateAllWallets(): void
    {
        $wallets = TronWallet::where('is_active', true)->get();
        $count = $wallets->count();

        $this->info("Updating balances for {$count} wallets...");
        
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($wallets as $wallet) {
            $balance = $this->tatumService->getBalance($wallet->address);
            $usdtBalance = $this->tatumService->getUsdtBalance($wallet->address);

            $wallet->update([
                'balance' => $balance['balance'] ?? 0,
                'usdt_balance' => $usdtBalance,
                'last_balance_check' => now(),
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('All wallet balances have been updated successfully!');
    }
}
