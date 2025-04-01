<?php

namespace App\Console\Commands;

use App\Services\TatumService;
use Illuminate\Console\Command;

class GenerateTronWallets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tron:generate-wallets {count=1 : Number of wallets to generate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate TRON wallets using Tatum API';

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
        $count = (int) $this->argument('count');
        
        if ($count < 1) {
            $this->error('Count must be greater than 0');
            return 1;
        }

        $this->info("Generating {$count} TRON wallets...");
        
        $wallets = $this->tatumService->generateWallets($count);
        
        $this->info('Generated wallets:');
        foreach ($wallets as $wallet) {
            $this->line("Address: {$wallet->address}");
            $this->line("Public Key: {$wallet->public_key}");
            $this->line("Base58 Address: {$wallet->base58_address}");
            $this->line('---');
        }

        $this->info('Wallets have been generated successfully!');
    }
}
