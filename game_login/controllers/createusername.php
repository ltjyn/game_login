<?php
/**
 * 200协议
 * 创建用户&角色采用消息队列0x54321076
 * @var createusername
 */
ini_set('default_socket_timeout',1);

if (! defined ( 'BASEPATH' ) || ! defined('_SERVER_'))
    exit ( 'No direct script access allowed' );

class Createusername extends CI_Controller {
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
			$queue_data = receive_from_queue ( 0x54321076 );
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
				$channel = $queue_data['channel'];
				if (isset ( $socket_key )) {
					$ret = $this->socket_handler [$socket_key]->userinfo_register ( $queue_data ['username'], $queue_data ['passwd'], $queue_data ['email'], $queue_data ['time'], $queue_data['channel']);
					$key_name = "waitcreate_" . md5 ( $queue_data ['username'] );
					if ($ret ['result'] != 0) {
						debug ( "userinfo_register ERR err=" . $ret ['result'] . " username=" . $queue_data ['username'] );
						$this->cache->memcached->save ( $key_name, $ret, 50 );
					} else {
						debug ( "succ register user:" . "user_name=" . $queue_data ['username'] . " email=" . $queue_data ['email'] . " userid=" . $ret ['user_id'] );
						$ret_arr ['result'] = $ret ['result'];
						$ret_arr ['user_id'] = $ret ['user_id'];
						$ret_arr ['mytoken'] = $ret ['sess'];
						$input_wx = 0;
						$ret_svrs = get_svr_list ( array (), $queue_data['ver'], $queue_data['username'], $input_wx, $channel, "user_register uid=" . $ret ['user_id'], $queue_data['client_ip']);
						$ret_arr = array_merge ( $ret_arr, $ret_svrs );
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
