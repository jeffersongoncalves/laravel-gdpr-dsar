<?php

namespace JeffersonGoncalves\GdprDsar\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\GdprDsar\Events\DataSubjectRequestOverdue;
use JeffersonGoncalves\GdprDsar\Models\DataSubjectRequest;

class OverdueCommand extends Command
{
    protected $signature = 'gdpr:dsar-overdue';

    protected $description = 'Dispatch DataSubjectRequestOverdue for every open request past its legal deadline';

    public function handle(): int
    {
        $count = 0;

        DataSubjectRequest::query()->overdue()->lazyById()->each(function (DataSubjectRequest $request) use (&$count): void {
            event(new DataSubjectRequestOverdue($request));
            $count++;
        });

        $count === 0
            ? $this->info('No overdue data subject requests.')
            : $this->warn("{$count} overdue data subject request(s).");

        return self::SUCCESS;
    }
}
