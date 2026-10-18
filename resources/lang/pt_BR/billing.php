<?php

declare(strict_types=1);

return [
    'navigation' => [
        'billing' => 'Faturamento',
    ],

    'checkout' => [
        'title' => 'Escolha um plano',
        'subheading' => 'Escolha o plano ideal para você. Você pode alterá-lo ou cancelá-lo a qualquer momento.',
        'subscribe' => 'Assinar',
        'start_trial' => 'Iniciar teste grátis',
        'trial' => '{1} Teste grátis de :days dia|[2,*] Teste grátis de :days dias',
        'highlighted' => 'Mais popular',
        'manage' => 'Gerenciar faturamento',
        'price_per_interval' => ':amount :interval',
        'intervals' => [
            'day' => 'por dia',
            'week' => 'por semana',
            'month' => 'por mês',
            'year' => 'por ano',
        ],
        'subscribed' => [
            'heading' => 'Você tem uma assinatura ativa',
            'description' => 'Altere seu plano, atualize as formas de pagamento e baixe faturas no portal de faturamento.',
        ],
        'empty' => [
            'heading' => 'Nenhum plano disponível',
            'description' => 'Os planos de faturamento ainda não foram configurados.',
        ],
    ],

    'paddle' => [
        'title' => 'Pagamento',
        'loading' => 'Abrindo o pagamento seguro…',
        'cancel' => 'Voltar aos planos',
    ],

    'exceptions' => [
        'missing_package' => 'O provedor de faturamento :provider precisa do pacote :package. Instale-o com `composer require :package`.',
        'paddle_compatibility' => 'Observação: o Cashier Paddle precisa do Guzzle 7. O Laravel 13 aceita o Guzzle 7 e 8; se o Composer recusar, execute o comando com -W para mudar para o Guzzle 7.',
        'spark_licence' => 'O Laravel Spark é um pacote pago: compre uma licença em https://spark.laravel.com e siga o guia de instalação.',
        'invalid_billable' => 'O model faturável [:model] deve usar o trait :trait.',
        'plan_without_price' => 'O plano de faturamento [:plan] não tem preço. Defina um com Plan::make(\':plan\')->price(\'…\').',
        'portal_unavailable' => 'Não foi possível abrir o portal de faturamento do :provider.',
    ],
];
