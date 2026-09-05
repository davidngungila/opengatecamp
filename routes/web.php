<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DigitalCardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberPortalController;
use App\Http\Controllers\MessagingController;
use App\Http\Controllers\PledgeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Public verification page (receipt / ticket QR scan)
Route::get('/verify', [VerificationController::class, 'verify'])->name('verify');

// Public digital card view (no auth required — shared via SMS link)
Route::get('/card/{hash}', [DigitalCardController::class, 'show'])->name('cards.show');
Route::get('/card/{hash}/pdf', [DigitalCardController::class, 'publicPdf'])->name('cards.publicPdf');
Route::post('/card/{hash}/contribute', [DigitalCardController::class, 'contribute'])->name('cards.contribute');

// Short personalised card link for SMS (redirects to the full card view)
Route::get('/c/{code}', [DigitalCardController::class, 'lite'])->name('cards.lite');

Route::middleware(['auth', 'committee.readonly'])->group(function () {

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

    // ── User Account (Profile / Settings / Audit Logs) ──────────────────
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
        Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
        Route::post('/profile/photo', [AccountController::class, 'updatePhoto'])->name('profile.photo');
        Route::delete('/profile/photo', [AccountController::class, 'removePhoto'])->name('profile.photo.remove');
        Route::put('/password', [AccountController::class, 'updatePassword'])->name('password.update');
        Route::get('/settings', [AccountController::class, 'settings'])->name('settings');
        Route::get('/audit-logs', [AccountController::class, 'auditLogs'])->name('audit-logs');
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

    Route::middleware('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::patch('/users/{user}/suspend', [UserController::class, 'toggleSuspend'])->name('users.suspend');
        Route::patch('/users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password');
        Route::get('/api/users/{user}', [UserController::class, 'apiUserDetail'])->name('users.api');
        Route::put('/roles/{role}/permissions', [UserController::class, 'updatePermissions'])->name('roles.permissions');

        Route::get('/settings', fn () => redirect()->route('settings.page.general'))->name('settings.index');

        Route::get('/settings/general', [SettingsController::class, 'generalPage'])->name('settings.page.general');
        Route::get('/settings/notifications', [SettingsController::class, 'notificationsPage'])->name('settings.page.notifications');
        Route::get('/settings/accounting', [SettingsController::class, 'accountingPage'])->name('settings.page.accounting');
        Route::get('/settings/financial-years', [SettingsController::class, 'financialYearsPage'])->name('settings.page.years');
        Route::get('/settings/fellowships', [SettingsController::class, 'fellowshipsPage'])->name('settings.page.fellowships');
        Route::get('/settings/security', [SettingsController::class, 'securityPage'])->name('settings.page.security');
        Route::get('/settings/backup', [SettingsController::class, 'backupPage'])->name('settings.page.backup');
        Route::get('/settings/audit', [SettingsController::class, 'auditPage'])->name('settings.page.audit');
        Route::get('/settings/backup/download', [SettingsController::class, 'backup'])->name('settings.backup');

        Route::post('/settings/general', [SettingsController::class, 'updateGeneral'])->name('settings.general');
        Route::post('/settings/digital-card', [SettingsController::class, 'updateDigitalCard'])->name('settings.digital-card');
        Route::post('/settings/organization', [SettingsController::class, 'updateOrganization'])->name('settings.organization');
        Route::post('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications');
        Route::post('/settings/accounting', [SettingsController::class, 'updateAccounting'])->name('settings.accounting');
        Route::post('/settings/fellowships', [SettingsController::class, 'updateFellowships'])->name('settings.fellowships');
        Route::post('/settings/fellowships/providers', [SettingsController::class, 'storeFellowship'])->name('settings.fellowships.store');
        Route::put('/settings/fellowships/providers/{index}', [SettingsController::class, 'updateFellowship'])->whereNumber('index')->name('settings.fellowships.update');
        Route::delete('/settings/fellowships/providers/{index}', [SettingsController::class, 'destroyFellowship'])->whereNumber('index')->name('settings.fellowships.destroy');
        Route::post('/settings/security', [SettingsController::class, 'updateSecurity'])->name('settings.security');
        Route::post('/settings/financial-years', [SettingsController::class, 'storeYear'])->name('settings.years.store');
        Route::put('/settings/financial-years/{year}', [SettingsController::class, 'updateYear'])->name('settings.years.update');
        Route::delete('/settings/financial-years/{year}', [SettingsController::class, 'destroyYear'])->name('settings.years.destroy');
        Route::delete('/settings/audit', [SettingsController::class, 'clearAudit'])->name('settings.audit.clear');
    });

    Route::get('/settings/financial-years/{yearId}/switch', [SettingsController::class, 'switchYear'])->whereNumber('yearId')->name('settings.years.switch');

    Route::get('/events', fn () => redirect()->route('dashboard'))->name('events.index');
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
    Route::post('/calendar/sessions', [EventController::class, 'storeCalendarSession'])->name('calendar.sessions.store');
    Route::get('/calendar/timetable', [EventController::class, 'timetable'])->name('calendar.timetable');

    // ── Admission desk (scan ticket / enter code) ─────────
    Route::get('/admission', [CheckInController::class, 'index'])->name('admission.index');
    Route::post('/admission/lookup', [CheckInController::class, 'lookup'])->name('admission.lookup');
    Route::post('/admission/admit', [CheckInController::class, 'admit'])->name('admission.admit');
    Route::get('/attendees', [EventController::class, 'attendees'])->name('attendees.index');
    Route::post('/attendees', [EventController::class, 'storeAttendeeGlobal'])->name('attendees.store');
    Route::post('/attendees/{attendee}/payments', [EventController::class, 'recordAttendeePayment'])->name('attendees.payments');
    Route::post('/attendees/{attendee}/sms', [EventController::class, 'sendAttendeeSms'])->name('attendees.sms');
    Route::get('/attendees/{attendee}/ticket', [EventController::class, 'ticketPdf'])->name('attendees.ticket.pdf');
    Route::post('/attendees/{attendee}/ticket/sms', [EventController::class, 'sendTicketSms'])->name('attendees.ticket.sms');

    Route::get('/pledges', [PledgeController::class, 'index'])->name('pledges.index');
    Route::post('/pledges', [PledgeController::class, 'store'])->name('pledges.store');
    Route::put('/pledges/{pledge}', [PledgeController::class, 'update'])->name('pledges.update');
    Route::delete('/pledges/{pledge}', [PledgeController::class, 'destroy'])->name('pledges.destroy');
    Route::post('/pledges/{pledge}/payments', [PledgeController::class, 'recordPayment'])->name('pledges.payments');
    Route::post('/pledges/{pledge}/remind', [PledgeController::class, 'remind'])->name('pledges.remind');
    Route::post('/pledges/{pledge}/thanks', [PledgeController::class, 'sendThanks'])->name('pledges.thanks');

    // ── Digital Cards ────────────────────────────────────
    Route::get('/digital-cards', [DigitalCardController::class, 'index'])->name('cards.index');
    Route::get('/digital-cards/export', [DigitalCardController::class, 'exportCsv'])->name('cards.export');
    Route::get('/digital-cards/{card}', [DigitalCardController::class, 'details'])->name('cards.details');
    Route::post('/digital-cards', [DigitalCardController::class, 'store'])->name('cards.store');
    Route::put('/digital-cards/{card}', [DigitalCardController::class, 'update'])->name('cards.update');
    Route::delete('/digital-cards/{card}', [DigitalCardController::class, 'destroy'])->name('cards.destroy');
    Route::post('/digital-cards/{card}/status', [DigitalCardController::class, 'updateStatus'])->name('cards.status');
    Route::post('/digital-cards/{card}/add-contribution', [DigitalCardController::class, 'addContribution'])->name('cards.addContribution');
    Route::post('/digital-cards/{card}/send-sms', [DigitalCardController::class, 'sendSms'])->name('cards.sendSms');
    Route::post('/digital-cards/{card}/add-list', [DigitalCardController::class, 'addList'])->name('cards.addList');
    Route::post('/digital-cards/{card}/send-pending', [DigitalCardController::class, 'sendPending'])->name('cards.sendPending');
    Route::post('/digital-cards/recipients/{recipient}/delivery', [DigitalCardController::class, 'checkRecipientDelivery'])->name('cards.recipient.delivery');
    Route::post('/digital-cards/recipients/{recipient}/resend', [DigitalCardController::class, 'resendSms'])->name('cards.recipient.resend');
    Route::delete('/digital-cards/recipients/{recipient}', [DigitalCardController::class, 'destroyRecipient'])->name('cards.recipient.destroy');
    Route::get('/digital-cards/{card}/pdf', [DigitalCardController::class, 'downloadPdf'])->name('cards.pdf');
    Route::get('/digital-cards/{card}/preview', [DigitalCardController::class, 'preview'])->name('cards.preview');

    Route::get('/calendar-legacy', fn () => redirect()->route('calendar.index'))->name('calendar.legacy');

    Route::middleware('not.committee')->group(function () {
        Route::get('/messaging', fn () => redirect()->route('messaging.sms'))->name('messaging.index');
        Route::get('/messaging/sms', [MessagingController::class, 'sms'])->name('messaging.sms');
        Route::get('/messaging/email', [MessagingController::class, 'email'])->name('messaging.email');
        Route::get('/messaging/notifications', [MessagingController::class, 'notifications'])->name('messaging.notifications');
        Route::post('/messaging/notifications/mark-all-read', [MessagingController::class, 'markAllNotificationsRead'])->name('messaging.notifications.mark-all-read');
        Route::get('/messaging/history', [MessagingController::class, 'history'])->name('messaging.history');
        Route::get('/messaging/history/{message}', [MessagingController::class, 'show'])->name('messaging.show');
        Route::post('/messaging/history/{message}/delivery', [MessagingController::class, 'checkDelivery'])->name('messaging.delivery');
        Route::get('/messaging/templates', [MessagingController::class, 'templates'])->name('messaging.templates');
        Route::post('/messaging/templates', [MessagingController::class, 'templateStore'])->name('messaging.templates.store');
        Route::delete('/messaging/templates/{id}', [MessagingController::class, 'templateDestroy'])->name('messaging.templates.destroy');
        Route::get('/messaging/settings', [MessagingController::class, 'settings'])->name('messaging.settings');
        Route::get('/messaging/settings/email', [MessagingController::class, 'emailSettings'])->name('messaging.settings.email');
        Route::post('/messaging/settings/email', [MessagingController::class, 'saveEmailSettings'])->name('messaging.settings.email.save');
        Route::post('/messaging', [MessagingController::class, 'store'])->name('messaging.store');
        Route::post('/messaging/token', [MessagingController::class, 'saveToken'])->name('messaging.token');

        Route::post('/messaging/settings/sms/providers', [MessagingController::class, 'smsProviderStore'])->name('messaging.settings.sms.provider.store');
        Route::post('/messaging/settings/sms/providers/{key}', [MessagingController::class, 'smsProviderUpdate'])->name('messaging.settings.sms.provider.update');
        Route::post('/messaging/settings/sms/providers/{key}/primary', [MessagingController::class, 'smsProviderPrimary'])->name('messaging.settings.sms.provider.primary');
        Route::post('/messaging/settings/sms/providers/{key}/test', [MessagingController::class, 'testSmsProvider'])->name('messaging.settings.sms.provider.test');
        Route::delete('/messaging/settings/sms/providers/{key}', [MessagingController::class, 'smsProviderDelete'])->name('messaging.settings.sms.provider.delete');

        Route::post('/messaging/settings/email/providers', [MessagingController::class, 'emailProviderStore'])->name('messaging.settings.email.provider.store');
        Route::post('/messaging/settings/email/providers/{key}', [MessagingController::class, 'emailProviderUpdate'])->name('messaging.settings.email.provider.update');
        Route::post('/messaging/settings/email/providers/{key}/primary', [MessagingController::class, 'emailProviderPrimary'])->name('messaging.settings.email.provider.primary');
        Route::delete('/messaging/settings/email/providers/{key}', [MessagingController::class, 'emailProviderDelete'])->name('messaging.settings.email.provider.delete');
        Route::get('/messaging/recipients', [MessagingController::class, 'getRecipients'])->name('messaging.recipients');
        Route::post('/messaging/use-template', [MessagingController::class, 'useTemplate'])->name('messaging.use-template');
    });

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

    Route::middleware('not.committee')->group(function () {
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
        Route::get('/accounting/transactions/{entry}/receipt', [AccountingController::class, 'receiptPdf'])->name('accounting.transactions.receipt');

        // JSON detail endpoints for drawers
        Route::get('/accounting/api/overview', [AccountingController::class, 'apiOverview'])->name('accounting.api.overview');
        Route::get('/accounting/api/receipts/{doc}', [AccountingController::class, 'offeringDetail'])->name('accounting.api.receipt');
        Route::get('/accounting/api/payments/{doc}', [AccountingController::class, 'paymentDetail'])->name('accounting.api.payment');
        Route::get('/accounting/api/accounts/{account}', [AccountingController::class, 'apiAccountDetail'])->name('accounting.api.account');
        Route::get('/accounting/api/journal/{entry}', [AccountingController::class, 'apiJournalDetail'])->name('accounting.api.journal');
        Route::get('/accounting/api/trial-balance/{account}', [AccountingController::class, 'apiTrialBalanceDetail'])->name('accounting.api.trial-balance');
        Route::get('/accounting/api/ledger/{line}', [AccountingController::class, 'apiLedgerDetail'])->name('accounting.api.ledger');
        Route::get('/accounting/api/budgets/{budget}', [AccountingController::class, 'apiBudgetDetail'])->name('accounting.api.budget');
        Route::get('/accounting/api/cash-movements/{line}', [AccountingController::class, 'apiCashMovementDetail'])->name('accounting.api.cash-movement');
    });
});
