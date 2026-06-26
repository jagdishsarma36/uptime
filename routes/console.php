<?php

use App\Jobs\CleanupOldLogs;
use App\Jobs\DispatchCheckJobs;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new DispatchCheckJobs)->everyMinute()->withoutOverlapping();

Schedule::job(new CleanupOldLogs)->daily()->withoutOverlapping();

Schedule::command('queue:prune-batches --finished --hours=48')->daily();
Schedule::command('queue:prune-failed-jobs --hours=48')->daily();
