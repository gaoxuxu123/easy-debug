##安装

```

composer require yii2-showsql/showsql

或者在composer.json中加入

 "require": {

        "yii2-showsql": "dev-master"
}

```
更新依赖 ``` composer update ```

##使用说明

##DEMO

```

'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                [
                    //日志记录方式 加入这段配置
                    'class' => 'easy\debug\utils\LogTarget',
                    'levels' => ['info','error', 'warning'],//日志等级
                    'logVars' =>['_GET', '_POST', '_FILES', '_COOKIE', '_SESSION','_SERVER'],//被收集记录的额外数据如 'logVars' => ['_GET', '_POST', '_FILES', '_COOKIE', '_SESSION','_SERVER'],
                    //指定日志目录
                    'logFile' => '@app/runtime/logs/info.log',
                ],
            ],
        ],

```

