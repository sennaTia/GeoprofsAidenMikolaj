<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateYearlyLeaveBalances extends Command
{
    protected $signature = 'leave:create-yearly-balances {year? : Het jaar, standaard het huidige jaar} {--days=25 : Aantal toe te kennen dagen}';

    protected $description = 'Maakt voor elke werknemer automatisch een nieuw verlofsaldo aan voor het opgegeven jaar';

    public function handle(): int
    {
        $year = (int) ($this->argument('year') ?? now()->year);
        $days = (float) $this->option('days');

        $count = 0;

        User::query()->each(function (User $user) use ($year, $days, &$count) {
            $created = $user->leaveBalances()->firstOrCreate(
                ['year' => $year],
                ['total_days' => $days]
            );

            if ($created->wasRecentlyCreated) {
                $count++;
            }
        });

        $this->info("Klaar: {$count} nieuwe verlofsaldo's aangemaakt voor {$year}.");

        return self::SUCCESS;
    }
}