<?php

declare(strict_types=1);

return [
    'navigation' => [
        'billing' => '請求',
    ],

    'checkout' => [
        'title' => 'プランを選択',
        'subheading' => 'ご希望に合うプランをお選びください。いつでも変更・解約できます。',
        'subscribe' => '購読する',
        'start_trial' => '無料トライアルを開始',
        'trial' => '{1} :days 日間の無料トライアル|[2,*] :days 日間の無料トライアル',
        'highlighted' => '一番人気',
        'manage' => '請求を管理',
        'price_per_interval' => ':amount :interval',
        'intervals' => [
            'day' => '/日',
            'week' => '/週',
            'month' => '/月',
            'year' => '/年',
        ],
        'subscribed' => [
            'heading' => '有効なサブスクリプションがあります',
            'description' => '請求ポータルでプランの変更、支払い方法の更新、請求書のダウンロードができます。',
        ],
        'empty' => [
            'heading' => '利用できるプランがありません',
            'description' => '請求プランはまだ設定されていません。',
        ],
    ],

    'paddle' => [
        'title' => 'お支払い',
        'loading' => '安全なお支払い画面を開いています…',
        'cancel' => 'プラン一覧に戻る',
    ],

    'exceptions' => [
        'missing_package' => '請求プロバイダー :provider には :package パッケージが必要です。`composer require :package` でインストールしてください。',
        'paddle_compatibility' => '注意: Cashier Paddle には Guzzle 7 が必要です。Laravel 13 は Guzzle 7 と 8 の両方に対応しているため、Composer が拒否した場合は -W を付けてコマンドを実行し、Guzzle 7 に切り替えてください。',
        'spark_licence' => 'Laravel Spark は有料パッケージです。https://spark.laravel.com でライセンスを購入し、インストールガイドに従ってください。',
        'invalid_billable' => '課金対象モデル [:model] は :trait トレイトを使用する必要があります。',
        'plan_without_price' => '請求プラン [:plan] に価格がありません。Plan::make(\':plan\')->price(\'…\') で設定してください。',
        'portal_unavailable' => ':provider の請求ポータルを開けませんでした。',
    ],
];
