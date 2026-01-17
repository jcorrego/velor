<?php

namespace App\Enums\Finance;

enum TaxFormCode: string
{
    case ScheduleE = 'schedule_e';
    case ScheduleC = 'schedule_c';
    case ScheduleD = 'schedule_d';
    case ScheduleSE = 'schedule_se';
    case Form1040 = 'form_1040';
    case Form1040NR = 'form_1040_nr';
    case Form1120 = 'form_1120';
    case Form1099NEC = 'form_1099_nec';
    case Form1099INT = 'form_1099_int';
    case Form1099DIV = 'form_1099_div';
    case Form5472 = 'form_5472';
    case IRPF = 'irpf';
    case IRPFCapitalGains = 'irpf_capital_gains';
    case IRPFForeignIncome = 'irpf_foreign_income';
    case Modelo720 = 'modelo_720';
    case Form100 = 'form_100';
    case ColombianDeclaration = 'colombian_declaration';

    public function label(): string
    {
        return match ($this) {
            self::ScheduleE => 'Schedule E (Rental Property)',
            self::ScheduleC => 'Schedule C (Business Profit/Loss)',
            self::ScheduleD => 'Schedule D (Capital Gains/Losses)',
            self::ScheduleSE => 'Schedule SE (Self-Employment Tax)',
            self::Form1040 => 'Form 1040 (U.S. Individual Income Tax Return)',
            self::Form1040NR => 'Form 1040-NR (Nonresident Alien Income Tax Return)',
            self::Form1120 => 'Form 1120 (U.S. Corporate Income Tax Return)',
            self::Form1099NEC => 'Form 1099-NEC (Nonemployee Compensation)',
            self::Form1099INT => 'Form 1099-INT (Interest Income)',
            self::Form1099DIV => 'Form 1099-DIV (Dividend Income)',
            self::Form5472 => 'Form 5472 (Related Party Transactions)',
            self::IRPF => 'IRPF (Spanish Personal Income Tax)',
            self::IRPFCapitalGains => 'IRPF (Capital Gains)',
            self::IRPFForeignIncome => 'IRPF (Foreign Income)',
            self::Modelo720 => 'Modelo 720 (Declaration of Foreign Assets)',
            self::Form100 => 'Form 100 (Spanish Corporate Income Tax)',
            self::ColombianDeclaration => 'Colombian Income Tax Declaration',
        };
    }

    public function country(): string
    {
        return match ($this) {
            self::ScheduleE, self::ScheduleC, self::ScheduleD, self::ScheduleSE,
            self::Form1040, self::Form1040NR, self::Form1120,
            self::Form1099NEC, self::Form1099INT, self::Form1099DIV, self::Form5472 => 'USA',
            self::IRPF, self::IRPFCapitalGains, self::IRPFForeignIncome, self::Modelo720, self::Form100 => 'Spain',
            self::ColombianDeclaration => 'Colombia',
        };
    }
}
