<?php

// change the following paths if necessary
$yiit='/var/www/libs/yii/framework/yii.php';
$config= '../../config/main.php';

require_once($yiit);
require_once('./WebTestCase.php');
    
Yii::createWebApplication($config);

function shutdown() {
    Yii::app()->end();
}
register_shutdown_function('shutdown');
