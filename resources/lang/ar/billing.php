<?php

declare(strict_types=1);

return [
    'navigation' => [
        'billing' => 'الفوترة',
    ],

    'checkout' => [
        'title' => 'اختر خطة',
        'subheading' => 'اختر الخطة التي تناسبك. يمكنك تغييرها أو إلغاؤها في أي وقت.',
        'subscribe' => 'اشترك',
        'start_trial' => 'ابدأ الفترة التجريبية المجانية',
        'trial' => '{1} فترة تجريبية مجانية لمدة :days يوم|[2,*] فترة تجريبية مجانية لمدة :days أيام',
        'highlighted' => 'الأكثر شيوعًا',
        'manage' => 'إدارة الفوترة',
        'price_per_interval' => ':amount :interval',
        'intervals' => [
            'day' => 'يوميًا',
            'week' => 'أسبوعيًا',
            'month' => 'شهريًا',
            'year' => 'سنويًا',
        ],
        'subscribed' => [
            'heading' => 'لديك اشتراك نشط',
            'description' => 'غيّر خطتك وحدّث طرق الدفع ونزّل الفواتير من بوابة الفوترة.',
        ],
        'empty' => [
            'heading' => 'لا توجد خطط متاحة',
            'description' => 'لم يتم إعداد خطط الفوترة بعد.',
        ],
    ],

    'paddle' => [
        'title' => 'الدفع',
        'loading' => 'جارٍ فتح صفحة الدفع الآمنة…',
        'cancel' => 'العودة إلى الخطط',
    ],

    'exceptions' => [
        'missing_package' => 'يحتاج مزود الفوترة :provider إلى الحزمة :package. ثبّتها باستخدام `composer require :package`.',
        'paddle_compatibility' => 'ملاحظة: يتطلب Cashier Paddle الإصدار 7 من Guzzle. يدعم Laravel 13 الإصدارين 7 و8، فإن رفض Composer التثبيت فشغّل الأمر مع -W للانتقال إلى Guzzle 7.',
        'spark_licence' => 'Laravel Spark حزمة مدفوعة: اشترِ ترخيصًا من https://spark.laravel.com واتبع دليل التثبيت الخاص بها.',
        'invalid_billable' => 'يجب أن يستخدم النموذج القابل للفوترة [:model] السمة :trait.',
        'plan_without_price' => 'خطة الفوترة [:plan] ليس لها سعر. حدّد سعرًا باستخدام Plan::make(\':plan\')->price(\'…\').',
        'portal_unavailable' => 'تعذر فتح بوابة الفوترة الخاصة بـ :provider.',
    ],
];
