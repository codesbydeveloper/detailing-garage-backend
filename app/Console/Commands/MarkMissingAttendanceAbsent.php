<?php

namespace App\Console\Commands;

use App\Services\AttendanceService;
use Illuminate\Console\Command;

class MarkMissingAttendanceAbsent extends Command
{
    protected $signature = 'attendance:mark-missing-absent {--date=}';

    protected $description = 'Mark active staff without an attendance row as absent';

    public function handle(AttendanceService $attendance): int
    {
        $count = $attendance->markMissingAbsent($this->option('date') ?: null);
        $this->info("Marked {$count} staff members absent.");

        return self::SUCCESS;
    }
}
