<?php
//账号登陆的db
$g_account_db = array (
		'db_ip' => '192.168.1.210',
		'db_port' => 11001,
	);

//分区列表
$zone_lists = array (
	"server_1" => array (
		'server_id'=>1,
		'name' => '内网1服',
		'state' => 0,# 0 无状态, 1 推荐, 2 爆满, 3 特殊推荐(无角色时)。至少有一个服的状态是3，人数多时1会变为2，但3保持不变
		//'domain' => 'app1000000477.t.tqapp.cn',#用来替换返给前端的online服务器的ip，内网可不配置
		//'domain_ports' => array ( 5200 => 8008 ), #端口映射关系,内网可不配置
		'ip' => '192.168.1.210',#gateway的ip 
		'port' => 5590,
		'db_ip' => '192.168.1.210',#dbproxy的ip
		'db_port' => 11001,
		'user_count' => 0,
		//'min_ver' => 20930, # 客户端版本号不低于此值的才可见
		//'max_ver' => 20930, # 客户端版本号不高于此值的才可见
		'start_time' => '2014-09-30 12:00:00', //开服时间，不配置则默认不开服
		//'test' => 0, #测试状态(默认为0): 0 正常服; 1 仅测试号可见; 2 仅测试号不可见(用于合服测试)
	),
);

return $zone_lists;
