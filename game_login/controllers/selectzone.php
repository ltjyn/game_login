<?php
/**
 * 202协议
 * 角色采用消息队列0x43210765
 * @var createusername
 */
ini_set('default_socket_timeout',1);

if (! defined ( 'BASEPATH' ) || ! defined('_SERVER_'))
    exit ( 'No direct script access allowed' );

class Selectzone extends CI_Controller {
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
			$queue_data = receive_from_queue ( 0x43210765 );
			if ($i == 0) {
				$this->keep_connect ();
			}
			//var_export($queue_data,true);
			if ($queue_data && is_array ( $queue_data )) {
				$socket_key = null;
				foreach ( $this->socket_handler as $key => $socket ) {
					$svrid = substr($key, strpos($key, ':')+1);
				    //if ($svrid == $queue_data['zone_id'] && $socket->sock->is_connect ()) {
				    if ($svrid == $queue_data['zone_id']) {
						$socket_key = $key;
						break;
					}
				}
				debug("wait_selectzone userid ".$queue_data['user_id']." zoneid ".$queue_data['zone_id'].
						" socket_key: ".var_export($socket_key,true));
				if (isset ( $socket_key )) {
					$retA = $this->socket_handler [$socket_key]->userinfo_select_zone($queue_data['user_id'], $queue_data['zone_id']);
					$ret_arr ['result'] = $retA ['result'];
					$ret_arr ['session'] = "1234567890";
					$ret_arr ['role_count'] = 0;
					
					$role_count = 0;	
					$key_name = "wait_selectzone" . md5 ( $queue_data ['user_id'].'-'.$queue_data['zone_id'] );
					if ($retA ['result'] != 0) {
						debug ( "select_zone userinfo_select_zone ERR ret=" . $retA ['result'] . " userid=" . $queue_data ['user_id'] );
						$this->cache->memcached->save ( $key_name, $retA, 50 );
					} else {
						if ($retA ['user_id'] != 0) {
							$ret_arr ['userid_' . $role_count] = $retA ['user_id'];
							$user_ret = $this->socket_handler [$socket_key]->user_get_base_info ( $queue_data ['user_id'] );
							$ret_arr ['nick_' . $role_count] = $user_ret ['nick'];
							$ret_arr ['role_type_' . $role_count] = $user_ret ['role_type'];
							$ret_arr ['level' . $role_count] = $user_ret ['exp'];
							$role_count ++;
						}
						$ret_arr ['role_count'] = $role_count;
						debug ( "select_zone userinfo_select_zone succ userid=".$queue_data['user_id']." zone_id=".$queue_data['zone_id']);
						$ret_arr ['result'] = $retA ['result'];
						$ret_arr ['user_id'] = $retA ['user_id'];
						$input_wx = isset($queue_data['wx']) ? $queue_data['wx'] : 0;
						$wx = ($input_wx == 1) ? 1 : 0;
						$channel = "";
						$ret_svrs = get_svr_list ( array (), $queue_data['ver'], "selc_zone", $wx, $channel, "user_login uid=" . $retA ['user_id'], $queue_data['client_ip']);
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
					$handler_name = "{$server['ip']}_{$server['port']}:{$server['server_id']}";
					if (! isset ( $this->socket_handler [$handler_name] ) || ! is_resource ( $this->socket_handler [$handler_name] )) {					
						$this->socket_handler [$handler_name] = create_sock('Cproto', $server ['db_ip'], $server ['db_port'], $server['server_id']);
                    } else if (! $this->socket_handler [$handler_name]->sock->is_connect ()) {
                        //如果没有链接上去就重新提供一次链接的机会
                        unset ( $this->socket_handler [$handler_name] );
                        $this->socket_handler [$handler_name] = create_sock ( 'Cproto', $server ['db_ip'], $server ['db_port'], $server ['server_id'] );
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
