<?php

namespace App\Enums\Finance;

enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case Transfer = 'transfer';
    case Fee = 'fee';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Income',
            self::Expense => 'Expense',
            self::Transfer => 'Transfer',
            self::Fee => 'Fee',
        };
    }

    public function isIncomeOrExpense(): bool
    {
        return in_array($this, [self::Income, self::Expense]);
    }
}
