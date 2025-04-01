<?php

namespace App\Console\Commands;

use App\Services\TronTransactionMonitor;
use Illuminate\Console\Command;

class MonitorTronTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tron:monitor {--interval=60 : Monitoring interval in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor TRON transactions and auto-transfer funds to master wallet';

    private TronTransactionMonitor $monitor;

    public function __construct(TronTransactionMonitor $monitor)
    {
        parent::__construct();
        $this->monitor = $monitor;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $interval = (int) $this->option('interval');
        
        if ($interval < 10) {
            $this->error('Interval must be at least 10 seconds');
            return 1;
        }

        $this->info("Starting TRON transaction monitor (interval: {$interval}s)");
        $this->info('Press Ctrl+C to stop');

        while (true) {
            try {
                $this->monitor->monitorTransactions();
                $this->info('Transaction check completed at ' . now());
                sleep($interval);
            } catch (\Exception $e) {
                $this->error('Error during monitoring: ' . $e->getMessage());
                sleep(5); // Wait 5 seconds before retrying
            }
        }
    }
}
