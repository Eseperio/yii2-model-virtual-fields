<?php

$db = [
    'class' => 'yii\db\Connection',
    'dsn' => 'sqlite:' . __DIR__ . '/../runtime/test.db',
];

return [
    'id' => 'yii2-virtual-fields-test-app',
    'basePath' => dirname(__DIR__),
    'aliases' => [
        '@tests' => dirname(__DIR__),
        '@vendor' => dirname(dirname(dirname(__DIR__))) . '/vendor',
        '@eseperio/virtualfields' => dirname(dirname(dirname(__DIR__))) . '/src',
    ],
    'modules' => [
        'virtualFields' => [
            'class' => 'eseperio\virtualfields\Module',
            'entityMap' => [
                1 => 'tests\\_app\\models\\TestModel',
                2 => 'tests\\_app\\models\\Product',
            ],
        ],
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => 'test-key',
            'scriptFile' => __DIR__ . '/index.php',
            'scriptUrl' => '/index.php',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],
    ],
    'params' => [],
];
