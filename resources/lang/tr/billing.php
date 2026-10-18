<?php

declare(strict_types=1);

return [
    'navigation' => [
        'billing' => 'Faturalandırma',
    ],

    'checkout' => [
        'title' => 'Bir plan seçin',
        'subheading' => 'Size uygun planı seçin. İstediğiniz zaman değiştirebilir veya iptal edebilirsiniz.',
        'subscribe' => 'Abone ol',
        'start_trial' => 'Ücretsiz denemeyi başlat',
        'trial' => '{1} :days günlük ücretsiz deneme|[2,*] :days günlük ücretsiz deneme',
        'highlighted' => 'En popüler',
        'manage' => 'Faturalandırmayı yönet',
        'price_per_interval' => ':amount :interval',
        'intervals' => [
            'day' => 'günlük',
            'week' => 'haftalık',
            'month' => 'aylık',
            'year' => 'yıllık',
        ],
        'subscribed' => [
            'heading' => 'Etkin bir aboneliğiniz var',
            'description' => 'Faturalandırma portalında planınızı değiştirin, ödeme yöntemlerini güncelleyin ve faturaları indirin.',
        ],
        'empty' => [
            'heading' => 'Kullanılabilir plan yok',
            'description' => 'Faturalandırma planları henüz yapılandırılmadı.',
        ],
    ],

    'paddle' => [
        'title' => 'Ödeme',
        'loading' => 'Güvenli ödeme sayfası açılıyor…',
        'cancel' => 'Planlara dön',
    ],

    'exceptions' => [
        'missing_package' => ':provider faturalandırma sağlayıcısı :package paketine ihtiyaç duyar. `composer require :package` ile kurun.',
        'paddle_compatibility' => 'Not: Cashier Paddle, Guzzle 7 gerektirir. Laravel 13 hem Guzzle 7 hem 8\'i destekler; Composer reddederse Guzzle 7\'ye geçmek için komutu -W ile çalıştırın.',
        'spark_licence' => 'Laravel Spark ücretli bir pakettir: https://spark.laravel.com adresinden lisans satın alın ve kurulum kılavuzunu izleyin.',
        'invalid_billable' => 'Faturalandırılabilir model [:model], :trait trait’ini kullanmalıdır.',
        'plan_without_price' => '[:plan] faturalandırma planının fiyatı yok. Plan::make(\':plan\')->price(\'…\') ile bir fiyat belirleyin.',
        'portal_unavailable' => ':provider faturalandırma portalı açılamadı.',
    ],
];
