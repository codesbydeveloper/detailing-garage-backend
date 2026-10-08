<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\User;
use App\Support\Query;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(private ActivityLogService $activity) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Attendance::query()->with('staff:id,full_name,staff_code');
        $this->restrict($query, $filters);
        Query::sort($query, $filters['sort'] ?? null, $filters['direction'] ?? null, ['id', 'date', 'status', 'staff_id', 'created_at'], 'date');

        return $query->paginate(Query::perPage($filters['per_page'] ?? null))->withQueryString();
    }

    public function mine(User $actor, array $filters): LengthAwarePaginator
    {
        $filters['staff_id'] = $actor->staff_id;

        return $this->paginate($filters);
    }

    public function today(): \Illuminate\Support\Collection
    {
        return Attendance::query()
            ->with('staff:id,full_name,staff_code')
            ->whereDate('date', now()->toDateString())
            ->orderBy('staff_id')
            ->get();
    }

    public function monthly(int $year, int $month, ?int $staffId = null): \Illuminate\Support\Collection
    {
        return Attendance::query()
            ->with('staff:id,full_name,staff_code')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->when($staffId, fn ($query) => $query->where('staff_id', $staffId))
            ->orderBy('date')
            ->get();
    }

    public function clockIn(User $actor, ?string $notes = null): Attendance
    {
        $staffId = $this->staffId($actor);

        return DB::transaction(function () use ($actor, $staffId, $notes) {
            $existing = Attendance::query()
                ->where('staff_id', $staffId)
                ->whereDate('date', now()->toDateString())
                ->lockForUpdate()
                ->first();

            if ($existing?->clock_in && ! $existing->clock_out) {
                throw ValidationException::withMessages([
                    'clock_in' => ['You are already clocked in.'],
                ]);
            }

            if ($existing?->clock_in && $existing->clock_out) {
                throw ValidationException::withMessages([
                    'clock_in' => ['Attendance for today is already completed.'],
                ]);
            }

            if ($existing) {
                $before = $this->activity->snapshot($existing);
                $existing->fill([
                    'clock_in' => now(),
                    'status' => AttendanceStatus::Present->value,
                    'notes' => $notes ?? $existing->notes,
                    'updated_by' => $actor->id,
                ])->save();
                $this->activity->log('CLOCK_IN', 'attendance', 'Staff clocked in', $existing, $before, $this->activity->snapshot($existing), 'success', $actor);

                return $existing->load('staff:id,full_name,staff_code');
            }

            $attendance = Attendance::query()->create([
                'staff_id' => $staffId,
                'date' => now()->toDateString(),
                'clock_in' => now(),
                'status' => AttendanceStatus::Present->value,
                'notes' => $notes,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->activity->log('CLOCK_IN', 'attendance', 'Staff clocked in', $attendance, null, $this->activity->snapshot($attendance), 'success', $actor);

            return $attendance->load('staff:id,full_name,staff_code');
        });
    }

    public function clockOut(User $actor, ?string $notes = null): Attendance
    {
        $staffId = $this->staffId($actor);

        return DB::transaction(function () use ($actor, $staffId, $notes) {
            $existing = Attendance::query()
                ->where('staff_id', $staffId)
                ->whereDate('date', now()->toDateString())
                ->lockForUpdate()
                ->first();

            if (! $existing || ! $existing->clock_in) {
                throw ValidationException::withMessages([
                    'clock_out' => ['No active clock-in was found.'],
                ]);
            }

            if ($existing->clock_out) {
                throw ValidationException::withMessages([
                    'clock_out' => ['You have already clocked out.'],
                ]);
            }

            $before = $this->activity->snapshot($existing);
            $existing->clock_out = now();
            $existing->total_minutes = $existing->minutesBetweenClocks();
            $existing->updated_by = $actor->id;
            if ($notes) {
                $existing->notes = $notes;
            }
            $existing->save();

            $this->activity->log('CLOCK_OUT', 'attendance', 'Staff clocked out', $existing, $before, $this->activity->snapshot($existing), 'success', $actor);

            return $existing->load('staff:id,full_name,staff_code');
        });
    }

    public function create(array $data, User $actor): Attendance
    {
        return DB::transaction(function () use ($data, $actor) {
            $exists = Attendance::query()
                ->where('staff_id', $data['staff_id'])
                ->whereDate('date', $data['date'])
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'date' => ['Attendance already exists for this staff member on that date.'],
                ]);
            }

            $attendance = new Attendance([
                'staff_id' => $data['staff_id'],
                'date' => $data['date'],
                'clock_in' => $data['clock_in'] ?? null,
                'clock_out' => $data['clock_out'] ?? null,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
            $attendance->total_minutes = $attendance->minutesBetweenClocks();
            $attendance->save();

            $this->activity->log('ATTENDANCE_CREATED', 'attendance', 'Attendance record created', $attendance, null, $this->activity->snapshot($attendance), 'success', $actor);

            return $attendance->load('staff:id,full_name,staff_code');
        });
    }

    public function update(Attendance $attendance, array $data, User $actor): Attendance
    {
        $attendance->fill([
            'staff_id' => $data['staff_id'] ?? $attendance->staff_id,
            'date' => $data['date'] ?? $attendance->date,
            'clock_in' => array_key_exists('clock_in', $data) ? $data['clock_in'] : $attendance->clock_in,
            'clock_out' => array_key_exists('clock_out', $data) ? $data['clock_out'] : $attendance->clock_out,
            'status' => $data['status'] ?? $attendance->status,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $attendance->notes,
            'updated_by' => $actor->id,
        ]);
        $attendance->total_minutes = $attendance->minutesBetweenClocks();
        $attendance->save();

        [$old, $new] = $this->activity->changed($attendance);
        $this->activity->log('ATTENDANCE_UPDATED', 'attendance', 'Attendance record updated', $attendance, $old, $new, 'success', $actor);

        return $attendance->load('staff:id,full_name,staff_code');
    }

    public function delete(Attendance $attendance, User $actor): void
    {
        $snapshot = $this->activity->snapshot($attendance);
        $attendance->delete();
        $this->activity->log('ATTENDANCE_DELETED', 'attendance', 'Attendance record deleted', $attendance, $snapshot, null, 'success', $actor);
    }

    public function markMissingAbsent(?string $date = null): int
    {
        $day = $date ? \Carbon\Carbon::parse($date)->toDateString() : now()->toDateString();
        $count = 0;

        \App\Models\Staff::query()->where('status', 'active')->orderBy('id')->each(function ($staff) use ($day, &$count) {
            $exists = Attendance::query()->where('staff_id', $staff->id)->whereDate('date', $day)->exists();

            if ($exists) {
                return;
            }

            Attendance::query()->create([
                'staff_id' => $staff->id,
                'date' => $day,
                'status' => AttendanceStatus::Absent->value,
                'notes' => 'Automatically marked absent',
            ]);
            $count++;
        });

        return $count;
    }

    public function summary(int $year, int $month, ?int $staffId = null): array
    {
        $rows = Attendance::query()
            ->with('staff:id,full_name')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->when($staffId, fn ($query) => $query->where('staff_id', $staffId))
            ->get()
            ->groupBy('staff_id');

        return $rows->map(function ($records) {
            $counts = $records->countBy('status');
            $minutes = (int) $records->sum('total_minutes');

            return [
                'staff_id' => (int) $records->first()->staff_id,
                'staff_name' => $records->first()->staff?->full_name,
                'present' => (int) ($counts['present'] ?? 0),
                'absent' => (int) ($counts['absent'] ?? 0),
                'leave' => (int) ($counts['leave'] ?? 0),
                'late' => (int) ($counts['late'] ?? 0),
                'total_hours' => round($minutes / 60, 2),
            ];
        })->values()->all();
    }

    private function staffId(User $actor): int
    {
        if (! $actor->staff_id) {
            throw ValidationException::withMessages([
                'staff' => ['No staff profile is linked to this account.'],
            ]);
        }

        return (int) $actor->staff_id;
    }

    private function restrict($query, array $filters): void
    {
        if (! empty($filters['staff_id'])) {
            $query->where('staff_id', $filters['staff_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        Query::whereDateRange($query, 'date', $filters['date_from'] ?? null, $filters['date_to'] ?? null);
    }
}
