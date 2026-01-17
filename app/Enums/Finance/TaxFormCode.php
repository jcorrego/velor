<?php

namespace App\Enums\Finance;

enum TaxFormCode: string
{
    case ScheduleE = 'schedule_e';
    case IRPF = 'irpf';

    public function label(): string
    {
        return match ($this) {
            self::ScheduleE => 'Schedule E (Rental Property)',
            self::IRPF => 'IRPF (Spanish Income Tax)',
        };
    }

    public function country(): string
    {
        return match ($this) {
            self::ScheduleE => 'USA',
            self::IRPF => 'Spain',
        };
    }
}
