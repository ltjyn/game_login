1.��������: start_task.sh

2.������restart_task.sh

3.�رգ�stop_task.sh

4.config/path_config.php�� ������gamelogin��system�ľ���·�����£�
	$path_config['application'] = '/data/htdocs/game_login';//����Ŀ¼
	$path_config['system'] = '/data/htdocs/system';// CIϵͳ����Ŀ¼��˵���ļ�

5. config/memcached.php: ����memcache��Ϣ

6. config/zone_lists.php�����÷�����Ϣ,����:
	$zone_lists = array (
		"server_1" => array (
			'server_id'=>1,
			'name' => '��������1��',
			'state' => 0,#��æ���Ƽ�״̬: Ĭ��Ϊ0, 1 �Ƽ� 2 ��
			//'domain' => 'app1000000477.t.tqapp.cn',#�����滻����ǰ�˵�online��������ip�������ɲ�����
			'ip' => '192.168.1.210',#gateway��ip 
			'port' => 5590,
			#��proxy������proxy��ip��û����ֱ������DB svr��ip��port
			'db_ip' =>'192.168.1.210',
			'db_port' => 6500,
			'user_count' => 0,
		),
		//... �����ö��
	);

7. web�������ڣ�webroot/index.php
   nginx�������� ��Ŀ¼ ����Ϊ��webroot

8. �谲װ nginx, memcached, libmemcached, php, php-fpm

9. ��½ҳ���棺
   ·��: webroot/index.htm
   ͬʱ: controllers/index.php �� 'inform' url��ַ
