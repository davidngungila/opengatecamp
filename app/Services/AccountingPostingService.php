<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class AccountingPostingService
{
    /**
     * Post a money-in double entry: Dr cash account, Cr income account.
     */
    public function postMoneyIn(array $params): JournalEntry
    {
        $amount = round((float) $params['amount'], 2);
        $method = $params['method'] ?? 'cash';
        $date = $params['date'];
        $description = $params['description'];
        $reference = $params['reference'] ?? null;
        $createdBy = $params['created_by'] ?? (auth()->user()?->name ?? 'Daniel Mwinuka');

        $cashId = $params['cashAccount'] ?? $this->cashAccountFor($method);
        $incomeId = $params['incomeAccount'] ?? null;

        $cash = $cashId ? Account::find($cashId) : null;
        $income = $incomeId ? Account::find($incomeId) : null;

        if (! $cash || ! $income) {
            throw new \RuntimeException('Automatic journal entry could not be posted: required accounts not configured.');
        }

        return DB::transaction(function () use ($amount, $cash, $income, $date, $description, $reference, $createdBy) {
            $entry = JournalEntry::create([
                'entry_no' => JournalEntry::nextEntryNo(),
                'entry_date' => $date,
                'description' => $description,
                'reference' => $reference,
                'status' => 'posted',
                'created_by' => $createdBy,
            ]);

            JournalLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $cash->id,
                'description' => 'Debit '.$cash->name,
                'debit' => $amount,
                'credit' => 0,
            ]);

            JournalLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $income->id,
                'description' => 'Credit '.$income->name,
                'debit' => 0,
                'credit' => $amount,
            ]);

            return $entry;
        });
    }

    /**
     * Resolve the account (id) configured for a payment method.
     */
    public function cashAccountFor(string $method): ?int
    {
        $settingKey = match ($method) {
            'bank' => 'acct.default_bank',
            'mobile' => 'acct.default_mobile',
            default => 'acct.default_cash',
        };

        $code = Setting::get($settingKey);
        if ($code) {
            $account = Account::where('code', $code)->where('is_cash', true)->first();
            if ($account) {
                return $account->id;
            }
        }

        $defaultCode = match ($method) {
            'bank' => '1000',
            'mobile' => '1020',
            default => '1010',
        };

        return Account::where('code', $defaultCode)->where('is_cash', true)->value('id');
    }

    /**
     * Resolve the income account id from settings key + fallback code.
     */
    public function incomeAccount(string $settingKey, string $fallbackCode = '4040'): ?int
    {
        $code = Setting::get($settingKey);
        $account = $code ? Account::where('code', $code)->where('type', 'income')->first() : null;

        return $account?->id
            ?? Account::where('code', $fallbackCode)->where('type', 'income')->value('id');
    }
}