<?php

namespace App\Console\Commands;

use App\Jobs\GenerateDish;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('plated:plate {count=1 : How many dishes to plate} {--now : Plate synchronously for an instant reveal}')]
#[Description('Fire new AI-generated dishes onto the pass')]
class PlateDish extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = max(1, (int) $this->argument('count'));
        $now = (bool) $this->option('now');

        for ($i = 0; $i < $count; $i++) {
            $now
                ? GenerateDish::dispatchSync()
                : GenerateDish::dispatch();
        }

        $this->info($now
            ? "Plated {$count} dish(es) on the pass."
            : "Fired {$count} ticket(s) to the queue.");

        return self::SUCCESS;
    }
}
