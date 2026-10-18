<?php

declare(strict_types=1);

return [
    'navigation' => [
        'billing' => 'Facturering',
    ],

    'checkout' => [
        'title' => 'Kies een abonnement',
        'subheading' => 'Kies het abonnement dat bij je past. Je kunt het op elk moment wijzigen of opzeggen.',
        'subscribe' => 'Abonneren',
        'start_trial' => 'Gratis proefperiode starten',
        'trial' => '{1} :days dag gratis proberen|[2,*] :days dagen gratis proberen',
        'highlighted' => 'Populairst',
        'manage' => 'Facturering beheren',
        'price_per_interval' => ':amount :interval',
        'intervals' => [
            'day' => 'per dag',
            'week' => 'per week',
            'month' => 'per maand',
            'year' => 'per jaar',
        ],
        'subscribed' => [
            'heading' => 'Je hebt een actief abonnement',
            'description' => 'Wijzig je abonnement, werk betaalmethoden bij en download facturen in het factureringsportaal.',
        ],
        'empty' => [
            'heading' => 'Geen abonnementen beschikbaar',
            'description' => 'Er zijn nog geen abonnementen geconfigureerd.',
        ],
    ],

    'paddle' => [
        'title' => 'Afrekenen',
        'loading' => 'Beveiligde betaalpagina wordt geopend…',
        'cancel' => 'Terug naar de abonnementen',
    ],

    'exceptions' => [
        'missing_package' => 'De factureringsprovider :provider heeft het pakket :package nodig. Installeer het met `composer require :package`.',
        'paddle_compatibility' => 'Let op: Cashier Paddle vereist Guzzle 7. Laravel 13 ondersteunt Guzzle 7 en 8; weigert Composer, voer de opdracht dan uit met -W om naar Guzzle 7 over te stappen.',
        'spark_licence' => 'Laravel Spark is een betaald pakket: koop een licentie op https://spark.laravel.com en volg de installatiehandleiding.',
        'invalid_billable' => 'Het factureerbare model [:model] moet de trait :trait gebruiken.',
        'plan_without_price' => 'Het abonnement [:plan] heeft geen prijs. Stel er een in met Plan::make(\':plan\')->price(\'…\').',
        'portal_unavailable' => 'Het factureringsportaal van :provider kon niet worden geopend.',
    ],
];
