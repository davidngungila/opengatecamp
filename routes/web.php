<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PledgeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MessagingController;
use App\Http\Controllers\MemberPortalController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── Member Portal ──────────────────────────────────
    Route::prefix('portal')->name('portal.')->group(function () {
        Route::get('/', [MemberPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [MemberPortalController::class, 'profile'])->name('profile');
        Route::put('/profile', [MemberPortalController::class, 'updateProfile'])->name('profile.update');
        Route::get('/family', [MemberPortalController::class, 'family'])->name('family');
        Route::get('/registrations', [MemberPortalController::class, 'registrations'])->name('registrations');
        Route::post('/registrations', [MemberPortalController::class, 'storeAttendee'])->name('registrations.store');
        Route::get('/pledges', [MemberPortalController::class, 'pledges'])->name('pledges');
        Route::post('/pledges', [MemberPortalController::class, 'storePledge'])->name('pledges.store');
        Route::get('/contributions', [MemberPortalController::class, 'contributions'])->name('contributions');
        Route::get('/activations', [MemberPortalController::class, 'activations'])->name('activations');
        Route::get('/settings', [MemberPortalController::class, 'settings'])->name('settings');
        Route::put('/password', [MemberPortalController::class, 'updatePassword'])->name('password.update');
    });

    Route::post('/members/activate-students', [MemberController::class, 'activateAll'])->name('members.activateAll');
    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::post('/members', [MemberController::class, 'store'])->name('members.store');
    Route::patch('/members/{member}/status', [MemberController::class, 'toggleStatus'])->name('members.status');
    Route::post('/members/{member}/activate', [MemberController::class, 'activate'])->name('members.activate');
    Route::put('/members/{member}', [MemberController::class, 'update'])->name('members.update');
    Route::delete('/members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');
    Route::get('/members/profile/{key}', [MemberController::class, 'profile'])->name('members.profile');
    Route::get('/members/{member}/edit', [MemberController::class, 'edit'])->name('members.edit');

    Route::resource('families', FamilyController::class)->except(['show', 'create', 'edit']);

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::patch('/users/{user}/suspend', [UserController::class, 'toggleSuspend'])->name('users.suspend');
    Route::patch('/users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password');
    Route::put('/roles/{role}/permissions', [UserController::class, 'updatePermissions'])->name('roles.permissions');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/general', [SettingsController::class, 'updateGeneral'])->name('settings.general');
    Route::post('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications');
    Route::post('/settings/accounting', [SettingsController::class, 'updateAccounting'])->name('settings.accounting');
    Route::post('/settings/security', [SettingsController::class, 'updateSecurity'])->name('settings.security');
    Route::post('/settings/financial-years', [SettingsController::class, 'storeYear'])->name('settings.years.store');
    Route::put('/settings/financial-years/{year}', [SettingsController::class, 'updateYear'])->name('settings.years.update');
    Route::delete('/settings/financial-years/{year}', [SettingsController::class, 'destroyYear'])->name('settings.years.destroy');
    Route::get('/settings/financial-years/{yearId}/switch', [SettingsController::class, 'switchYear'])->whereNumber('yearId')->name('settings.years.switch');
    Route::delete('/settings/audit', [SettingsController::class, 'clearAudit'])->name('settings.audit.clear');
    Route::get('/settings/backup', [SettingsController::class, 'backup'])->name('settings.backup');

    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::get('/events/{event:slug}', [EventController::class, 'show'])->name('events.show');
    Route::put('/events/{event:slug}', [EventController::class, 'update'])->name('events.update');
    Route::patch('/events/{event:slug}/status', [EventController::class, 'toggleStatus'])->name('events.status');
    Route::delete('/events/{event:slug}', [EventController::class, 'destroy'])->name('events.destroy');
    Route::post('/events/{event:slug}/sessions', [EventController::class, 'storeSession'])->name('events.sessions.store');
    Route::delete('/events/{event:slug}/sessions/{session}', [EventController::class, 'destroySession'])->name('events.sessions.destroy');
    Route::post('/events/{event:slug}/attendees', [EventController::class, 'storeAttendee'])->name('events.attendees.store');
    Route::put('/events/{event:slug}/attendees/{attendee}', [EventController::class, 'updateAttendee'])->name('events.attendees.update');
    Route::delete('/events/{event:slug}/attendees/{attendee}', [EventController::class, 'destroyAttendee'])->name('events.attendees.destroy');
    Route::get('/calendar', [EventController::class, 'calendar'])->name('calendar.index');
    Route::get('/attendees', [EventController::class, 'attendees'])->name('attendees.index');
    Route::post('/attendees', [EventController::class, 'storeAttendeeGlobal'])->name('attendees.store');
    Route::post('/attendees/{attendee}/payments', [EventController::class, 'recordAttendeePayment'])->name('attendees.payments');
    Route::post('/attendees/{attendee}/sms', [EventController::class, 'sendAttendeeSms'])->name('attendees.sms');

    Route::get('/pledges', [PledgeController::class, 'index'])->name('pledges.index');
    Route::post('/pledges', [PledgeController::class, 'store'])->name('pledges.store');
    Route::put('/pledges/{pledge}', [PledgeController::class, 'update'])->name('pledges.update');
    Route::delete('/pledges/{pledge}', [PledgeController::class, 'destroy'])->name('pledges.destroy');
    Route::post('/pledges/{pledge}/payments', [PledgeController::class, 'recordPayment'])->name('pledges.payments');

    Route::get('/calendar-legacy', fn () => redirect()->route('calendar.index'))->name('calendar.legacy');
    Route::get('/messaging', fn () => redirect()->route('messaging.sms'))->name('messaging.index');
    Route::get('/messaging/sms', [MessagingController::class, 'sms'])->name('messaging.sms');
    Route::get('/messaging/email', [MessagingController::class, 'email'])->name('messaging.email');
    Route::get('/messaging/notifications', [MessagingController::class, 'notifications'])->name('messaging.notifications');
    Route::get('/messaging/templates', [MessagingController::class, 'templates'])->name('messaging.templates');
    Route::get('/messaging/settings', [MessagingController::class, 'settings'])->name('messaging.settings');
    Route::post('/messaging', [MessagingController::class, 'store'])->name('messaging.store');
    Route::post('/messaging/token', [MessagingController::class, 'saveToken'])->name('messaging.token');
    Route::get('/messaging/recipients', [MessagingController::class, 'getRecipients'])->name('messaging.recipients');
    Route::post('/messaging/use-template', [MessagingController::class, 'useTemplate'])->name('messaging.use-template');

    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{encrypted}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/documents/{encrypted}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::get('/documents/{encrypted}/preview/file', [DocumentController::class, 'previewFile'])->name('documents.preview.file');
    Route::delete('/documents/{encrypted}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    Route::get('/documents/categories', [DocumentController::class, 'categories'])->name('documents.categories');
    Route::post('/documents/categories', [DocumentController::class, 'storeCategory'])->name('documents.categories.store');
    Route::put('/documents/categories/{category}', [DocumentController::class, 'updateCategory'])->name('documents.categories.update');
    Route::delete('/documents/categories/{category}', [DocumentController::class, 'destroyCategory'])->name('documents.categories.destroy');

    Route::get('/accounting', [AccountingController::class, 'index'])->name('accounting.index');
    Route::get('/accounting/accounts', [AccountingController::class, 'accounts'])->name('accounting.accounts');
    Route::post('/accounting/accounts', [AccountingController::class, 'storeAccount'])->name('accounting.accounts.store');
    Route::put('/accounting/accounts/{account}', [AccountingController::class, 'updateAccount'])->name('accounting.accounts.update');
    Route::delete('/accounting/accounts/{account}', [AccountingController::class, 'destroyAccount'])->name('accounting.accounts.destroy');
    Route::get('/accounting/journal/create', [AccountingController::class, 'createJournal'])->name('accounting.journal.create');
    Route::post('/accounting/journal', [AccountingController::class, 'storeJournal'])->name('accounting.journal.store');
    Route::delete('/accounting/journal/{entry}', [AccountingController::class, 'destroyJournal'])->name('accounting.journal.destroy');
    Route::get('/accounting/journal', [AccountingController::class, 'journal'])->name('accounting.journal');
    Route::get('/accounting/trial-balance', [AccountingController::class, 'trialBalance'])->name('accounting.trial-balance');
    Route::get('/accounting/ledger', [AccountingController::class, 'ledger'])->name('accounting.ledger');
    Route::get('/accounting/income-statement', [AccountingController::class, 'incomeStatement'])->name('accounting.income-statement');
    Route::get('/accounting/balance-sheet', [AccountingController::class, 'balanceSheet'])->name('accounting.balance-sheet');
    Route::get('/accounting/offerings', [AccountingController::class, 'offerings'])->name('accounting.offerings');
    Route::post('/accounting/offerings', [AccountingController::class, 'storeOffering'])->name('accounting.offerings.store');
    Route::get('/accounting/payments', [AccountingController::class, 'payments'])->name('accounting.payments');
    Route::post('/accounting/payments', [AccountingController::class, 'storePayment'])->name('accounting.payments.store');
    Route::delete('/accounting/receipts-payments/{doc}', [AccountingController::class, 'destroyDocument'])->name('accounting.documents.destroy');
    Route::get('/accounting/cash-bank', [AccountingController::class, 'cashBank'])->name('accounting.cash-bank');
    Route::get('/accounting/budgets', [AccountingController::class, 'budgets'])->name('accounting.budgets');
    Route::post('/accounting/budgets', [AccountingController::class, 'storeBudget'])->name('accounting.budgets.store');
    Route::delete('/accounting/budgets/{budget}', [AccountingController::class, 'destroyBudget'])->name('accounting.budgets.destroy');
    Route::get('/accounting/reconciliation', [AccountingController::class, 'reconciliation'])->name('accounting.reconciliation');
    Route::post('/accounting/reconciliation', [AccountingController::class, 'storeReconciliation'])->name('accounting.reconciliation.store');
    Route::get('/accounting/transactions', [AccountingController::class, 'transactions'])->name('accounting.transactions');
});
