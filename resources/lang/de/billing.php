<?php

declare(strict_types=1);

return [
    'navigation' => [
        'billing' => 'Abrechnung',
    ],

    'checkout' => [
        'title' => 'Tarif wählen',
        'subheading' => 'Wählen Sie den passenden Tarif. Sie können ihn jederzeit ändern oder kündigen.',
        'subscribe' => 'Abonnieren',
        'start_trial' => 'Kostenlos testen',
        'trial' => '{1} :days Tag kostenlos testen|[2,*] :days Tage kostenlos testen',
        'highlighted' => 'Am beliebtesten',
        'manage' => 'Abrechnung verwalten',
        'price_per_interval' => ':amount :interval',
        'intervals' => [
            'day' => 'pro Tag',
            'week' => 'pro Woche',
            'month' => 'pro Monat',
            'year' => 'pro Jahr',
        ],
        'subscribed' => [
            'heading' => 'Sie haben ein aktives Abonnement',
            'description' => 'Ändern Sie Ihren Tarif, aktualisieren Sie Zahlungsmethoden und laden Sie Rechnungen im Abrechnungsportal herunter.',
        ],
        'empty' => [
            'heading' => 'Keine Tarife verfügbar',
            'description' => 'Es wurden noch keine Tarife konfiguriert.',
        ],
    ],

    'paddle' => [
        'title' => 'Bezahlung',
        'loading' => 'Sichere Bezahlseite wird geöffnet…',
        'cancel' => 'Zurück zu den Tarifen',
    ],

    'exceptions' => [
        'missing_package' => 'Der Abrechnungsanbieter :provider benötigt das Paket :package. Installieren Sie es mit `composer require :package`.',
        'paddle_compatibility' => 'Hinweis: Cashier Paddle benötigt Guzzle 7. Laravel 13 unterstützt Guzzle 7 und 8. Falls Composer ablehnt, führen Sie den Befehl mit -W aus, um auf Guzzle 7 zu wechseln.',
        'spark_licence' => 'Laravel Spark ist ein kostenpflichtiges Paket: Erwerben Sie eine Lizenz unter https://spark.laravel.com und folgen Sie der Installationsanleitung.',
        'invalid_billable' => 'Das abrechenbare Model [:model] muss den Trait :trait verwenden.',
        'plan_without_price' => 'Der Tarif [:plan] hat keinen Preis. Legen Sie einen mit Plan::make(\':plan\')->price(\'…\') fest.',
        'portal_unavailable' => 'Das Abrechnungsportal von :provider konnte nicht geöffnet werden.',
    ],
    'unavailable' => [
        'title' => 'Zahlungen sind gerade nicht verfügbar',
        'body' => 'Bitte versuchen Sie es später erneut.',
        'hint' => 'Die Anfrage an :provider ist fehlgeschlagen. Prüfen Sie die :provider-Schlüssel in .env und die Preis-IDs des Plans; der Fehler steht im Log.',
    ],
];
