<?php

namespace App\Enums\Finance;

enum TaxFormCode: string
{
    case ScheduleE = 'schedule_e';
    case ScheduleC = 'schedule_c';
    case ScheduleD = 'schedule_d';
    case ScheduleSE = 'schedule_se';
    case Form1099NEC = 'form_1099_nec';
    case Form1099INT = 'form_1099_int';
    case Form1099DIV = 'form_1099_div';
    case Form5472 = 'form_5472';
    case IRPF = 'irpf';
    case IRPFCapitalGains = 'irpf_capital_gains';
    case IRPFForeignIncome = 'irpf_foreign_income';

    public function label(): string
    {
        return match ($this) {
            self::ScheduleE => 'Schedule E (Rental Property)',
            self::ScheduleC => 'Schedule C (Business Profit/Loss)',
            self::ScheduleD => 'Schedule D (Capital Gains/Losses)',
            self::ScheduleSE => 'Schedule SE (Self-Employment Tax)',
            self::Form1099NEC => 'Form 1099-NEC (Nonemployee Compensation)',
            self::Form1099INT => 'Form 1099-INT (Interest Income)',
            self::Form1099DIV => 'Form 1099-DIV (Dividend Income)',
            self::Form5472 => 'Form 5472 (Related Party Transactions)',
            self::IRPF => 'IRPF (Spanish Income Tax)',
            self::IRPFCapitalGains => 'IRPF (Capital Gains)',
            self::IRPFForeignIncome => 'IRPF (Foreign Income)',
        };
    }

    public function country(): string
    {
        return match ($this) {
            self::ScheduleE, self::ScheduleC, self::ScheduleD, self::ScheduleSE,
            self::Form1099NEC, self::Form1099INT, self::Form1099DIV, self::Form5472 => 'USA',
            self::IRPF, self::IRPFCapitalGains, self::IRPFForeignIncome => 'Spain',
        };
    }
}
