<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\XenditService;

class XenditCheckUnpaidBill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:xendit-check-unpaid-bill';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check unpaid Xendit bills and update their status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        XenditService::checkUnpaidBills();
    }
}
