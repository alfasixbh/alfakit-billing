<?php

declare(strict_types=1);

return [
    'navigation' => [
        'billing' => 'Facturación',
    ],

    'checkout' => [
        'title' => 'Elija un plan',
        'subheading' => 'Elija el plan que mejor se adapte a usted. Puede cambiarlo o cancelarlo en cualquier momento.',
        'subscribe' => 'Suscribirse',
        'start_trial' => 'Iniciar prueba gratuita',
        'trial' => '{1} Prueba gratuita de :days día|[2,*] Prueba gratuita de :days días',
        'highlighted' => 'Más popular',
        'manage' => 'Gestionar la facturación',
        'price_per_interval' => ':amount :interval',
        'intervals' => [
            'day' => 'al día',
            'week' => 'a la semana',
            'month' => 'al mes',
            'year' => 'al año',
        ],
        'subscribed' => [
            'heading' => 'Tiene una suscripción activa',
            'description' => 'Cambie su plan, actualice sus métodos de pago y descargue sus facturas en el portal de facturación.',
        ],
        'empty' => [
            'heading' => 'No hay planes disponibles',
            'description' => 'Aún no se han configurado planes de facturación.',
        ],
    ],

    'paddle' => [
        'title' => 'Pago',
        'loading' => 'Abriendo el pago seguro…',
        'cancel' => 'Volver a los planes',
    ],

    'exceptions' => [
        'missing_package' => 'El proveedor de facturación :provider necesita el paquete :package. Instálelo con `composer require :package`.',
        'paddle_compatibility' => 'Nota: Cashier Paddle necesita Guzzle 7. Laravel 13 admite Guzzle 7 y 8, así que si Composer lo rechaza, ejecuta el comando con -W para cambiar a Guzzle 7.',
        'spark_licence' => 'Laravel Spark es un paquete de pago: compre una licencia en https://spark.laravel.com y siga su guía de instalación.',
        'invalid_billable' => 'El modelo facturable [:model] debe usar el trait :trait.',
        'plan_without_price' => 'El plan de facturación [:plan] no tiene precio. Defina uno con Plan::make(\':plan\')->price(\'…\').',
        'portal_unavailable' => 'No se pudo abrir el portal de facturación de :provider.',
    ],
    'unavailable' => [
        'title' => 'Los pagos no están disponibles en este momento',
        'body' => 'Vuelve a intentarlo más tarde.',
        'hint' => 'La solicitud a :provider falló. Revisa las claves de :provider en .env y los ID de precio del plan; el error está en el log.',
    ],
];
