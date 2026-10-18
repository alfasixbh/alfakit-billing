<?php

declare(strict_types=1);

return [
    'navigation' => [
        'billing' => 'Оплата',
    ],

    'checkout' => [
        'title' => 'Выберите тариф',
        'subheading' => 'Выберите подходящий тариф. Его можно сменить или отменить в любое время.',
        'subscribe' => 'Оформить подписку',
        'start_trial' => 'Начать бесплатный период',
        'trial' => '{1} Бесплатный период :days день|[2,4] Бесплатный период :days дня|[5,*] Бесплатный период :days дней',
        'highlighted' => 'Самый популярный',
        'manage' => 'Управление оплатой',
        'price_per_interval' => ':amount :interval',
        'intervals' => [
            'day' => 'в день',
            'week' => 'в неделю',
            'month' => 'в месяц',
            'year' => 'в год',
        ],
        'subscribed' => [
            'heading' => 'У вас есть активная подписка',
            'description' => 'Меняйте тариф, обновляйте способы оплаты и скачивайте счета на портале оплаты.',
        ],
        'empty' => [
            'heading' => 'Нет доступных тарифов',
            'description' => 'Тарифы ещё не настроены.',
        ],
    ],

    'paddle' => [
        'title' => 'Оплата',
        'loading' => 'Открывается защищённая страница оплаты…',
        'cancel' => 'Назад к тарифам',
    ],

    'exceptions' => [
        'missing_package' => 'Для платёжного провайдера :provider нужен пакет :package. Установите его командой `composer require :package`.',
        'paddle_compatibility' => 'Примечание: Cashier Paddle требует Guzzle 7. Laravel 13 поддерживает Guzzle 7 и 8, поэтому если Composer откажет, запустите команду с -W, чтобы перейти на Guzzle 7.',
        'spark_licence' => 'Laravel Spark — платный пакет: купите лицензию на https://spark.laravel.com и следуйте инструкции по установке.',
        'invalid_billable' => 'Оплачиваемая модель [:model] должна использовать трейт :trait.',
        'plan_without_price' => 'У тарифа [:plan] нет цены. Задайте её через Plan::make(\':plan\')->price(\'…\').',
        'portal_unavailable' => 'Не удалось открыть портал оплаты :provider.',
    ],
];
