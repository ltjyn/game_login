<?php
/**
 * 203协议
 * 创建用户&角色采用消息队列0x32107654
 * @var createusername
 */
ini_set('default_socket_timeout',1);

if (! defined ( 'BASEPATH' ) || ! defined('_SERVER_'))
    exit ( 'No direct script access allowed' );

class Createrole extends CI_Controller {
	private $socket_handler = array (); //SOCKET链接池,减少重复短链接的时间消耗
	private function init() {
		$this->load->driver ( 'cache' );
	}
	
	public function Running() {
		$this->init ();
		$this->keep_connect ();
		$i = 1;
		while ( 1 ) {
			if ($i > 18000) { //180s
				$i = 0;
			}
			$queue_data = receive_from_queue ( 0x32107654 );
			if ($i == 0) {
				$this->keep_connect ();
			}
			if ($queue_data && is_array ( $queue_data )) {
				//ret username passwd time
				$socket_key = null;
				//需要保证链接到对应的服上才行
				foreach ( $this->socket_handler as $key => $socket ) {
				    $svrid = substr($key, strpos($key, ':')+1);
					if ($svrid == $queue_data['zone_id']) {
						$socket_key = $key;
						break;
					}
				}
				debug ( "user_createrole user_id:".$queue_data ['user_id']." zoneid:".$queue_data['zone_id'].
						" role:".$queue_data['role_type']." socket_key:".$socket_key);
				$key_name = "wait_createrole" . md5($queue_data['user_id'].'-'.$queue_data['zone_id'].'-'.$queue_data['role_type']);
				if (isset ( $socket_key )) {
					$check_same = $this->socket_handler[$socket_key]->user_check_same_role_nick($queue_data['user_id'], $queue_data['role_nick']);
					if ($check_same['is_find'] == 1) { //角色名已经被使用
						debug ( "create_role nick used ERR zone_id=" . $queue_data['zone_id'] . " userid=" . $queue_data['user_id'] . " nick=" . $queue_data['role_nick']);
						$this->cache->memcached->save ( $key_name, array ('result' => 1112 ), 50 );
						continue;
					}

					$invite_code = 0;
					$ret = $this->socket_handler[$socket_key]->user_create_role_all($queue_data['user_id'], 
							$queue_data['role_nick'], $queue_data['role_type'], $invite_code, $queue_data['zone_id']);
					if ($ret['result'] != 0) {
						debug ("create_role_all ERR ret=" . $ret ['result'] ." zone_id=".$queue_data['zone_id'].
								" userid=" . $queue_data ['user_id'] . " nick=" . $queue_data['role_nick']);
						$this->cache->memcached->save($key_name, $ret, 50 );
						continue;
					} else {
						debug ("create_role_all succ zone_id=".$queue_data['zone_id'].
								" userid=" . $queue_data ['user_id'] . " nick=" . $queue_data['role_nick']);
						$ret_arr ['commandid'] = $queue_data ['cmdid'];
						$ret_arr ['result'] = 0;
						$ret_arr ['userid'] = $ret ['userid'];
						$ret_arr ['user_id'] = $ret ['userid'];
						$ret_arr ['role_type'] = $queue_data ['role_type'];
						$this->cache->memcached->save ( $key_name, $ret_arr, 50 );
						continue;
					}
					//临时处理,所有创建操作在一条db里进行
					continue;

					//去USERINFO库检查玩家在这个服上是否已经创建过角色
					$ret = $this->socket_handler[$socket_key]->userinfo_select_zone ( $queue_data ['user_id'], $queue_data ['zone_id'] );
					if ($ret ['result'] != 0) {
						debug ("create_role userinfo_select_zone ERR ret=" . $ret ['result'] . " zone_id=".$queue_data['zone_id']." userid=" . $queue_data ['user_id'] );
						$this->cache->memcached->save($key_name, $ret, 50 );continue;
					} else {
						$userid = 0;
						if ($ret ['user_id'] == 0) {
							$userid = $queue_data ['user_id'];
						} else { //已经创建过角色
							debug("create_role already created ERR zone_id=".$queue_data['user_id']." userid=" . $queue_data ['user_id'] );
							$this->cache->memcached->save($key_name, array ('result' => 1113), 50 );continue;
						}
						//检查角色名是否可用
						$check_same = $this->socket_handler [$socket_key]->user_check_same_role_nick ( $userid, $queue_data ['role_nick'] );
						if ($check_same ['is_find'] == 1) { //角色名已经被使用
							debug ( "create_role nick used ERR zone_id=" . $queue_data ['zone_id'] . " userid=" . $queue_data ['user_id'] . " nick=" . $queue_data ['role_nick'] );
							$this->cache->memcached->save ( $key_name, array ('result' => 1112 ), 50 );
							continue;
						}
						//去USER库创建
						$user_ret = $this->socket_handler [$socket_key]->user_create_role ( $userid, $queue_data ['role_type'], $queue_data ['role_nick'], $queue_data['zone_id']);
						if ($user_ret ['result'] != 0) {
							debug ( "create_role user_create_role ERR ret=" . $user_ret ['result'] . " zone_id=" . $queue_data ['zone_id'] . " userid=" . $queue_data ['user_id'] );
							$this->cache->memcached->save ( $key_name, $user_ret, 50 );
							continue;
						}
						debug ( "create_role step1 user_create_role succ zone_id=" . $queue_data ['zone_id'] . " userid=" . $queue_data ['user_id'] );
						$userinfo_ret = $this->socket_handler [$socket_key]->userinfo_create_role ( $userid, $queue_data ['role_type'], $queue_data ['zone_id'] );
						if ($userinfo_ret ['result'] != 0) {
							debug ( "create_role userinfo_create_role ERR ret=" . $userinfo_ret ['result'] . " zone_id=" . $queue_data ['zone_id'] . " userid=" . $queue_data ['user_id'] );
							$this->cache->memcached->save ( $key_name, $userinfo_ret, 50 );
							continue;
						}
						debug ( "create_role step2 userinfo_create_role succ zone_id=" . $queue_data ['zone_id'] . " userid=" . $queue_data ['user_id'] );
						
						$global_info_ret = $this->socket_handler [$socket_key]->global_user_info_create_role ( $userid, $queue_data ['role_type'], $queue_data ['role_nick'] );
						if ($global_info_ret ['result'] != 0) {
							debug ( "create_role global_user_info_create_role ERR ret=" . $global_info_ret ['result'] . " zone_id=" . $queue_data ['zone_id'] . " userid=" . $queue_data ['user_id'] );
							$this->cache->memcached->save ( $key_name, $global_info_ret, 50 );
							continue;
						} else {
							debug ( "create_role step3 global_user_info_create_role succ zone_id=" . $queue_data ['zone_id'] . " userid=" . $queue_data ['user_id'] );
							$ret_arr ['commandid'] = $queue_data ['cmdid'];
							$ret_arr ['result'] = 0;
							$ret_arr ['userid'] = $user_ret ['userid'];
							$ret_arr ['user_id'] = $user_ret ['userid'];
							$ret_arr ['role_type'] = $queue_data ['role_type'];
							$this->cache->memcached->save ( $key_name, $ret_arr, 50 );
							continue;
						}			
					}
				}
			}
			$i ++;
			usleep ( 10000 ); //0.01s
		}
	}
	
	private function keep_connect() {
		//保持连接
		$server_list = $this->cache->memcached->get ( 'server_list' );
		if ($server_list) {
			foreach ( $server_list as $key => $server ) {
				if (isset ( $server ['db_ip'] ) && isset ( $server ['db_port'] )) {
					$handler_name = "{$server['ip']}_{$server['port']}:{$server['server_id']}";
					if (! isset ( $this->socket_handler [$handler_name] ) || ! is_resource ( $this->socket_handler [$handler_name] )) {
						$this->socket_handler [$handler_name] = create_sock('Cproto', $server['db_ip'], $server['db_port'], $server['server_id']);
					} else if (! $this->socket_handler [$handler_name]->sock->is_connect ()) {
						//如果没有链接上去就重新提供一次链接的机会
						unset ( $this->socket_handler [$handler_name] );
						$this->socket_handler [$handler_name] = create_sock('Cproto', $server['db_ip'], $server['db_port'], $server['server_id']);
					}
					if (! $this->socket_handler [$handler_name]->sock->is_connect ()) {
						//unset ( $this->socket_handler [$handler_name] );
						debug ( "fail to connect dbserv ".$server ['db_ip'].":".$server ['db_port'] );
					} else {
						debug ( "succ connect dbserv ".$server ['db_ip'].":".$server ['db_port'] );
					}
				}
			}
		}
	}
}
