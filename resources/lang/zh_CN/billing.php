<?php

declare(strict_types=1);

return [
    'navigation' => [
        'billing' => '账单',
    ],

    'checkout' => [
        'title' => '选择方案',
        'subheading' => '选择适合您的方案。您可以随时更改或取消。',
        'subscribe' => '订阅',
        'start_trial' => '开始免费试用',
        'trial' => '{1} :days 天免费试用|[2,*] :days 天免费试用',
        'highlighted' => '最受欢迎',
        'manage' => '管理账单',
        'price_per_interval' => ':amount :interval',
        'intervals' => [
            'day' => '每天',
            'week' => '每周',
            'month' => '每月',
            'year' => '每年',
        ],
        'subscribed' => [
            'heading' => '您有一个有效的订阅',
            'description' => '在账单门户中更改方案、更新付款方式并下载发票。',
        ],
        'empty' => [
            'heading' => '暂无可用方案',
            'description' => '尚未配置账单方案。',
        ],
    ],

    'paddle' => [
        'title' => '结账',
        'loading' => '正在打开安全结账页面…',
        'cancel' => '返回方案列表',
    ],

    'exceptions' => [
        'missing_package' => '账单提供商 :provider 需要 :package 包。请使用 `composer require :package` 安装。',
        'paddle_compatibility' => '注意：Cashier Paddle 需要 Guzzle 7。Laravel 13 同时支持 Guzzle 7 和 8，如果 Composer 拒绝安装，请加上 -W 运行命令以切换到 Guzzle 7。',
        'spark_licence' => 'Laravel Spark 是付费包：请在 https://spark.laravel.com 购买许可证并按照其安装指南操作。',
        'invalid_billable' => '计费模型 [:model] 必须使用 :trait trait。',
        'plan_without_price' => '账单方案 [:plan] 没有价格。请使用 Plan::make(\':plan\')->price(\'…\') 设置价格。',
        'portal_unavailable' => '无法打开 :provider 账单门户。',
    ],
];
