<?php

namespace App\Enums\Finance;

enum RelatedPartyType: string
{
    case OwnerContribution = 'owner_contribution';
    case OwnerDraw = 'owner_draw';
    case PersonalSpending = 'personal_spending';
    case Reimbursement = 'reimbursement';

    public function label(): string
    {
        return match ($this) {
            self::OwnerContribution => 'Owner Contribution',
            self::OwnerDraw => 'Owner Draw',
            self::PersonalSpending => 'Personal Spending',
            self::Reimbursement => 'Reimbursement',
        };
    }
}
