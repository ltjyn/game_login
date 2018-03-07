<?php

require_once ("./zone_lists.php");
$out_file = fopen('./zone_lists_new.php', 'w+');

$g_account_db;//账号db配置
$zone_lists;//分区列表配置

$string = <<<'EOT'
<?php
//账号登陆的db
$g_account_db = 
EOT;

$string .= var_export($g_account_db, true);
$string .= ";\n\n";

$new_server_key = 'server_20';
$new_server_data = array (
	'server_id'=>20,
	'name' => '内网20服',
	'start_time' => '2014-09-30 12:00:00',
	'state' => 0,# 0 无状态, 1 推荐, 2 爆满, 3 特殊推荐(无角色时)。至少有一个服的状态是3，人数多时1会变为2，但3保持不变
	//'domain' => 'app1000000477.t.tqapp.cn',#用来替换返给前端的online服务器的ip，内网可不配置
	//'domain_ports' => array ( 5200 => 8008 ), #端口映射关系,内网可不配置
	'ip' => '192.168.1.210',#gateway的ip 
	'port' => 5590, 
	'db_ip' => '192.168.1.210',#dbproxy的ip
	'db_port' => 11001,
	'user_count' => 0,
	//'min_ver' => 27000, # 客户端版本号不低于此值的才可见
	//'max_ver' => 20930, # 客户端版本号不高于此值的才可见
	//'test' => 1, #0 正式服 1 仅测试号可见 2合服测试号不可见
);

$zone_lists[$new_server_key] = $new_server_data;

$string .= <<<'EOT'
//分区列表
$zone_lists = 
EOT;

$string .= var_export($zone_lists, true);
$string .= ";\n\n";

//合并后输出到文件
fwrite($out_file, $string);
