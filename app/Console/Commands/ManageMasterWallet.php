<?php

namespace App\Console\Commands;

use App\Models\TronWallet;
use App\Services\TatumService;
use Illuminate\Console\Command;

class ManageMasterWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tron:master-wallet {action=status : Action to perform (create|generate|status)} {--count=1000 : Number of wallets to generate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage TRON master wallet and generate derived wallets';

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
        $action = $this->argument('action');

        switch ($action) {
            case 'create':
                $this->createMasterWallet();
                break;
            case 'generate':
                $this->generateDerivedWallets();
                break;
            case 'status':
                $this->showStatus();
                break;
            default:
                $this->error('Invalid action. Use: create, generate, or status');
                return 1;
        }
    }

    private function createMasterWallet(): void
    {
        $existingMaster = TronWallet::where('is_master', true)->first();

        if ($existingMaster) {
            $this->error('Master wallet already exists!');
            $this->showWalletInfo($existingMaster);
            return;
        }

        $this->info('Creating master wallet...');
        $masterWallet = $this->tatumService->createMasterWallet();

        if ($masterWallet) {
            $this->info('Master wallet created successfully!');
            $this->showWalletInfo($masterWallet);
        } else {
            $this->error('Failed to create master wallet');
        }
    }

    private function generateDerivedWallets(): void
    {
        $masterWallet = TronWallet::where('is_master', true)->first();

        if (!$masterWallet) {
            $this->error('Master wallet not found. Please create it first using: tron:master-wallet create');
            return;
        }

        $count = (int) $this->option('count');
        
        if ($count < 1) {
            $this->error('Count must be greater than 0');
            return;
        }

        $this->info("Generating {$count} derived wallets...");
        
        $wallets = $this->tatumService->generateDerivedWallets($count);
        
        $this->info('Generated wallets:');
        foreach ($wallets as $wallet) {
            $this->line("Address: {$wallet->address}");
            $this->line("Base58 Address: {$wallet->base58_address}");
            $this->line('---');
        }

        $this->info('Wallets have been generated successfully!');
    }

    private function showStatus(): void
    {
        $masterWallet = TronWallet::where('is_master', true)->first();
        $totalWallets = TronWallet::count();
        $availableWallets = TronWallet::where('is_active', true)
            ->where('is_master', false)
            ->whereNull('user_id')
            ->count();
        $assignedWallets = TronWallet::whereNotNull('user_id')->count();

        $this->info('TRON Wallets Status:');
        $this->line("Total Wallets: {$totalWallets}");
        $this->line("Available Wallets: {$availableWallets}");
        $this->line("Assigned Wallets: {$assignedWallets}");

        if ($masterWallet) {
            $this->newLine();
            $this->info('Master Wallet:');
            $this->showWalletInfo($masterWallet);
        } else {
            $this->newLine();
            $this->warn('No master wallet found. Create one using: tron:master-wallet create');
        }
    }

    private function showWalletInfo(TronWallet $wallet): void
    {
        $this->line("Address: {$wallet->address}");
        $this->line("Public Key: {$wallet->public_key}");
        $this->line("Base58 Address: {$wallet->base58_address}");
        $this->line("Balance: {$wallet->balance} TRX");
        $this->line("USDT Balance: {$wallet->usdt_balance} USDT");
        $this->line("Last Balance Check: {$wallet->last_balance_check}");
        $this->line("Status: " . ($wallet->is_active ? 'Active' : 'Inactive'));
    }
}
