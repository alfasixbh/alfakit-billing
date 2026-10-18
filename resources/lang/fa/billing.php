<?php

declare(strict_types=1);

return [
    'navigation' => [
        'billing' => 'صورت‌حساب',
    ],

    'checkout' => [
        'title' => 'انتخاب طرح',
        'subheading' => 'طرح مناسب خود را انتخاب کنید. هر زمان می‌توانید آن را تغییر دهید یا لغو کنید.',
        'subscribe' => 'اشتراک',
        'start_trial' => 'شروع دورهٔ آزمایشی رایگان',
        'trial' => '{1} دورهٔ آزمایشی رایگان :days روزه|[2,*] دورهٔ آزمایشی رایگان :days روزه',
        'highlighted' => 'محبوب‌ترین',
        'manage' => 'مدیریت صورت‌حساب',
        'price_per_interval' => ':amount :interval',
        'intervals' => [
            'day' => 'در روز',
            'week' => 'در هفته',
            'month' => 'در ماه',
            'year' => 'در سال',
        ],
        'subscribed' => [
            'heading' => 'شما یک اشتراک فعال دارید',
            'description' => 'در درگاه صورت‌حساب، طرح خود را تغییر دهید، روش‌های پرداخت را به‌روز کنید و فاکتورها را دانلود کنید.',
        ],
        'empty' => [
            'heading' => 'طرحی در دسترس نیست',
            'description' => 'هنوز طرح صورت‌حسابی پیکربندی نشده است.',
        ],
    ],

    'paddle' => [
        'title' => 'پرداخت',
        'loading' => 'در حال باز کردن صفحهٔ پرداخت امن…',
        'cancel' => 'بازگشت به طرح‌ها',
    ],

    'exceptions' => [
        'missing_package' => 'ارائه‌دهندهٔ صورت‌حساب :provider به بستهٔ :package نیاز دارد. آن را با `composer require :package` نصب کنید.',
        'paddle_compatibility' => 'توجه: Cashier Paddle به Guzzle نسخهٔ ۷ نیاز دارد. Laravel 13 از Guzzle 7 و 8 پشتیبانی می‌کند؛ اگر Composer نپذیرفت، فرمان را با -W اجرا کنید تا Guzzle به نسخهٔ ۷ برود.',
        'spark_licence' => 'Laravel Spark یک بستهٔ پولی است: از https://spark.laravel.com مجوز بخرید و راهنمای نصب آن را دنبال کنید.',
        'invalid_billable' => 'مدل قابل صورت‌حساب [:model] باید از trait :trait استفاده کند.',
        'plan_without_price' => 'طرح صورت‌حساب [:plan] قیمت ندارد. با Plan::make(\':plan\')->price(\'…\') یک قیمت تعیین کنید.',
        'portal_unavailable' => 'درگاه صورت‌حساب :provider باز نشد.',
    ],
];
