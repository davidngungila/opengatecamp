@extends('layouts.app')

@section('title', 'Accounting — Settings — OpenGate Camp Connect')
@section('crumb', 'System / Settings / Accounting')
@section('page_title', 'Accounting')

@php
    $s = fn($key, $default = '') => old($key, \App\Models\Setting::get($key, $default));
@endphp

@section('content')
<div class="fade-in">
  <div class="section-head"><h2>Settings</h2><p style="color:var(--text-secondary);font-size:13px;margin:4px 0 0">Accounting</p></div>

  <div class="solid-card">
    <h2 style="font-size:14.5px;margin:0 0 6px">Account Defaults for Automatic Double-Entry</h2>
    <p style="font-size:12.5px;color:var(--text-tertiary);margin:0 0 14px">Pledge payments, attendee registration payments and attendee fee payments are posted automatically to the journal. Choose the cash / bank / mobile-money accounts (debit) and the income accounts (credit) used for each.</p>
    <form method="POST" action="{{ route('settings.accounting') }}">
      @csrf
      <div class="form-grid">
        <div class="field">
          <label>Cash on Hand (petty payments)</label>
          <select name="acct_default_cash">
            <option value="">— Select —</option>
            @foreach($cashAccounts as $a)
            <option value="{{ $a->code }}" @if($s('acct.default_cash')===$a->code) selected @endif>{{ $a->code }} — {{ $a->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="field">
          <label>Bank account (bank payments)</label>
          <select name="acct_default_bank">
            <option value="">— Select —</option>
            @foreach($cashAccounts as $a)
            <option value="{{ $a->code }}" @if($s('acct.default_bank')===$a->code) selected @endif>{{ $a->code }} — {{ $a->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="field">
          <label>Mobile money float (mobile payments)</label>
          <select name="acct_default_mobile">
            <option value="">— Select —</option>
            @foreach($cashAccounts as $a)
            <option value="{{ $a->code }}" @if($s('acct.default_mobile')===$a->code) selected @endif>{{ $a->code }} — {{ $a->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="field">
          <label>Pledge income account (credit)</label>
          <select name="acct_pledge_income">
            <option value="">— Select —</option>
            @foreach($incomeAccounts as $a)
            <option value="{{ $a->code }}" @if($s('acct.pledge_income')===$a->code) selected @endif>{{ $a->code }} — {{ $a->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="field">
          <label>Attendee fee income account (credit)</label>
          <select name="acct_attendee_income">
            <option value="">— Select —</option>
            @foreach($incomeAccounts as $a)
            <option value="{{ $a->code }}" @if($s('acct.attendee_income')===$a->code) selected @endif>{{ $a->code }} — {{ $a->name }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="flex" style="justify-content:flex-end;margin-top:16px">
        <button type="submit" class="btn btn-accent">Save Accounting Defaults</button>
      </div>
    </form>
  </div>
</div>
@endsection
