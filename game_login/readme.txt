1.开启程序: start_task.sh

2.重启：restart_task.sh

3.关闭：stop_task.sh

4.config/path_config.php： 需配置gamelogin和system的绝对路径如下：
	$path_config['application'] = '/data/htdocs/game_login';//程序目录
	$path_config['system'] = '/data/htdocs/system';// CI系统代码目录单说明文件

5. config/memcached.php: 配置memcache信息

6. config/zone_lists.php：配置分区信息,如下:
	$zone_lists = array (
		"server_1" => array (
			'server_id'=>1,
			'name' => '内网测试1服',
			'state' => 0,#繁忙或推荐状态: 默认为0, 1 推荐 2 火爆
			//'domain' => 'app1000000477.t.tqapp.cn',#用来替换返给前端的online服务器的ip，内网可不配置
			'ip' => '192.168.1.210',#gateway的ip 
			'port' => 5590,
			#有proxy则配置proxy的ip，没有则直接配置DB svr和ip和port
			'db_ip' =>'192.168.1.210',
			'db_port' => 6500,
			'user_count' => 0,
		),
		//... 可配置多个
	);

7. web服务的入口：webroot/index.php
   nginx虚拟主机 根目录 设置为：webroot

8. 需安装 nginx, memcached, libmemcached, php, php-fpm

9. 登陆页公告：
   路径: webroot/index.htm
   同时: controllers/index.php 中 'inform' url地址
