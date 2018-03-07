<?php
//使用环形队列,接收并处理程序
error_reporting(E_ERROR);
if(PHP_SAPI!='cli'){
	echo("only running in shell!\n");
	exit;
}

/**
 *
 * 服务端任务调度器
 *
 */

/* 设置应用程序的配置 */
$applicationROOT = './';
define ( 'RUNLEVELS', 1024 );

set_time_limit ( 0 );

// 这里需读取配置
$selfScript = __FILE__;
$serverDir = dirname ( $selfScript );
$bin = "php";

$log_dir = 'logs/';
$log_prefix = 'daemon_';
$log_datefmt = 'Ymd';//日志文件名及目录拆分规则，YmdHis 

$sleepTimeInterval = 10;//睡眠时间间隔
$sleepTime = 0;//下次睡眠时间

$countTaskProc = unixCountProcess ($selfScript );

if ($countTaskProc > 1) {
	exit ( "task manager is already running!\n" );
}
unset ( $countTaskProc );

//建立SOCKET	长连接,根据
/*
 * 脚本文件,命令参数,启动数量
 */
$process_list = array();
$process_list[] = array('script_name'=>'serverlist.php','args'=>$serverDir . '/daemon/index.php serverlist running','max_num'=>1);
$process_list[] = array('script_name'=>'createusername.php','args'=>$serverDir . '/daemon/index.php createusername running','max_num'=>1);
$process_list[] = array('script_name'=>'waitlogin.php','args'=>$serverDir . '/daemon/index.php waitlogin running','max_num'=>1);
$process_list[] = array('script_name'=>'selectzone.php','args'=>$serverDir . '/daemon/index.php selectzone running','max_num'=>1);
$process_list[] = array('script_name'=>'createrole.php','args'=>$serverDir . '/daemon/index.php createrole running','max_num'=>1);
$process_list[] = array('script_name'=>'createbyplatform.php','args'=>$serverDir . '/daemon/index.php createbyplatform running','max_num'=>1);

$handles = array();
while(true) {
	$time_now = time();
	$dateHour0 = date($log_datefmt);
	$dateHour1 = date($log_datefmt, $time_now - $sleepTime);
	if ($dateHour0 != $dateHour1) {//是否需要重启,主要是考虑log输出,建立不同目录
		foreach ($process_list as $key=>$script_info) {
			if (isset($handles[$script_info['script_name']]) 
					&& is_array($handles[$script_info['script_name']])
					&& count($handles[$script_info['script_name']]) > 0) {
				foreach ($handles[$script_info['script_name']] as $proc) {
					debug ( 'hup stop service:' . $script_info['script_name']." proc:".var_export($proc, true) );
					proc_terminate($proc);
				}
				$handles[$script_info['script_name']] = array();
			}
		}
	}
	if ($sleepTime == 0) {//为了计算整点
		$sleepTime = $sleepTimeInterval - $time_now % $sleepTimeInterval;
	} else {
		$sleepTime = $sleepTimeInterval;
	}

	foreach ($process_list as $key=>$script_info) {
		if (file_exists($serverDir . '/controllers/' . $script_info['script_name'])) {
            $bin_path = "$bin {$script_info['args']}";
            $countTaskProc = unixCountProcess($bin_path);
            if ($countTaskProc > $script_info['max_num']) {
                //运行的个数超过就清除掉
                debug ( 'stop service:' . $script_info['script_name'] );
			    for($j = 0; $j < $countTaskProc - $script_info['max_num']; $j ++) {
				    proc_terminate ( $handles [$script_info['script_name']] [$j] );
				    array_splice ( $handles [$script_info['script_name']], $j, 1 );
			    }
    			continue;
            } else if ($countTaskProc < $script_info['max_num']) {
        		// 写入任务日志
		        $logFilePath = $log_dir . date ( $log_datefmt );
		        if (! is_dir ( $logFilePath )) {
			        $old = umask ( 0 );
			        mkdir ( $logFilePath, 0777, true );
			        umask ( $old );
		        }

                debug ( 'start to run service:' . $script_info['script_name'] . ' previous execute@' . date ( 'Y-m-d H:i:s' ) );
		        for($i = $countTaskProc; $i < $script_info['max_num']; $i ++) {
			        $logFile = $logFilePath . '/' . $log_prefix . $script_info['script_name'] . '_' . $i . '.log';
                    $command = "$bin {$script_info['args']}";
			        $descriptors = array (
			            //0 => array("pipe", "r")
			            1 => array ("file", $logFile, "a" )
			        );// stdout is a file to append to
			        $handles [$script_info['script_name']] [] = proc_open ( $command, $descriptors, $pipes );
		        }
            }
		}
    }
	// 清理已经关闭的任务
	if (is_array ( $handles ) && count ( $handles ) > 0) {
		foreach ( $handles as $script_info['script_name'] => $handleMulti ) {
			foreach ( $handleMulti as $key2 => $handle ) {
				$pstatus = proc_get_status ( $handle );
				if (! $pstatus ['running']) {
					proc_close ( $handle );
					unset ( $handles [$script_info['script_name']] [$key2] );
					if (count ( $handles [$script_info['script_name']] ) <= 0)
						unset ( $handles [$script_info['script_name']] );
				}
			}
		}
	}
    sleep($sleepTime);
}

function debug($str) {
	echo '[' . date ( 'Y-m-d H:i:s' ) . '] ' . $str . "\n";
}

/**
 * unixCountProcess
 * 获取UNIX/LINUX 下正在运行的进程数量
 */
function unixCountProcess($script) {
	$countCmd = popen ( "ps -ef | grep \"$script\" | grep -v grep | wc -l", "r" );
	$countProc = fread ( $countCmd, 512 );
	pclose ( $countCmd );
	return intval ( $countProc );
}

/**
 * unix_get_process_id 
 * 获取进程ID
 */
function unix_get_process_id ( $script , $str )
{
    exec ( "ps ef | grep -v grep | grep '$script'", $output );

    $procIds = array ();
    while ( FALSE != ($one = @each ( $output )) )
    {
    	list ( $opKey, $opItem ) = $one;
        $procId = trim(strstr( $opItem, $str , true));
        if ( $procId )
        {
            //preg_match ( "/^[^ ]+[ ]+([0-9]+).*$/", $opItem, $pregMatch );
            array_push ( $procIds, $procId );
        }
    }
    return $procIds;
}
