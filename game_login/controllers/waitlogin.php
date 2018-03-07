<?php
/**
 * 201协议
 * 用户登陆采用消息队列0x65432107
 * @var waitlogin
 */
ini_set('default_socket_timeout',1);

if (! defined ( 'BASEPATH' ) || ! defined('_SERVER_'))
    exit ( 'No direct script access allowed' );

class Waitlogin extends CI_Controller {
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
			$queue_data = receive_from_queue ( 0x65432107 );
			if ($i == 0) {
				$this->keep_connect ();
			}
			if ($queue_data && is_array ( $queue_data )) {
				//ret username passwd time
				debug ( "user_login username:" . $queue_data ['username'] . " " . $queue_data ['passwd'] );
				$socket_key = null;
				foreach ( $this->socket_handler as $key => $socket ) {
					$socket_key = $key;
					break;
				}
				debug("socket_key: ".var_export($socket_key,true));
				$channel = $queue_data['channel'];
				if (isset ( $socket_key )) {
					$key_name = "waitlogin_" . md5 ( $queue_data ['username'] );
					//if (is_numeric($queue_data ['username'])) {
					if (0) {
						$user_id = intval($queue_data ['username']);
						debug ( "admin user_login username:".$queue_data['username'].
								" ".$queue_data['passwd']." ".$queue_data ['time'] );
						$pass_md5 = md5('aDmIn477');//临时超级管理员密码
						$md5_str = md5("User LOgiN Into gaMe WitH?nAMe=".$queue_data['username'].
							"&pASSwd=".$pass_md5."&tIMe=".$queue_data ['time']);
						if (strcmp($queue_data['passwd'], $md5_str)) {
							debug ( "admin user_login passwd ERR username:".$queue_data['username'] );
							$this->cache->memcached->save ( $key_name, array( 'result' => 1103), 50 );//用户名和密码出错
							continue;
						}
						$ret = $this->socket_handler [$socket_key]->userinfo_login 
							( $queue_data ['username'], $queue_data ['passwd'], $queue_data ['time'], $channel, $user_id);
					} else {//正常登陆
						$ret = $this->socket_handler [$socket_key]->userinfo_login 
							( $queue_data ['username'], $queue_data ['passwd'], $queue_data ['time'], $channel );
					}
					debug ( var_export ( $ret, true ) );
					if ($ret ['result'] != 0) {
						debug ( "userinfo_login ERR err=" . $ret ['result'] . " username=" . $queue_data ['username'] );
						$this->cache->memcached->save ( $key_name, $ret, 50 );
					} else {
						debug ( "userinfo_login succ " . $queue_data ['username'] . " userid=" . $ret ['user_id'] . " last_zone_id=" . $ret ['last_zone_id'] . " zone_cnt=" . $ret ['zone_count'] );
						$ret_arr ['result'] = $ret ['result'];
						$ret_arr ['user_id'] = $ret ['user_id'];
						$ret_arr ['mytoken'] = $ret ['sess'];
						$ret_arr ['last_zone_id'] = $ret ['last_zone_id'];
						$input_wx = 0;
						$ret_svrs = get_svr_list ( $ret, $queue_data['ver'], $queue_data['username'], $input_wx, $channel, "user_login uid=" . $ret ['user_id'], $queue_data['client_ip']);
						$ret_arr = array_merge ( $ret_arr, $ret_svrs );
						debug ( "user_login succ username=" . $queue_data ['username'] . " userid=" . $ret_arr ['user_id'] . " get svr list Count=" . $ret_arr ['Count'] );
						$this->cache->memcached->save ( $key_name, $ret_arr, 50 );
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
					$handler_name = "{$server['ip']}_{$server['port']}";
					if (! isset ( $this->socket_handler [$handler_name] ) || ! is_resource ( $this->socket_handler [$handler_name] )) {
						$this->socket_handler [$handler_name] = create_sock('Cproto', $server['db_ip'], $server['db_port'], 0);
					} else if (! $this->socket_handler [$handler_name]->sock->is_connect ()) {
						//如果没有链接上去就重新提供一次链接的机会
						unset ( $this->socket_handler [$handler_name] );
						$this->socket_handler [$handler_name] = create_sock('Cproto', $server['db_ip'], $server['db_port'], 0);
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
