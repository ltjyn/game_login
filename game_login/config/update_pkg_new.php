<?php
//20141030版本及以后 整包更新
$g_pkg_update = array (
	//'ver' => 25500, //更新后版本号(即客户端程序版本号)
	'opentime' => strtotime('2014-10-30 10:00:00'), 
	'tencent' => array (
		'opentime' => strtotime('2014-10-30 18:00:00'), 
		'mode' => 3, # 3 比对更新
		'url'=>'http://android.app.qq.com/myapp/detail.htm?apkName=com.tencent.tmgp.gedou',
	),
	'18420001' => array (//BAIDU_CHANNEL
		'opentime' => strtotime('2014-10-30 18:00:00'), 
		'mode' => 0, # 3 百度sdk会自动更新
		'url'=>'',
	),
	'18320001' => array (//XIAOMI_CHANNEL
		'opentime' => strtotime('2014-10-30 18:00:00'), 
		'mode' => 2, # 2 强制整包更新,需给出url
		'url'=>'http://app.mi.com/download/70226',
		#'url'=>'http://app.mi.com/detail/70226',
	),
	'200000174' => array (//LENOVO_CHANNEL
		'opentime' => strtotime('2014-10-30 18:00:00'), 
		'mode' => 2, # 2 强制整包更新,需给出url
		'url'=>'http://app.lenovo.com/appstore/psl/com.tencent.tmgp.gedou.lenovo',
	),
	'2057' => array (//UC_CHANNEL
		'opentime' => strtotime('2014-10-30 18:00:00'), 
		'mode' => 2, # 2 强制整包更新,需给出url
		'url'=>'', //临时
	),
);
?>
