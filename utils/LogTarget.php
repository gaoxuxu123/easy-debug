<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace easy\debug\utils;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;
use yii\log\FileTarget;
use yii\log\Target;

/**
 * The debug LogTarget is used to store logs for later use in the debugger tool
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class LogTarget extends FileTarget
{
    public $logFile;
    /**
     * @var bool whether log files should be rotated when they reach a certain [[maxFileSize|maximum size]].
     * Log rotation is enabled by default. This property allows you to disable it, when you have configured
     * an external tools for log rotation on your server.
     * @since 2.0.3
     */
    public $enableRotation = true;
    /**
     * @var int maximum log file size, in kilo-bytes. Defaults to 10240, meaning 10MB.
     */
    public $maxFileSize = 10240; // in KB
    /**
     * @var int number of log files used for rotation. Defaults to 5.
     */
    public $maxLogFiles = 5;
    /**
     * @var int the permission to be set for newly created log files.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * If not set, the permission will be determined by the current environment.
     */
    public $fileMode;
    /**
     * @var int the permission to be set for newly created directories.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * Defaults to 0775, meaning the directory is read-writable by owner and group,
     * but read-only for other users.
     */
    public $dirMode = 0775;
    /**
     * @var bool Whether to rotate log files by copy and truncate in contrast to rotation by
     * renaming files. Defaults to `true` to be more compatible with log tailers and is windows
     * systems which do not play well with rename on open files. Rotation by renaming however is
     * a bit faster.
     *
     * The problem with windows systems where the [rename()](http://www.php.net/manual/en/function.rename.php)
     * function does not work with files that are opened by some process is described in a
     * [comment by Martin Pelletier](http://www.php.net/manual/en/function.rename.php#102274) in
     * the PHP documentation. By setting rotateByCopy to `true` you can work
     * around this problem.
     */
    public $rotateByCopy = true;
    public function init()
    {
        if ($this->logFile === null) {
            $this->logFile = Yii::$app->getRuntimePath() . '/logs/info.log';
        } else {
            $this->logFile = Yii::getAlias($this->logFile);
        }
    }

    public function export()
    {
        $logPath = dirname($this->logFile);
        FileHelper::createDirectory($logPath, $this->dirMode, true);

        $text = [];

        $base_info = [];
        //基本信息
        //1.请求信息
        $base_info['request_info'] = date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']).' '.
             $_SERVER['SERVER_PROTOCOL'].' '
            .$_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'];
        //内存开销
        $base_info['memory'] = (round(memory_get_usage(true)/1024/1024,2)).'M';
        //sql查询次数 执行次数
        $base_info['sql_query_count'] = 0;
        $base_info['sql_execute_count'] = 0;
        //运行时间
        $diff_time = microtime(true) - $_SERVER['REQUEST_TIME'];
        $base_info['run_time'] = round($diff_time,4).'s';
        //文件加载数
        $base_info['file_require_count'] = count(get_included_files());
        //会话信息
        $base_info['session_info'] = explode(';',$_SERVER['HTTP_COOKIE'])[0];
        //当前IP
        $base_info['ip'] = Yii::$app->request->getUserIP();
        //数据表信息
        $command = \Yii::$app->db->createCommand('SHOW TABLES');
        $ret     = $command->queryAll();
        $base_info['table_num'] = count($ret);
        $base_info['driver_name'] = \Yii::$app->db->getDriverName();
        $base_info['db_version'] = \Yii::$app->db->getServerVersion();
        $base_info['server_info'] = $_SERVER['SERVER_SOFTWARE'];
        //硬盘
        $dt = round(@disk_total_space(".")/(1024*1024*1024),3); //总
        $df = round(@disk_free_space(".")/(1024*1024*1024),3); //可用
        $du = $dt-$df; //已用
        $hdPercent = (floatval($dt)!=0)?round($du/$dt*100,2):0;
        $base_info['disk_info'] = ['dt' => $dt,'df' => $df,'du' => $du,'hdPercent' => $hdPercent];
        switch(PHP_OS)
        {
            case "Linux":
                $sysReShow = (false !== ($sysInfo = $this->sys_linux()))?true:false;
                break;

            case "FreeBSD":
                $sysReShow = (false !== ($sysInfo = $this->sys_freebsd()))?true:false;
                break;

            default:
                break;
        }
        $sql_info  =[];
        //sql信息
        foreach ($this->messages as $message){

            if(in_array($message[2],['yii\db\Command::query', 'yii\db\Command::execute'])){

                $temp = [];
                $temp['file'] = $message[4][0]['file'];
                $temp['line'] = $message[4][0]['line'];
                $temp['sql'] = $message[0];
                $temp['command'] = $message[2];
                $temp['time'] = $message[3];

                $sql_info[] = $temp;
            }
            if($message[2] === 'yii\db\Command::query') {

                $base_info['sql_query_count'] ++ ;
            }
            if($message[2] === 'yii\db\Command::execute') {

                $base_info['sql_execute_count'] ++ ;
            }
        }
        $text['base_info'] = $base_info;
        $text['sql_info'] = $sql_info;
        //错误|异常
        $text = serialize($text);
        //重置文件内容
        @file_put_contents($this->logFile, '');
        #$text = implode("\n", array_map([$this, 'formatMessage'], $this->messages)) . "\n";
        if (($fp = @fopen($this->logFile, 'a')) === false) {
            throw new InvalidConfigException("Unable to append to log file: {$this->logFile}");
        }
        @flock($fp, LOCK_EX);
        if ($this->enableRotation) {
            // clear stat cache to ensure getting the real current file size and not a cached one
            // this may result in rotating twice when cached file size is used on subsequent calls
            clearstatcache();
        }
        if ($this->enableRotation && @filesize($this->logFile) > $this->maxFileSize * 1024) {
            $this->rotateFiles();
            @flock($fp, LOCK_UN);
            @fclose($fp);
            $writeResult = @file_put_contents($this->logFile, $text, FILE_APPEND | LOCK_EX);
            if ($writeResult === false) {
                $error = error_get_last();
                throw new \Exception("Unable to export log through file!: {$error['message']}");
            }
            $textSize = strlen($text);
            if ($writeResult < $textSize) {
                throw new \Exception("Unable to export whole log through file! Wrote $writeResult out of $textSize bytes.");
            }
        } else {
            $writeResult = @fwrite($fp, $text);
            if ($writeResult === false) {
                $error = error_get_last();
                throw new \Exception("Unable to export log through file!: {$error['message']}");
            }
            $textSize = strlen($text);
            if ($writeResult < $textSize) {
                throw new \Exception("Unable to export whole log through file! Wrote $writeResult out of $textSize bytes.");
            }
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
        if ($this->fileMode !== null) {
            @chmod($this->logFile, $this->fileMode);
        }
    }

    private function sys_linux()
    {
        // CPU
        if (false === ($str = @file("/proc/cpuinfo"))) return false;
        $str = implode("", $str);
        @preg_match_all("/model\s+name\s{0,}\:+\s{0,}([\w\s\)\(\@.-]+)([\r\n]+)/s", $str, $model);
        @preg_match_all("/cpu\s+MHz\s{0,}\:+\s{0,}([\d\.]+)[\r\n]+/", $str, $mhz);
        @preg_match_all("/cache\s+size\s{0,}\:+\s{0,}([\d\.]+\s{0,}[A-Z]+[\r\n]+)/", $str, $cache);
        @preg_match_all("/bogomips\s{0,}\:+\s{0,}([\d\.]+)[\r\n]+/", $str, $bogomips);
        if (false !== is_array($model[1]))
        {
            $res['cpu']['num'] = sizeof($model[1]);
            if($res['cpu']['num']==1)
                $x1 = '';
            else
                $x1 = ' ×'.$res['cpu']['num'];
            $mhz[1][0] = ' | 频率:'.$mhz[1][0];
            $cache[1][0] = ' | 二级缓存:'.$cache[1][0];
            $bogomips[1][0] = ' | Bogomips:'.$bogomips[1][0];
            $res['cpu']['model'][] = $model[1][0].$mhz[1][0].$cache[1][0].$bogomips[1][0].$x1;
            if (false !== is_array($res['cpu']['model'])) $res['cpu']['model'] = implode("<br />", $res['cpu']['model']);
            if (false !== is_array($res['cpu']['mhz'])) $res['cpu']['mhz'] = implode("<br />", $res['cpu']['mhz']);
            if (false !== is_array($res['cpu']['cache'])) $res['cpu']['cache'] = implode("<br />", $res['cpu']['cache']);
            if (false !== is_array($res['cpu']['bogomips'])) $res['cpu']['bogomips'] = implode("<br />", $res['cpu']['bogomips']);
        }

        // MEMORY
        if (false === ($str = @file("/proc/meminfo"))) return false;
        $str = implode("", $str);
        preg_match_all("/MemTotal\s{0,}\:+\s{0,}([\d\.]+).+?MemFree\s{0,}\:+\s{0,}([\d\.]+).+?Cached\s{0,}\:+\s{0,}([\d\.]+).+?SwapTotal\s{0,}\:+\s{0,}([\d\.]+).+?SwapFree\s{0,}\:+\s{0,}([\d\.]+)/s", $str, $buf);
        preg_match_all("/Buffers\s{0,}\:+\s{0,}([\d\.]+)/s", $str, $buffers);

        $res['memTotal'] = round($buf[1][0]/1024, 2);
        $res['memFree'] = round($buf[2][0]/1024, 2);
        $res['memBuffers'] = round($buffers[1][0]/1024, 2);
        $res['memCached'] = round($buf[3][0]/1024, 2);
        $res['memUsed'] = $res['memTotal']-$res['memFree'];
        $res['memPercent'] = (floatval($res['memTotal'])!=0)?round($res['memUsed']/$res['memTotal']*100,2):0;

        $res['memRealUsed'] = $res['memTotal'] - $res['memFree'] - $res['memCached'] - $res['memBuffers']; //真实内存使用
        $res['memRealFree'] = $res['memTotal'] - $res['memRealUsed']; //真实空闲
        $res['memRealPercent'] = (floatval($res['memTotal'])!=0)?round($res['memRealUsed']/$res['memTotal']*100,2):0; //真实内存使用率

        $res['memCachedPercent'] = (floatval($res['memCached'])!=0)?round($res['memCached']/$res['memTotal']*100,2):0; //Cached内存使用率

        $res['swapTotal'] = round($buf[4][0]/1024, 2);
        $res['swapFree'] = round($buf[5][0]/1024, 2);
        $res['swapUsed'] = round($res['swapTotal']-$res['swapFree'], 2);
        $res['swapPercent'] = (floatval($res['swapTotal'])!=0)?round($res['swapUsed']/$res['swapTotal']*100,2):0;

        return $res;
    }

    //FreeBSD系统探测
   private function sys_freebsd()
    {
        //CPU
        if (false === ($res['cpu']['num'] = $this->get_key("hw.ncpu"))) return false;
        $res['cpu']['model'] = $this->get_key("hw.model");
        //LOAD AVG
        if (false === ($res['loadAvg'] = $this->get_key("vm.loadavg"))) return false;
        //UPTIME
        if (false === ($buf = $this->get_key("kern.boottime"))) return false;
        $buf = explode(' ', $buf);
        $sys_ticks = time() - intval($buf[3]);
        $min = $sys_ticks / 60;
        $hours = $min / 60;
        $days = floor($hours / 24);
        $hours = floor($hours - ($days * 24));
        $min = floor($min - ($days * 60 * 24) - ($hours * 60));
        if ($days !== 0) $res['uptime'] = $days."天";
        if ($hours !== 0) $res['uptime'] .= $hours."小时";
        $res['uptime'] .= $min."分钟";
        //MEMORY
        if (false === ($buf = $this->get_key("hw.physmem"))) return false;
        $res['memTotal'] = round($buf/1024/1024, 2);

        $str = $this->get_key("vm.vmtotal");
        preg_match_all("/\nVirtual Memory[\:\s]*\(Total[\:\s]*([\d]+)K[\,\s]*Active[\:\s]*([\d]+)K\)\n/i", $str, $buff, PREG_SET_ORDER);
        preg_match_all("/\nReal Memory[\:\s]*\(Total[\:\s]*([\d]+)K[\,\s]*Active[\:\s]*([\d]+)K\)\n/i", $str, $buf, PREG_SET_ORDER);

        $res['memRealUsed'] = round($buf[0][2]/1024, 2);
        $res['memCached'] = round($buff[0][2]/1024, 2);
        $res['memUsed'] = round($buf[0][1]/1024, 2) + $res['memCached'];
        $res['memFree'] = $res['memTotal'] - $res['memUsed'];
        $res['memPercent'] = (floatval($res['memTotal'])!=0)?round($res['memUsed']/$res['memTotal']*100,2):0;

        $res['memRealPercent'] = (floatval($res['memTotal'])!=0)?round($res['memRealUsed']/$res['memTotal']*100,2):0;

        return $res;
    }
    private function get_key($keyName)
    {
        return $this->do_command('sysctl', "-n $keyName");
    }

   private function find_command($commandName)
    {
        $path = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin', '/usr/local/sbin');
        foreach($path as $p)
        {
            if (@is_executable("$p/$commandName")) return "$p/$commandName";
        }
        return false;
    }

   private function do_command($commandName, $args)
    {
        $buffer = "";
        if (false === ($command = $this->find_command($commandName))) return false;
        if ($fp = @popen("$command $args", 'r'))
        {
            while (!@feof($fp))
            {
                $buffer .= @fgets($fp, 4096);
            }
            return trim($buffer);
        }
        return false;
    }

}
