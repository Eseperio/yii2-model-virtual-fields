<?php

return yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/web.php'),
    [
        'id' => 'yii2-virtual-fields-console',
        'controllerNamespace' => 'tests\\_app\\commands',
    ]
);
