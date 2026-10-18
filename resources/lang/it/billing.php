<?php

declare(strict_types=1);

return [
    'navigation' => [
        'billing' => 'Fatturazione',
    ],

    'checkout' => [
        'title' => 'Scegli un piano',
        'subheading' => 'Scegli il piano più adatto a te. Puoi cambiarlo o annullarlo in qualsiasi momento.',
        'subscribe' => 'Abbonati',
        'start_trial' => 'Inizia la prova gratuita',
        'trial' => '{1} Prova gratuita di :days giorno|[2,*] Prova gratuita di :days giorni',
        'highlighted' => 'Il più popolare',
        'manage' => 'Gestisci la fatturazione',
        'price_per_interval' => ':amount :interval',
        'intervals' => [
            'day' => 'al giorno',
            'week' => 'a settimana',
            'month' => 'al mese',
            'year' => 'all’anno',
        ],
        'subscribed' => [
            'heading' => 'Hai un abbonamento attivo',
            'description' => 'Cambia piano, aggiorna i metodi di pagamento e scarica le fatture nel portale di fatturazione.',
        ],
        'empty' => [
            'heading' => 'Nessun piano disponibile',
            'description' => 'I piani di fatturazione non sono ancora stati configurati.',
        ],
    ],

    'paddle' => [
        'title' => 'Pagamento',
        'loading' => 'Apertura del pagamento sicuro…',
        'cancel' => 'Torna ai piani',
    ],

    'exceptions' => [
        'missing_package' => 'Il provider di fatturazione :provider richiede il pacchetto :package. Installalo con `composer require :package`.',
        'paddle_compatibility' => 'Nota: Cashier Paddle richiede Guzzle 7. Laravel 13 supporta Guzzle 7 e 8, quindi se Composer rifiuta esegui il comando con -W per passare a Guzzle 7.',
        'spark_licence' => 'Laravel Spark è un pacchetto a pagamento: acquista una licenza su https://spark.laravel.com e segui la guida all’installazione.',
        'invalid_billable' => 'Il modello fatturabile [:model] deve usare il trait :trait.',
        'plan_without_price' => 'Il piano di fatturazione [:plan] non ha un prezzo. Impostalo con Plan::make(\':plan\')->price(\'…\').',
        'portal_unavailable' => 'Impossibile aprire il portale di fatturazione di :provider.',
    ],
];
