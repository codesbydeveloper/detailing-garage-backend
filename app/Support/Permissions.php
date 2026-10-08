<?php

namespace App\Support;

final class Permissions
{
    public const ALL = [
        'dashboard.view',
        'leads.view',
        'leads.create',
        'leads.update',
        'leads.delete',
        'jobs.view',
        'jobs.create',
        'jobs.update',
        'jobs.delete',
        'expenses.view',
        'expenses.create',
        'expenses.update',
        'expenses.delete',
        'vendors.view',
        'vendors.create',
        'vendors.update',
        'vendors.delete',
        'staff.view',
        'staff.create',
        'staff.update',
        'staff.delete',
        'attendance.view',
        'attendance.manage',
        'attendance.my',
        'salary.view',
        'salary.manage',
        'salary.my',
        'extra_pay.view',
        'extra_pay.manage',
        'reports.view',
        'activity_logs.view',
        'settings.view',
        'settings.manage',
        'profile.view',
        'profile.update',
    ];

    public const STAFF = [
        'attendance.my',
        'salary.my',
        'profile.view',
        'profile.update',
    ];

    /**
     * Frontend names that resolve to a stored permission.
     *
     * @var array<string, string>
     */
    public const ALIASES = [
        'leads.edit' => 'leads.update',
        'jobs.edit' => 'jobs.update',
        'attendance.own' => 'attendance.my',
        'salary.own' => 'salary.my',
        'extra_pay.create' => 'extra_pay.manage',
        'finance.view' => 'reports.view',
        'vendor_payments.view' => 'vendors.view',
        'vendor_payments.create' => 'vendors.create',
        'vendor_payments.update' => 'vendors.update',
        'vendor_payments.delete' => 'vendors.delete',
        'expenses.office.view' => 'expenses.view',
        'expenses.office.create' => 'expenses.create',
        'expenses.office.update' => 'expenses.update',
        'expenses.office.delete' => 'expenses.delete',
        'expenses.personal.view' => 'expenses.view',
        'expenses.personal.create' => 'expenses.create',
        'expenses.personal.update' => 'expenses.update',
        'expenses.personal.delete' => 'expenses.delete',
    ];

    /**
     * @param  list<string>  $granted
     * @return list<string>
     */
    public static function expand(array $granted): array
    {
        $lookup = array_fill_keys($granted, true);

        foreach (self::ALIASES as $alias => $source) {
            if (! empty($lookup[$source])) {
                $granted[] = $alias;
            }
        }

        return array_values(array_unique($granted));
    }

    /**
     * @param  list<string>  $granted
     * @return array<string, bool>
     */
    public static function matrix(array $granted): array
    {
        $enabled = array_fill_keys(self::expand($granted), true);
        $flags = [];

        foreach (array_merge(self::ALL, array_keys(self::ALIASES)) as $name) {
            $flags[$name] = isset($enabled[$name]);
        }

        return $flags;
    }

    public static function canonical(string $name): ?string
    {
        if (in_array($name, self::ALL, true)) {
            return $name;
        }

        return self::ALIASES[$name] ?? null;
    }

    /**
     * Operational access plus financial permissions. Settings changes stay with the owner.
     *
     * @return list<string>
     */
    public static function manager(): array
    {
        return array_values(array_filter(
            self::ALL,
            fn (string $permission) => $permission !== 'settings.manage'
        ));
    }
}
