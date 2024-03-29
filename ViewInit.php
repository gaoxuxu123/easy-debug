<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/30
 * Time: 22:05
 */

namespace easy\debug;
use easy\debug\utils\LogTarget;
use Yii;
use yii\base\Application;
use yii\helpers\Json;
use yii\web\Response;
use yii\web\View;
use yii\helpers\Html;
use yii\helpers\Url;

use yii\base\BootstrapInterface;

class ViewInit extends \yii\base\Module implements BootstrapInterface
{

    public $controllerNamespace = 'easy\debug\controllers';
    public function init()
    {
        parent::init(); // TODO: Change the autogenerated stub
    }

    public function bootstrap($app)
    {

        $app->on(Application::EVENT_BEFORE_REQUEST, function () use ($app) {
            $app->getView()->on(View::EVENT_END_BODY, [$this, 'renderToolbar']);
        });

        $app->getUrlManager()->addRules([
            [
                'class' => 'yii\web\UrlRule',
                'route' => $this->id,
                'pattern' => $this->id,
            ],
            [
                'class' => 'yii\web\UrlRule',
                'route' => $this->id . '/<controller>/<action>',
                'pattern' => $this->id . '/<controller:[\w\-]+>/<action:[\w\-]+>',
            ]
        ], false);
    }

    public function renderToolbar($event)
    {

        $view = $event->sender;
        echo $view->renderDynamic('return Yii::$app->getModule("' . $this->id . '")->getToolbarHtml();');
        echo '<style>' . $view->renderPhpFile(__DIR__ . '/assets/toolbar.css') . '</style>';
        echo '<script>' . $view->renderPhpFile(__DIR__ . '/assets/jquery-3.2.1.min.js') . '</script>';
        echo '<script>' . $view->renderPhpFile(__DIR__ . '/assets/layer.js') . '</script>';
        echo '<script>' . $view->renderPhpFile(__DIR__ . '/assets/toolbar.js') . '</script>';
    }

    public function getToolbarHtml()
    {
        $run_time = round((microtime(true) - YII_BEGIN_TIME),2);
        $memory_use = (round(memory_get_usage(true)/1024/1024,2)).'M';
        $url = Url::toRoute(['/' . $this->id . '/default/view'
        ]);
        return '<div id="parentDIV" data-url="' . Html::encode($url) . '">
                    <div id="suspensionBox" class="scrollTop">
                        <div class="content-tips">
                            <div>内存'.$memory_use.'</div>
                            <div>耗时'.$run_time.'s</div>
                            <div>查看更多</div>
                        </div>
                    </div>
                </div>';
    }

}