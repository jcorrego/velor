<?php

return [
    'transaction_categorization_rules' => [
        [
            'pattern' => '/\\b(payment received|invoice|consulting|salary|salario|deposito)\\b/i',
            'category_name' => 'Consulting Income',
            'fields' => ['description'],
        ],
        [
            'pattern' => '/\\b(rent|rental|lease|alquiler)\\b/i',
            'category_name' => 'Rental Income',
            'fields' => ['description'],
        ],
        [
            'pattern' => '/\\b(software|subscription|saas|github|stripe|aws|adobe|google|microsoft)\\b/i',
            'category_name' => 'Software Subscriptions',
            'fields' => ['description'],
        ],
        [
            'pattern' => '/\\b(fee|charge|commission|service fee|bank fee|atm)\\b/i',
            'category_name' => 'Bank Fees',
            'fields' => ['description'],
        ],
        [
            'pattern' => '/\\b(repair|maintenance|plumbing|electric)\\b/i',
            'category_name' => 'Repairs & Maintenance',
            'fields' => ['description'],
        ],
    ],
];
