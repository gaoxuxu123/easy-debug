<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/10
 * Time: 22:03
 */

namespace easy\debug\controllers;


use easy\debug\utils\LogTarget;
use yii\log\Logger;
use yii\log\Target;
use yii\web\Controller;

class DefaultController extends Controller
{

    public $defaultPath = '@runtime/logs';
    public function actionView()
    {

        $memory_use = (round(memory_get_usage(true)/1024/1024,2)).'M';

        $info = file_get_contents(getcwd().'/../runtime/logs/info.log');
        $info = unserialize($info);
        return $this->renderPartial('view',['memory' => $memory_use,'info' => $info]);
    }

    public function getPhpInfo()
    {
        ob_start();
        phpinfo();
        $pinfo = ob_get_contents();
        ob_end_clean();
        $phpinfo = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $pinfo);
        $phpinfo = str_replace('<table', '<div class="table-responsive"><table class="table table-condensed table-bordered table-striped table-hover config-php-info-table" ', $phpinfo);
        $phpinfo = str_replace('</table>', '</table></div>', $phpinfo);
        return $phpinfo;
    }

}