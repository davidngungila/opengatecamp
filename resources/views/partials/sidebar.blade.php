@php
    $p = request()->path() === '/' ? 'dashboard' : request()->path();
    $seg = explode('/', $p);
    $base = $seg[0];
    $isFinance = $base === 'accounting';
    $isComms = $base === 'messaging';
    $isSystem = in_array($base, ['users', 'settings']);
@endphp

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-mark">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                <path d="M12 2L4 6v6c0 5 3.4 8.6 8 10 4.6-1.4 8-5 8-10V6l-8-4z" fill="white" fill-opacity=".95"/>
                <path d="M12 6v12M9 9h6" stroke="#0B1F3A" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="brand-text">
            <strong>Open Gate</strong>
            <span>EVENT &amp; MISSION MGMT</span>
        </div>
    </div>

    <nav class="sidebar-scroll" id="sidebarNav">
        <!-- Dashboard -->
        <div class="tooltip-wrap">
            <a href="{{ url('/') }}" class="nav-single {{ $base === 'dashboard' ? 'active' : '' }}">
                <span class="nav-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="9" rx="1.5"/>
                        <rect x="14" y="3" width="7" height="5" rx="1.5"/>
                        <rect x="14" y="12" width="7" height="9" rx="1.5"/>
                        <rect x="3" y="16" width="7" height="5" rx="1.5"/>
                    </svg>
                </span>
                <span class="nav-label">Dashboard</span>
            </a>
            <span class="tt">Dashboard</span>
        </div>

        <!-- Calendar -->
        <div class="tooltip-wrap">
            <a href="{{ route('calendar.index') }}" class="nav-single {{ $base === 'calendar' ? 'active' : '' }}">
                <span class="nav-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <path d="M16 2v4M8 2v4M3 10h18"/>
                    </svg>
                </span>
                <span class="nav-label">Calendar</span>
            </a>
            <span class="tt">Calendar</span>
        </div>

        <!-- Admission Desk -->
        <div class="tooltip-wrap">
            <a href="{{ route('admission.index') }}" class="nav-single {{ $base === 'admission' ? 'active' : '' }}">
                <span class="nav-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                        <path d="M22 4L12 14.01l-3-3"/>
                    </svg>
                </span>
                <span class="nav-label">Admission Desk</span>
            </a>
            <span class="tt">Admission Desk</span>
        </div>

        <!-- Registrations -->
        <div class="tooltip-wrap">
            <a href="{{ route('attendees.index') }}" class="nav-single {{ $base === 'attendees' ? 'active' : '' }}">
                <span class="nav-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 6h13M8 12h13M8 18h13"/>
                        <path d="M3 6h.01M3 12h.01M3 18h.01"/>
                    </svg>
                </span>
                <span class="nav-label">Registrations</span>
            </a>
            <span class="tt">Registrations</span>
        </div>

        <!-- Pledges -->
        <div class="tooltip-wrap">
            <a href="{{ route('pledges.index') }}" class="nav-single {{ $base === 'pledges' ? 'active' : '' }}">
                <span class="nav-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path d="M12 8v6M9 11h6"/>
                    </svg>
                </span>
                <span class="nav-label">Pledges</span>
            </a>
            <span class="tt">Pledges</span>
        </div>

        <!-- Communication Group -->
        <div class="nav-group">
            <div class="tooltip-wrap">
                <button type="button" class="nav-parent {{ $isComms ? 'expanded' : '' }}" onclick="toggleNavGroup(this)">
                    <span class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                        </svg>
                    </span>
                    <span class="nav-label">Communication</span>
                    <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </button>
                <span class="tt">Communication</span>
            </div>
            <div class="nav-children {{ $isComms ? 'open' : '' }}">
                <a href="{{ route('messaging.sms') }}" class="nav-child {{ request()->routeIs('messaging.sms') ? 'active' : '' }}">SMS</a>
                <a href="{{ route('messaging.email') }}" class="nav-child {{ request()->routeIs('messaging.email') ? 'active' : '' }}">Email</a>
                <a href="{{ route('messaging.templates') }}" class="nav-child {{ request()->routeIs('messaging.templates') ? 'active' : '' }}">Templates</a>
                <a href="{{ route('messaging.history') }}" class="nav-child {{ request()->routeIs('messaging.history') || request()->routeIs('messaging.show') ? 'active' : '' }}">History</a>
                <a href="{{ route('messaging.notifications') }}" class="nav-child {{ request()->routeIs('messaging.notifications') ? 'active' : '' }}">Notification Logs</a>
                <a href="{{ route('messaging.settings') }}" class="nav-child {{ request()->routeIs('messaging.settings') ? 'active' : '' }}">Settings</a>
            </div>
        </div>

        <!-- Finance Group -->
        <div class="nav-group">
            <div class="tooltip-wrap">
                <button type="button" class="nav-parent {{ $isFinance ? 'expanded' : '' }}" onclick="toggleNavGroup(this)">
                    <span class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 3v18h18"/>
                            <rect x="7" y="12" width="3" height="6"/>
                            <rect x="12.5" y="8" width="3" height="10"/>
                            <rect x="18" y="5" width="3" height="13"/>
                        </svg>
                    </span>
                    <span class="nav-label">Finance</span>
                    <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </button>
                <span class="tt">Finance</span>
            </div>
            <div class="nav-children {{ $isFinance ? 'open' : '' }}">
                <a href="{{ url('/accounting') }}" class="nav-child {{ $p === 'accounting' ? 'active' : '' }}">Overview</a>
                <a href="{{ url('/accounting/offerings') }}" class="nav-child {{ $p === 'accounting/offerings' ? 'active' : '' }}">Contributions &amp; Income</a>
                <a href="{{ url('/accounting/payments') }}" class="nav-child {{ $p === 'accounting/payments' ? 'active' : '' }}">Expenses</a>
                <a href="{{ url('/accounting/cash-bank') }}" class="nav-child {{ $p === 'accounting/cash-bank' ? 'active' : '' }}">Cash &amp; Bank</a>
                <a href="{{ url('/accounting/budgets') }}" class="nav-child {{ $p === 'accounting/budgets' ? 'active' : '' }}">Budgets</a>
                <a href="{{ url('/accounting/accounts') }}" class="nav-child {{ $p === 'accounting/accounts' ? 'active' : '' }}">Chart of Accounts</a>
                <a href="{{ url('/accounting/journal') }}" class="nav-child {{ in_array($p, ['accounting/journal', 'accounting/journal/create']) ? 'active' : '' }}">Journal Entries</a>
                <a href="{{ url('/accounting/transactions') }}" class="nav-child {{ $p === 'accounting/transactions' ? 'active' : '' }}">Transactions</a>
                <a href="{{ url('/accounting/trial-balance') }}" class="nav-child {{ $p === 'accounting/trial-balance' ? 'active' : '' }}">Trial Balance</a>
                <a href="{{ url('/accounting/ledger') }}" class="nav-child {{ str_starts_with($p, 'accounting/ledger') ? 'active' : '' }}">General Ledger</a>
                <a href="{{ url('/accounting/income-statement') }}" class="nav-child {{ $p === 'accounting/income-statement' ? 'active' : '' }}">Income &amp; Expenditure</a>
                <a href="{{ url('/accounting/balance-sheet') }}" class="nav-child {{ $p === 'accounting/balance-sheet' ? 'active' : '' }}">Balance Sheet</a>
            </div>
        </div>

        <!-- Administration Group -->
        <div class="nav-group">
            <div class="tooltip-wrap">
                <button type="button" class="nav-parent {{ $isSystem ? 'expanded' : '' }}" onclick="toggleNavGroup(this)">
                    <span class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.7 1.7 0 00.3 1.9l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.9-.3 1.7 1.7 0 00-1 1.6V21a2 2 0 01-4 0v-.1a1.7 1.7 0 00-1-1.6 1.7 1.7 0 00-1.9.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.7 1.7 0 00.3-1.9 1.7 1.7 0 00-1.6-1H3a2 2 0 010-4h.1a1.7 1.7 0 001.6-1 1.7 1.7 0 00-.3-1.9l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.9.3H9a1.7 1.7 0 001-1.6V3a2 2 0 014 0v.1a1.7 1.7 0 001 1.6 1.7 1.7 0 001.9-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.9V9a1.7 1.7 0 001.6 1H21a2 2 0 010 4h-.1a1.7 1.7 0 00-1.6 1z"/>
                        </svg>
                    </span>
                    <span class="nav-label">Administration</span>
                    <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </button>
                <span class="tt">Administration</span>
            </div>
            <div class="nav-children {{ $isSystem ? 'open' : '' }}">
                <a href="{{ url('/users') }}" class="nav-child {{ $p === 'users' ? 'active' : '' }}">Users &amp; Roles</a>
                <a href="{{ url('/settings') }}" class="nav-child {{ $p === 'settings' ? 'active' : '' }}">Settings</a>
            </div>
        </div>

    </nav>
</aside>