<?php

namespace App\Console\Commands;

use App\Jobs\GenerateDish;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('plated:rush {count=20 : How many tickets to flood the pass with}')]
#[Description('Flood the queue with tickets to watch the workers chew through them')]
class RushPass extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = max(1, (int) $this->argument('count'));

        for ($i = 0; $i < $count; $i++) {
            GenerateDish::dispatch();
        }

        $this->info("Rushed {$count} tickets onto the queue. Watch the workers fire.");

        return self::SUCCESS;
    }
}
