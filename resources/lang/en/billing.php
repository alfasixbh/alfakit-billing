<?php

declare(strict_types=1);

return [
    'navigation' => [
        'billing' => 'Billing',
    ],

    'checkout' => [
        'title' => 'Choose a plan',
        'subheading' => 'Pick the plan that fits you. You can change or cancel it at any time.',
        'subscribe' => 'Subscribe',
        'start_trial' => 'Start free trial',
        'trial' => '{1} :days-day free trial|[2,*] :days-day free trial',
        'highlighted' => 'Most popular',
        'manage' => 'Manage billing',
        'price_per_interval' => ':amount :interval',
        'intervals' => [
            'day' => 'per day',
            'week' => 'per week',
            'month' => 'per month',
            'year' => 'per year',
        ],
        'subscribed' => [
            'heading' => 'You have an active subscription',
            'description' => 'Change your plan, update payment methods and download invoices in the billing portal.',
        ],
        'empty' => [
            'heading' => 'No plans available',
            'description' => 'Billing plans have not been configured yet.',
        ],
    ],

    'paddle' => [
        'title' => 'Checkout',
        'loading' => 'Opening the secure checkout…',
        'cancel' => 'Back to the plans',
    ],

    'exceptions' => [
        'missing_package' => 'The :provider billing provider needs the :package package. Install it with `composer require :package`.',
        'paddle_compatibility' => 'Note: Cashier Paddle needs Guzzle 7. Laravel 13 supports Guzzle 7 and 8, so if Composer refuses, run the command with -W to switch Guzzle to version 7.',
        'spark_licence' => 'Laravel Spark is a paid package: buy a licence at https://spark.laravel.com and follow its installation guide.',
        'invalid_billable' => 'The billable model [:model] must use the :trait trait.',
        'plan_without_price' => 'The billing plan [:plan] has no price. Set one with Plan::make(\':plan\')->price(\'…\').',
        'portal_unavailable' => 'The :provider billing portal could not be opened.',
    ],
];
