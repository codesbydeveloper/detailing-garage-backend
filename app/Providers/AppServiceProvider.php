<?php

namespace App\Providers;

use App\Events\LeadConvertedToJob;
use App\Listeners\LogLeadConverted;
use App\Models\User;
use App\Observers\BlameableObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(function (User $user) {
            if (! $user->isActive()) {
                return false;
            }

            return $user->isOwner() ? true : null;
        });

        Event::listen(LeadConvertedToJob::class, LogLeadConverted::class);

        foreach ([
            \App\Models\Staff::class,
            \App\Models\StaffCategory::class,
            \App\Models\WorkType::class,
            \App\Models\LeadStatus::class,
            \App\Models\ExpenseCategory::class,
            \App\Models\Lead::class,
            \App\Models\Job::class,
            \App\Models\OfficeExpense::class,
            \App\Models\PersonalExpense::class,
            \App\Models\Vendor::class,
            \App\Models\VendorPayment::class,
            \App\Models\Attendance::class,
            \App\Models\SalaryRecord::class,
        ] as $model) {
            $model::observe(BlameableObserver::class);
        }
    }
}
