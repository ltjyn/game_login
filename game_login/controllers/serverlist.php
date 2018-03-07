<?php
/**
 * 维护服务器列表
 * 程序每次启动后会将配置中的服务器写到缓存中去
 * 间隔多长时间检查服务器的状态信息
 * @var bin2db
 */
ini_set('default_socket_timeout',1);

if (! defined ( 'BASEPATH' ) || ! defined('_SERVER_'))
    exit ( 'No direct script access allowed' );

class Serverlist extends CI_Controller {
	private $g_zone_svr = array();//在zone_lists.php中配置
    private $socket_handler = array();//SOCKET链接池,减少重复短链接的时间消耗
    private function init() {
		$this->g_zone_svr = include(APPPATH.'config/zone_lists.php');
		$this->g_account_db = $g_account_db;//在zone_lists.php
        $this->load->driver('cache');
        $this->cache->memcached->save('server_list', $this->g_zone_svr, 20);
    }

    public function Running() {
        $this->init ();
        $i = 0;
		$sleep_time = 1;//秒数
        while ( 1 ) {
            $server_list = $this->cache->memcached->get ( 'server_list' );
            if (is_array ( $server_list )) {
                $server_list = array_merge ( $this->g_zone_svr, $server_list );
            } else {
                $server_list = $this->g_zone_svr;
            }
            foreach ( $server_list as $key => $server ) {
				//debug("server_list_one: $key= ".var_export($server, true));
                $handler_name = $server['ip'] . '_' . $server['port'];
                $result = $this->check_server_exists ( $server ['ip'], $server ['port'] );
                if (false === $result) {
                    debug("fail get zone online ip zone=$key from gw: " . $server ['ip'] . ":" . $server ['port'] . " not exists" );
					if ( !isset($server_list [$key] ['last_active_time']) 
						|| $server_list [$key] ['last_active_time'] + $sleep_time * 2 < time()) {
						unset ( $server_list [$key] );
						debug("clear dead zone=$key name={$server['name']}");
					}
                } else {
                    //debug($server['name'].':'.$server['user_count']."\n");
                    debug("succ get zone online ip zone=$key from gw: " . $server ['ip'] . ":" . $server ['port'] . 
						" cached_user_cnt=" . $server['user_count'] . " RES:" . " user_cnt=" . $result ['user_count'] .
						" online_ip_port=" . $result ['ip'] . ":" . $result ['port']);
                    $server_list [$key] ['last_active_time'] = time();
                    $server_list [$key] ['user_count'] = $result ['user_count'];
                    $server_list [$key] ['online_ip'] = $result ['ip'];
                    $server_list [$key] ['online_port'] = $result ['port'];
					//修正online的ip和port
					if ((isset($server ['domain']) && $server['domain'])) {
						$server_list [$key] ['online_ip'] = $server['domain'];
					}
                    if (isset($server ['domain_ports']) && is_array($server['domain_ports'])) {
                        $real_port = $server['domain_ports']["{$result ['port']}"];
						if (isset($real_port)) $server_list [$key] ['online_port'] = $real_port;
                    }
                }
            }
			//debug('server_list: '.var_export($server_list, true));
            $this->cache->memcached->save ( 'server_list', $server_list, 20 );
            unset ( $server_list );
            debug( "times:".$i ++ ." connect_num:". count($this->socket_handler));
            sleep ( $sleep_time );
        }
    }

    /* 返回false就是不存在,反之就是返回array(ret=>0,ip, port,user_count) */
    private function check_server_exists($ip, $port) {
        $handler_name = "{$ip}_{$port}";
        if (!isset($this->socket_handler[$handler_name]) || !is_resource($this->socket_handler[$handler_name])) {
            $this->socket_handler[$handler_name] = create_sock('Protosocket', $ip, $port, 0);
        } else if(!$this->socket_handler[$handler_name]->is_connect()) {
            //如果没有链接上去就重新提供一次链接的机会
			unset($this->socket_handler[$handler_name]);
			$this->socket_handler[$handler_name] = create_sock('Protosocket', $ip, $port, 0);
        }
        if ($this->socket_handler[$handler_name]->is_connect ()) {
            $sendbuf = @pack ( "L2SL3", 22, 0, 60101, 1, 0, 6666 );
            $recvbuf = $this->socket_handler[$handler_name]->sendmsg ( $sendbuf );
            if ($recvbuf != false) {
                $rethead = @unpack ( "Lproto_len/Lproto_id/Scommandid/Lsvrid/Lresult/Luserid", $recvbuf );
                if ($rethead ['result'] == 0) {
                    $pkg_arr = @unpack ( "Lproto_len/Lproto_id/Scommandid/Lsvrid/Lresult/Luserid" . "/Sonline_id/a16ip/Sport/Suser_count", $recvbuf );
                    debug("get zone online ip SUCC ol_id=" . $pkg_arr ['online_id'] . " user_cnt=" . $pkg_arr ['user_count'] . " ip_port=" . $pkg_arr ['ip'] . " " . $pkg_arr ['port'] );
                    return array ('ret' => 0, 'ip' => $pkg_arr ['ip'], 'port' => $pkg_arr ['port'] ,'user_count'=>$pkg_arr['user_count']);
                } else {
                    debug("get zone online ip ERR aa: err=" . $rethead ['result'] );
                }
            } else {
                debug("get zone online ip ERR bb gateway timeout" );
            }
        } else {
            //unset($this->socket_handler[$handler_name]);
            debug("get zone online ip ERR cc fail to connect gateway" );
        }

        return false;
    }
}
