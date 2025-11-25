<?php

$baseConfig = require(__DIR__ . '/web.php');

$consoleConfig = [
    'id' => 'yii2-virtual-fields-console',
    'controllerNamespace' => 'tests\\_app\\commands',
    'components' => [
        'request' => [
            'class' => 'yii\console\Request',
        ],
    ],
];

// Remove web-specific components for console
unset($baseConfig['components']['request']);
unset($baseConfig['components']['errorHandler']);
unset($baseConfig['components']['urlManager']);

return yii\helpers\ArrayHelper::merge($baseConfig, $consoleConfig);

