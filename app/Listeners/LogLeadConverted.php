<?php

namespace App\Listeners;

use App\Events\LeadConvertedToJob;

class LogLeadConverted
{
    public function handle(LeadConvertedToJob $event): void
    {
        // Conversion is audited inside the database transaction.
        // This listener exists so other modules can react after a lead becomes a job.
    }
}
