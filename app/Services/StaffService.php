<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\User;
use App\Support\Money;
use App\Support\Query;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffService
{
    public function __construct(
        private ActivityLogService $activity,
        private NumberGenerator $numbers,
    ) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Staff::query()->with(['category:id,name', 'user:id,name,email,role,status']);
        $like = Query::like($filters['search'] ?? null);

        if ($like) {
            $query->where(function ($inner) use ($like) {
                $inner->where('full_name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('staff_code', 'like', $like);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['staff_category_id'])) {
            $query->where('staff_category_id', $filters['staff_category_id']);
        }

        Query::sort($query, $filters['sort'] ?? null, $filters['direction'] ?? null, ['id', 'full_name', 'joining_date', 'status', 'staff_code'], 'full_name');

        return $query->paginate(Query::perPage($filters['per_page'] ?? null))->withQueryString();
    }

    public function create(array $data, User $actor): Staff
    {
        return DB::transaction(function () use ($data, $actor) {
            $staff = Staff::query()->create([
                'staff_code' => $this->numbers->sequence(Staff::class, 'staff_code', 'STF'),
                'user_id' => $data['user_id'] ?? null,
                'full_name' => $data['full_name'],
                'profile_photo' => $data['profile_photo'] ?? null,
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'joining_date' => $data['joining_date'],
                'emergency_contact' => $data['emergency_contact'] ?? null,
                'emergency_contact_number' => $data['emergency_contact_number'] ?? null,
                'staff_category_id' => $data['staff_category_id'],
                'role' => $data['role'],
                'department' => $data['department'] ?? null,
                'salary_type' => $data['salary_type'],
                'basic_salary' => Money::of($data['basic_salary']),
                'status' => $data['status'],
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            if (! empty($data['create_login'])) {
                $user = User::query()->create([
                    'name' => $staff->full_name,
                    'email' => $data['login_email'],
                    'password' => $data['login_password'],
                    'role' => $data['login_role'],
                    'status' => 'active',
                    'staff_id' => $staff->id,
                ]);
                $staff->update(['user_id' => $user->id]);
            } elseif (! empty($data['user_id'])) {
                User::query()->whereKey($data['user_id'])->update(['staff_id' => $staff->id]);
            }

            $staff->load(['category', 'user']);
            $this->activity->log('STAFF_CREATED', 'staff', 'Staff member created', $staff, null, $this->activity->snapshot($staff), 'success', $actor);

            return $staff;
        });
    }

    public function update(Staff $staff, array $data, User $actor): Staff
    {
        $payload = collect($data)->only([
            'full_name', 'profile_photo', 'phone', 'email', 'address', 'date_of_birth', 'joining_date',
            'emergency_contact', 'emergency_contact_number', 'staff_category_id', 'role', 'department',
            'salary_type', 'basic_salary', 'status', 'user_id',
        ])->all();

        if (array_key_exists('basic_salary', $payload)) {
            $payload['basic_salary'] = Money::of($payload['basic_salary']);
        }

        $payload['updated_by'] = $actor->id;
        $staff->fill($payload);
        $staff->save();

        if ($staff->user_id) {
            User::query()->whereKey($staff->user_id)->update(['staff_id' => $staff->id]);
        }

        [$old, $new] = $this->activity->changed($staff);
        $this->activity->log('STAFF_UPDATED', 'staff', 'Staff member updated', $staff, $old, $new, 'success', $actor);

        return $staff->load(['category', 'user']);
    }

    public function delete(Staff $staff, User $actor): void
    {
        if ($staff->user && $staff->user->isOwner()) {
            throw ValidationException::withMessages([
                'staff' => ['The owner staff profile cannot be deleted.'],
            ]);
        }

        $snapshot = $this->activity->snapshot($staff);
        $staff->delete();
        $this->activity->log('STAFF_DELETED', 'staff', 'Staff member deleted', $staff, $snapshot, null, 'success', $actor);
    }

    public function updateOwnProfile(User $actor, array $data): User
    {
        if (array_key_exists('name', $data)) {
            $actor->name = $data['name'];
            $actor->save();
        }

        if ($actor->staff) {
            $actor->staff->fill(collect($data)->only([
                'phone', 'address', 'emergency_contact', 'emergency_contact_number', 'date_of_birth', 'profile_photo',
            ])->all());

            if (array_key_exists('name', $data)) {
                $actor->staff->full_name = $data['name'];
            }

            $actor->staff->updated_by = $actor->id;
            $actor->staff->save();
        }

        return $actor->load('staff.category', 'roleRecord.permissions');
    }
}
