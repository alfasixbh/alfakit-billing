<?php

declare(strict_types=1);

return [
    'navigation' => [
        'billing' => 'Facturation',
    ],

    'checkout' => [
        'title' => 'Choisir une offre',
        'subheading' => 'Choisissez l’offre qui vous convient. Vous pouvez la modifier ou l’annuler à tout moment.',
        'subscribe' => 'S’abonner',
        'start_trial' => 'Commencer l’essai gratuit',
        'trial' => '{1} Essai gratuit de :days jour|[2,*] Essai gratuit de :days jours',
        'highlighted' => 'La plus populaire',
        'manage' => 'Gérer la facturation',
        'price_per_interval' => ':amount :interval',
        'intervals' => [
            'day' => 'par jour',
            'week' => 'par semaine',
            'month' => 'par mois',
            'year' => 'par an',
        ],
        'subscribed' => [
            'heading' => 'Vous avez un abonnement actif',
            'description' => 'Changez d’offre, mettez à jour vos moyens de paiement et téléchargez vos factures dans le portail de facturation.',
        ],
        'empty' => [
            'heading' => 'Aucune offre disponible',
            'description' => 'Aucune offre de facturation n’a encore été configurée.',
        ],
    ],

    'paddle' => [
        'title' => 'Paiement',
        'loading' => 'Ouverture du paiement sécurisé…',
        'cancel' => 'Retour aux offres',
    ],

    'exceptions' => [
        'missing_package' => 'Le fournisseur de facturation :provider nécessite le paquet :package. Installez-le avec `composer require :package`.',
        'paddle_compatibility' => 'Remarque : Cashier Paddle nécessite Guzzle 7. Laravel 13 prend en charge Guzzle 7 et 8 ; si Composer refuse, lancez la commande avec -W pour passer à Guzzle 7.',
        'spark_licence' => 'Laravel Spark est un paquet payant : achetez une licence sur https://spark.laravel.com et suivez son guide d’installation.',
        'invalid_billable' => 'Le modèle facturable [:model] doit utiliser le trait :trait.',
        'plan_without_price' => 'L’offre de facturation [:plan] n’a pas de prix. Définissez-en un avec Plan::make(\':plan\')->price(\'…\').',
        'portal_unavailable' => 'Le portail de facturation :provider n’a pas pu être ouvert.',
    ],
];
