<?php

use App\Http\Controllers\Api\ActivityLogs\ActivityLogController;
use App\Http\Controllers\Api\Attendance\AttendanceController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\Dashboard\DashboardController;
use App\Http\Controllers\Api\Expenses\ExpenseCategoryController;
use App\Http\Controllers\Api\Expenses\OfficeExpenseController;
use App\Http\Controllers\Api\Expenses\PersonalExpenseController;
use App\Http\Controllers\Api\Jobs\JobController;
use App\Http\Controllers\Api\Jobs\JobPaymentController;
use App\Http\Controllers\Api\Leads\LeadController;
use App\Http\Controllers\Api\Leads\LeadFollowupController;
use App\Http\Controllers\Api\Leads\LeadNoteController;
use App\Http\Controllers\Api\Reports\ReportController;
use App\Http\Controllers\Api\Salary\ExtraPayController;
use App\Http\Controllers\Api\Salary\SalaryController;
use App\Http\Controllers\Api\Security\SecurityPinController;
use App\Http\Controllers\Api\Settings\BusinessSettingController;
use App\Http\Controllers\Api\Settings\LeadStatusController;
use App\Http\Controllers\Api\Settings\WorkTypeController;
use App\Http\Controllers\Api\Staff\StaffCategoryController;
use App\Http\Controllers\Api\Staff\StaffController;
use App\Http\Controllers\Api\Vendors\VendorController;
use App\Http\Controllers\Api\Vendors\VendorPaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::get('/profile', [ProfileController::class, 'show'])->middleware('permission:profile.view');
        Route::put('/profile', [ProfileController::class, 'update'])->middleware('permission:profile.update');
        Route::put('/profile/password', [ProfileController::class, 'password'])->middleware('permission:profile.update');

        Route::post('/security/verify-pin', [SecurityPinController::class, 'verify']);
        Route::get('/security/status', [SecurityPinController::class, 'status']);
        Route::post('/security/logout', [SecurityPinController::class, 'logout']);
        Route::put('/security/pin', [SecurityPinController::class, 'update']);

        Route::get('/leads', [LeadController::class, 'index'])->middleware('permission:leads.view');
        Route::post('/leads', [LeadController::class, 'store'])->middleware('permission:leads.create');
        Route::get('/leads/{lead}', [LeadController::class, 'show'])->middleware('permission:leads.view');
        Route::put('/leads/{lead}', [LeadController::class, 'update'])->middleware('permission:leads.update');
        Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->middleware('permission:leads.delete');
        Route::post('/leads/{lead}/convert-to-job', [LeadController::class, 'convert'])->middleware(['permission:leads.update', 'permission:jobs.create']);
        Route::post('/leads/{lead}/convert', [LeadController::class, 'convertDirect'])->middleware(['permission:leads.update', 'permission:jobs.create']);
        Route::patch('/leads/{lead}', [LeadController::class, 'update'])->middleware('permission:leads.update');

        Route::get('/leads/{lead}/notes', [LeadNoteController::class, 'index'])->middleware('permission:leads.view');
        Route::post('/leads/{lead}/notes', [LeadNoteController::class, 'store'])->middleware('permission:leads.update');
        Route::put('/leads/{lead}/notes/{note}', [LeadNoteController::class, 'update'])->middleware('permission:leads.update');
        Route::delete('/leads/{lead}/notes/{note}', [LeadNoteController::class, 'destroy'])->middleware('permission:leads.update');

        Route::get('/leads/{lead}/followups', [LeadFollowupController::class, 'index'])->middleware('permission:leads.view');
        Route::post('/leads/{lead}/followups', [LeadFollowupController::class, 'store'])->middleware('permission:leads.update');
        Route::put('/leads/{lead}/followups/{followup}', [LeadFollowupController::class, 'update'])->middleware('permission:leads.update');
        Route::delete('/leads/{lead}/followups/{followup}', [LeadFollowupController::class, 'destroy'])->middleware('permission:leads.update');

        Route::get('/jobs', [JobController::class, 'index'])->middleware('permission:jobs.view');
        Route::post('/jobs', [JobController::class, 'store'])->middleware('permission:jobs.create');
        Route::get('/jobs/{job}', [JobController::class, 'show'])->middleware('permission:jobs.view');
        Route::put('/jobs/{job}', [JobController::class, 'update'])->middleware('permission:jobs.update');
        Route::delete('/jobs/{job}', [JobController::class, 'destroy'])->middleware('permission:jobs.delete');
        Route::patch('/jobs/{job}', [JobController::class, 'update'])->middleware('permission:jobs.update');
        Route::get('/jobs/{job}/payments', [JobPaymentController::class, 'index'])->middleware('permission:jobs.view');
        Route::post('/jobs/{job}/payments', [JobPaymentController::class, 'store'])->middleware('permission:jobs.update');
        Route::put('/jobs/{job}/payments/{payment}', [JobPaymentController::class, 'update'])->middleware('permission:jobs.update');
        Route::delete('/jobs/{job}/payments/{payment}', [JobPaymentController::class, 'destroy'])->middleware('permission:jobs.update');

        Route::get('/work-types', [WorkTypeController::class, 'index'])->middleware('permission:jobs.view,settings.view');
        Route::get('/work-types/{workType}', [WorkTypeController::class, 'show'])->middleware('permission:jobs.view,settings.view');
        Route::get('/lead-statuses', [LeadStatusController::class, 'index'])->middleware('permission:leads.view,settings.view');
        Route::get('/lead-statuses/{leadStatus}', [LeadStatusController::class, 'show'])->middleware('permission:leads.view,settings.view');
        Route::get('/staff-categories', [StaffCategoryController::class, 'index'])->middleware('permission:staff.view,settings.view');
        Route::get('/staff-categories/{staffCategory}', [StaffCategoryController::class, 'show'])->middleware('permission:staff.view,settings.view');

        Route::get('/vendors', [VendorController::class, 'index'])->middleware('permission:vendors.view');
        Route::post('/vendors', [VendorController::class, 'store'])->middleware('permission:vendors.create');
        Route::get('/vendors/{vendor}', [VendorController::class, 'show'])->middleware('permission:vendors.view');
        Route::put('/vendors/{vendor}', [VendorController::class, 'update'])->middleware('permission:vendors.update');
        Route::delete('/vendors/{vendor}', [VendorController::class, 'destroy'])->middleware('permission:vendors.delete');

        Route::get('/staff/directory', [StaffController::class, 'directory'])->middleware('permission:staff.view,attendance.view');
        Route::get('/staff', [StaffController::class, 'index'])->middleware('permission:staff.view');
        Route::get('/staff/{staff}', [StaffController::class, 'show'])->middleware('permission:staff.view');

        Route::get('/attendance/my', [AttendanceController::class, 'mine'])->middleware('permission:attendance.my,attendance.view');
        Route::get('/attendance/today', [AttendanceController::class, 'today'])->middleware('permission:attendance.view');
        Route::get('/attendance/monthly', [AttendanceController::class, 'monthly'])->middleware('permission:attendance.view');
        Route::get('/attendance/summary', [AttendanceController::class, 'summary'])->middleware('permission:attendance.view');
        Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->middleware('permission:attendance.my,attendance.manage');
        Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->middleware('permission:attendance.my,attendance.manage');
        Route::get('/attendance', [AttendanceController::class, 'index'])->middleware('permission:attendance.view');
        Route::post('/attendance', [AttendanceController::class, 'store'])->middleware('permission:attendance.manage');
        Route::get('/attendance/{attendance}', [AttendanceController::class, 'show'])->middleware('permission:attendance.view');
        Route::put('/attendance/{attendance}', [AttendanceController::class, 'update'])->middleware('permission:attendance.manage');
        Route::delete('/attendance/{attendance}', [AttendanceController::class, 'destroy'])->middleware('permission:attendance.manage');

        Route::get('/salary/my', [SalaryController::class, 'mine'])->middleware('permission:salary.my,salary.view');
        Route::get('/reports/leads', [ReportController::class, 'leads'])->middleware('permission:reports.view');

        Route::get('/dashboard', DashboardController::class)->middleware(['permission:dashboard.view', 'pin.verified']);

        Route::get('/reports', [ReportController::class, 'index'])->middleware(['permission:reports.view', 'pin.verified']);
        Route::get('/reports/financial', [ReportController::class, 'financial'])->middleware(['permission:reports.view', 'pin.verified']);
        Route::get('/reports/staff', [ReportController::class, 'staff'])->middleware(['permission:reports.view', 'pin.verified']);

        Route::get('/expenses/office', [OfficeExpenseController::class, 'index'])->middleware(['permission:expenses.view', 'pin.verified']);
        Route::post('/expenses/office', [OfficeExpenseController::class, 'store'])->middleware(['permission:expenses.create', 'pin.verified']);
        Route::get('/expenses/office/{officeExpense}', [OfficeExpenseController::class, 'show'])->middleware(['permission:expenses.view', 'pin.verified']);
        Route::put('/expenses/office/{officeExpense}', [OfficeExpenseController::class, 'update'])->middleware(['permission:expenses.update', 'pin.verified']);
        Route::delete('/expenses/office/{officeExpense}', [OfficeExpenseController::class, 'destroy'])->middleware(['permission:expenses.delete', 'pin.verified']);

        Route::get('/expenses/personal', [PersonalExpenseController::class, 'index'])->middleware(['permission:expenses.view', 'pin.verified']);
        Route::post('/expenses/personal', [PersonalExpenseController::class, 'store'])->middleware(['permission:expenses.create', 'pin.verified']);
        Route::get('/expenses/personal/{personalExpense}', [PersonalExpenseController::class, 'show'])->middleware(['permission:expenses.view', 'pin.verified']);
        Route::put('/expenses/personal/{personalExpense}', [PersonalExpenseController::class, 'update'])->middleware(['permission:expenses.update', 'pin.verified']);
        Route::delete('/expenses/personal/{personalExpense}', [PersonalExpenseController::class, 'destroy'])->middleware(['permission:expenses.delete', 'pin.verified']);

        Route::get('/office-expenses', [OfficeExpenseController::class, 'index'])->middleware(['permission:expenses.view', 'pin.verified']);
        Route::post('/office-expenses', [OfficeExpenseController::class, 'store'])->middleware(['permission:expenses.create', 'pin.verified']);
        Route::get('/office-expenses/{officeExpense}', [OfficeExpenseController::class, 'show'])->middleware(['permission:expenses.view', 'pin.verified']);
        Route::put('/office-expenses/{officeExpense}', [OfficeExpenseController::class, 'update'])->middleware(['permission:expenses.update', 'pin.verified']);
        Route::delete('/office-expenses/{officeExpense}', [OfficeExpenseController::class, 'destroy'])->middleware(['permission:expenses.delete', 'pin.verified']);

        Route::get('/personal-expenses', [PersonalExpenseController::class, 'index'])->middleware(['permission:expenses.view', 'pin.verified']);
        Route::post('/personal-expenses', [PersonalExpenseController::class, 'store'])->middleware(['permission:expenses.create', 'pin.verified']);
        Route::get('/personal-expenses/{personalExpense}', [PersonalExpenseController::class, 'show'])->middleware(['permission:expenses.view', 'pin.verified']);
        Route::put('/personal-expenses/{personalExpense}', [PersonalExpenseController::class, 'update'])->middleware(['permission:expenses.update', 'pin.verified']);
        Route::delete('/personal-expenses/{personalExpense}', [PersonalExpenseController::class, 'destroy'])->middleware(['permission:expenses.delete', 'pin.verified']);

        Route::get('/expense-categories', [ExpenseCategoryController::class, 'index'])->middleware(['permission:expenses.view,settings.view', 'pin.verified']);
        Route::post('/expense-categories', [ExpenseCategoryController::class, 'store'])->middleware(['permission:settings.manage', 'pin.verified']);
        Route::put('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->middleware(['permission:settings.manage', 'pin.verified']);
        Route::delete('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->middleware(['permission:settings.manage', 'pin.verified']);

        Route::get('/vendor-payments', [VendorPaymentController::class, 'index'])->middleware(['permission:vendors.view', 'pin.verified']);
        Route::post('/vendor-payments', [VendorPaymentController::class, 'store'])->middleware(['permission:vendors.create', 'pin.verified']);
        Route::get('/vendor-payments/{vendorPayment}', [VendorPaymentController::class, 'show'])->middleware(['permission:vendors.view', 'pin.verified']);
        Route::put('/vendor-payments/{vendorPayment}', [VendorPaymentController::class, 'update'])->middleware(['permission:vendors.update', 'pin.verified']);
        Route::delete('/vendor-payments/{vendorPayment}', [VendorPaymentController::class, 'destroy'])->middleware(['permission:vendors.delete', 'pin.verified']);

        Route::post('/staff', [StaffController::class, 'store'])->middleware(['permission:staff.create', 'pin.verified']);
        Route::put('/staff/{staff}', [StaffController::class, 'update'])->middleware(['permission:staff.update', 'pin.verified']);
        Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])->middleware(['permission:staff.delete', 'pin.verified']);

        Route::get('/salary', [SalaryController::class, 'index'])->middleware(['permission:salary.view', 'pin.verified']);
        Route::post('/salary/generate', [SalaryController::class, 'generate'])->middleware(['permission:salary.manage', 'pin.verified']);
        Route::get('/salary/{salary}', [SalaryController::class, 'show'])->middleware(['permission:salary.view', 'pin.verified']);
        Route::put('/salary/{salary}', [SalaryController::class, 'update'])->middleware(['permission:salary.manage', 'pin.verified']);
        Route::post('/salary/{salary}/mark-paid', [SalaryController::class, 'markPaid'])->middleware(['permission:salary.manage', 'pin.verified']);
        Route::post('/salary/{salary}/pay', [SalaryController::class, 'markPaid'])->middleware(['permission:salary.manage', 'pin.verified']);

        Route::get('/extra-pay', [ExtraPayController::class, 'index'])->middleware(['permission:extra_pay.view', 'pin.verified']);
        Route::post('/extra-pay', [ExtraPayController::class, 'store'])->middleware(['permission:extra_pay.manage', 'pin.verified']);
        Route::get('/extra-pay/{extraPay}', [ExtraPayController::class, 'show'])->middleware(['permission:extra_pay.view', 'pin.verified']);
        Route::put('/extra-pay/{extraPay}', [ExtraPayController::class, 'update'])->middleware(['permission:extra_pay.manage', 'pin.verified']);
        Route::delete('/extra-pay/{extraPay}', [ExtraPayController::class, 'destroy'])->middleware(['permission:extra_pay.manage', 'pin.verified']);

        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->middleware(['permission:activity_logs.view', 'pin.verified']);
        Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->middleware(['permission:activity_logs.view', 'pin.verified']);

        Route::get('/settings', [BusinessSettingController::class, 'show'])->middleware(['permission:settings.view', 'pin.verified']);
        Route::put('/settings', [BusinessSettingController::class, 'update'])->middleware(['permission:settings.manage', 'pin.verified']);
        Route::put('/settings/business', [BusinessSettingController::class, 'updateBusiness'])->middleware(['permission:settings.manage', 'pin.verified']);
        Route::put('/settings/permissions', [BusinessSettingController::class, 'updatePermissions'])->middleware(['permission:settings.manage', 'pin.verified']);

        Route::post('/work-types', [WorkTypeController::class, 'store'])->middleware(['permission:settings.manage', 'pin.verified']);
        Route::put('/work-types/{workType}', [WorkTypeController::class, 'update'])->middleware(['permission:settings.manage', 'pin.verified']);
        Route::delete('/work-types/{workType}', [WorkTypeController::class, 'destroy'])->middleware(['permission:settings.manage', 'pin.verified']);

        Route::post('/lead-statuses', [LeadStatusController::class, 'store'])->middleware(['permission:settings.manage', 'pin.verified']);
        Route::put('/lead-statuses/{leadStatus}', [LeadStatusController::class, 'update'])->middleware(['permission:settings.manage', 'pin.verified']);
        Route::delete('/lead-statuses/{leadStatus}', [LeadStatusController::class, 'destroy'])->middleware(['permission:settings.manage', 'pin.verified']);

        Route::post('/staff-categories', [StaffCategoryController::class, 'store'])->middleware(['permission:settings.manage', 'pin.verified']);
        Route::put('/staff-categories/{staffCategory}', [StaffCategoryController::class, 'update'])->middleware(['permission:settings.manage', 'pin.verified']);
        Route::delete('/staff-categories/{staffCategory}', [StaffCategoryController::class, 'destroy'])->middleware(['permission:settings.manage', 'pin.verified']);
    });
});
