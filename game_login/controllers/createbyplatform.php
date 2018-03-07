<?php
/**
 * 通过平台过来的创建用户采用消息队列0x21076543
 * @var createusername
 */
ini_set('default_socket_timeout',1);

if (! defined ( 'BASEPATH' ) || ! defined('_SERVER_'))
    exit ( 'No direct script access allowed' );

class Createbyplatform extends CI_Controller {
	private $socket_handler = array (); //SOCKET链接池,减少重复短链接的时间消耗
	private function init() {
		$this->load->driver ( 'cache' );
		include(APPPATH.'config/zone_lists.php');
		$this->g_account_db = $g_account_db;
	}
	
	public function Running() {
		$this->init ();
		$this->keep_connect ();
		$i = 1;
		while ( 1 ) {
			if ($i > 18000) { //180s
				$i = 0;
			}
			$queue_data = receive_from_queue ( 0x21076543 );
			if ($i == 0) {
				$this->keep_connect ();
			}
			if ($queue_data && is_array ( $queue_data )) {
				//ret username passwd time
				$socket_key = null;
				foreach ( $this->socket_handler as $key => $socket ) {
					$socket_key = $key;//取首个
					break;
				}
				debug ( "wait_createbyplatform username:".$queue_data ['username']." socket_key:".$socket_key);
				$key_name = "wait_createbyplatform" . $queue_data['username'];
				$is_ios = (isset($queue_data ['os']) && $queue_data ['os']) ? 1 : 0;
				$input_wx = isset($queue_data['wx']) ? $queue_data['wx'] : 0;

				$status_key_name = "status_205_" . $queue_data['username'];
				$status_ret = $this->cache->memcached->get($status_key_name);
				if (!$status_ret || !isset($status_ret['data'])) {
					debug("platfrom_user ERR no data found username=" . $queue_data ['username'] );
					continue;//do nothing
				}
				$queue_data = $status_ret['data'];
				$is_ios = (isset($queue_data ['os']) && $queue_data ['os']) ? 1 : 0;
				$input_wx = isset($queue_data['wx']) ? $queue_data['wx'] : 0;

				$channel = $queue_data['channel'];
				if ($channel == XIAOMI_CHANNEL || $channel == BAIDU_CHANNEL || $channel == KAKAO_CHANNEL
					|| $channel == TBT_CHANNEL || $channel == BD91_CHANNEL || $channel == ANZHI_CHANNEL 
					|| $channel == HUAWEI_CHANNEL || $channel == XM_CHANNEL || $channel == BD_CHANNEL
					|| $channel == ITOOLS_CHANNEL) {
					//need check session, but do nothing now.
				} else if ($channel == LENOVO_CHANNEL || $channel == UC_CHANNEL || $channel == MZW_CHANNEL
						|| $channel == Q360_CHANNEL || $channel == OPPO_CHANNEL || $channel == KY_CHANNEL 
						|| $channel == JINLI_CHANNEL || $channel == PP_CHANNEL || $channel == WDJ_CHANNEL
						|| $channel == I4_CHANNEL || $channel == NDUO_CHANNEL || $channel == XY_CHANNEL
						|| $channel == LX_CHANNEL || $channel == AIBEI_CHANNEL || $channel == DANGLE_CHANNEL
						|| $channel == IDANGLE_CHANNEL || $channel == VIVO_CHANNEL || $channel == SHUYOU_CHANNEL
						|| $channel == YYH_CHANNEL || $channel == AI4399_CHANNEL || $channel == JIFENG_CHANNEL
						|| $channel == PPW_CHANNEL) {
					//联想、UC、拇指玩渠道在进入队列前就已经做了验证
				} else if ($channel == APPLE_CHANNEL) {
					//appstore官方渠道快速进入模式, 暂不做校验
				} else {
					//校验登陆 手Q or 微信
					//$auth_uri = ($input_wx != 1) ? '/auth/verify_login' : '/auth/check_token';
					if ($input_wx == 1) {
						$auth_uri = '/auth/check_token';
						$appkey = "fdaf3e74a01096b4b7faa83a4241d2b9";
						$appid = "wx2589889e490861aa";
						$auth_post_params = array ( 
							"openid" => $queue_data ['username'], 
							"accessToken" => $queue_data ['openkey'],
						);
					} else if ($input_wx == 5) {
						$auth_uri = '/auth/guest_check_token';
						$appkey = GUEST_APPKEY_TENCENT;
						$appid = GUEST_APPID_TENCENT;
						$auth_post_params = array ( 
							"guestid" => $queue_data ['username'], 
							"accessToken" => $queue_data ['openkey'],
						);
					} else {
						$auth_uri = '/auth/verify_login';
						$appkey = APPKEY_TENCENT;
						$appid = intval(APPID_TENCENT);
						$auth_post_params = array (
							"appid" => $appid,
							"openid" => $queue_data ['username'], 
							"openkey" => $queue_data ['openkey'],
							"userip" => $queue_data ['userip'],
						);
					}

					$time_now = time();
					$auth_get_params = array (
						"appid" => $appid,
						"encode" => 1,
						"timestamp" => $time_now,
						"openid" => $queue_data ['username'],
						"sig" => md5($appkey.$time_now),
					);

					$auth_post_data = json_encode($auth_post_params);
					$response_data = request_by_curl(TENCENT_SDK_URL.$auth_uri.'/', $auth_get_params, $auth_post_data);
					$response_val = json_decode($response_data, true);
					if (!isset($response_val['ret']) || $response_val['ret']) {
						debug("platfrom_user ERR login_chk wx=$input_wx username=" . $queue_data ['username'] . 
								" res:" . var_export($response_data, true));
						$ret_arr = array ( 'result' => 10005);//登陆session出错(支付session出错)
						//$this->cache->memcached->save($key_name, $ret_arr, 50 );continue;
					}

					//拉取QQ好友
					if (($input_wx == 0) && $queue_data['username'] == "AA8037540B10A3534C2ABEB3116A6FEB") {
						$auth_uri = '/relation/qqfriends_detail';
						$time_now = time();
						$appkey = APPKEY_TENCENT;
						$appid = intval(APPID_TENCENT);
						$auth_get_params = array (
							"appid" => $appid,
							"encode" => 1,
							"timestamp" => $time_now,
							"openid" => $queue_data ['username'],
							"sig" => md5($appkey.$time_now),
						);
						$auth_post_params = array (
							"appid" => $appid,
							"openid" => $queue_data ['username'], 
							"accessToken" => $queue_data ['openkey'],
							"flag" => 2,//1 不包括自己，2包括自己
							);
						$auth_post_data = json_encode($auth_post_params);
						$response_data = request_by_curl(TENCENT_SDK_URL.$auth_uri.'/', $auth_get_params, $auth_post_data);
						debug("platfrom_user get_friends username=".$queue_data ['username']." res:".var_export($response_data, true));
					}

					/*if ($input_wx) {//微信好友列表
						$auth_uri = '/relation/wxfriends_profile';
						$response_data = request_by_curl(TENCENT_SDK_URL.$auth_uri.'/', $auth_get_params, $auth_post_data);
						debug("platfrom_user get_friends wx username=".$queue_data ['username']." res:".var_export($response_data, true));
					}*/

					//校验pay_token
					$os_plat = $is_ios ? "IOS" : "ANDR";
					$pay_url = $is_ios ? IOS_PAY_URL : ANDR_PAY_URL;
					$http_url = $pay_url."?c=index&m=check_get_balance";
					//$http_url = $pay_url;
					$pay_params = array (
							'time' => time(0),
							'openid' => $queue_data ['username'],
							'openkey' => $queue_data ['openkey'],
							'pay_token' => $queue_data ['pay_token'],
							'pfkey' => $queue_data ['pfkey'],
							'pf' => $queue_data ['pf'],
							'wx' => $input_wx,
							);
					$response_data = request_by_curl($http_url, $pay_params);
					$response_val = json_decode($response_data, true);
					if (!isset($response_val['result']) || $response_val['result']) {
						debug("platfrom_user ERR pay_chk $os_plat username=" . $queue_data ['username'] . 
								" res:" . var_export($response_data, true));
						$ret_arr = array ( 'result' => 10005);//登陆session出错(支付session出错)
						//$this->cache->memcached->save($key_name, $ret_arr, 50 );continue;
					}
				}

				if (isset ( $socket_key )) {
					//去USERINFO库检查玩家在这个服上是否已经创建过角色
					$ret = $this->socket_handler[$socket_key]->userinfo_add_userid_by_username_new ( $queue_data ['username'], $channel );
					if ($ret ['result'] != 0) {
						debug("platfrom_user get_userid_by_username ERR ret=" . $ret ['result'] . " username=" . $queue_data ['username'] );
						$this->cache->memcached->save($key_name, $ret, 50 );continue;
					} else {
						debug("platfrom_user get_userid_by_username succ username=" . $queue_data ['username'] . " userid=" . $ret ['user_id'] . " is_new=" . $ret ['is_new'] . " weixin=" . $input_wx);
						//用户登录
						$ret_arr ['is_new'] = $ret['is_new'];
						$ret_arr ['result'] = $ret ['result'];
						$ret_arr ['user_id'] = $ret ['user_id'];
						$ret_arr ['mytoken'] = $ret ['sess'];
						$ret_arr ['last_zone_id'] = $ret ['last_zone_id'];
						$wx = ($input_wx == 1) ? 1 : 0;
						$ret_svrs = get_svr_list ( $ret, $queue_data['ver'], $queue_data['username'], $wx, $channel, "platform_user uid=" . $ret ['user_id'], $queue_data['client_ip']);
						$ret_arr = array_merge ( $ret_arr, $ret_svrs );
						
						debug(json_encode ( $ret_arr ));
						//if ($ret ['is_new'] == 1) {//直接在DB落统计项
						//	sendtoqueue ( $ret_arr ['user_id'], 1, 1 );
						//}
						$this->cache->memcached->save($key_name, $ret_arr, 50 );continue;
					}
				}
			}
			$i ++;
			usleep ( 10000 ); //0.01s
		}
	}
	
	private function keep_connect() {
		//保持连接
		$server = $this->g_account_db;
		if ($server) {
			if (isset ( $server ['db_ip'] ) && isset ( $server ['db_port'] )) {
				$handler_name = "{$server['db_ip']}_{$server['db_port']}";
				if (! isset ( $this->socket_handler [$handler_name] ) || ! is_resource ( $this->socket_handler [$handler_name] )) {
					$this->socket_handler [$handler_name] = create_sock('Cproto', $server ['db_ip'], $server ['db_port']);
				} else if (! $this->socket_handler [$handler_name]->sock->is_connect ()) {
					//如果没有链接上去就重新提供一次链接的机会
					unset ( $this->socket_handler [$handler_name] );
					$this->socket_handler [$handler_name] = create_sock('Cproto', $server ['db_ip'], $server ['db_port']);
				}
				if (! $this->socket_handler [$handler_name]->sock->is_connect ()) {
					//unset ( $this->socket_handler [$handler_name] );
					debug ( "fail to connect dbaccount server ".$server ['db_ip'].":".$server ['db_port'] );
				} else {
					debug ( "succ connect dbaccount server ".$server ['db_ip'].":".$server ['db_port'] );
				}
			}
		}
		/*
		$server_list = $this->cache->memcached->get ( 'server_list' );
		if ($server_list) {
			foreach ( $server_list as $key => $server ) {
				if (isset ( $server ['db_ip'] ) && isset ( $server ['db_port'] )) {
					$handler_name = "{$server['ip']}_{$server['port']}";
					if (! isset ( $this->socket_handler [$handler_name] ) || ! is_resource ( $this->socket_handler [$handler_name] )) {
						$this->socket_handler [$handler_name] = create_sock('Cproto', $server ['db_ip'], $server ['db_port'], $server['server_id']);
					} else if (! $this->socket_handler [$handler_name]->sock->is_connect ()) {
						//如果没有链接上去就重新提供一次链接的机会
						unset ( $this->socket_handler [$handler_name] );
						$this->socket_handler [$handler_name] = create_sock('Cproto', $server ['db_ip'], $server ['db_port'], $server['server_id']);
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
		*/
	}
}
