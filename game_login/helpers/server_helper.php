<?php
if (! function_exists ( 'get_svr_list' )) {

    function get_svr_list($role_info, $ver, $username, $wx = 0, $channel = "", $prompt = "", $client_ip = "") {
		global $g_test_users;
		if (!isset($g_test_users)) {
			//$g_test_users = array("testaaa",);
			include(APPPATH.'config/test_users.php');
		}
		if ($wx != 1) {//等于1才是微信
			$wx = 0;
		}
        $CI = &get_instance ();
        $ret_arr = array ();
        $ret_arr ['Count'] = 0;
        $i = 0;
		$channel_checked = array(LX_CHANNEL,UC_CHANNEL,KY_CHANNEL,JINLI_CHANNEL,OPPO_CHANNEL,I4_CHANNEL,ITOOLS_CHANNEL,Q360_CHANNEL,WDJ_CHANNEL,XY_CHANNEL,NDUO_CHANNEL,XM_CHANNEL);
        $server_list = $CI->cache->memcached->get ( 'server_list' );
        if (is_array ( $server_list ) && count ( $server_list )) {
            foreach ( $server_list as $zone_id => $zone_conf ) {
				$zone_id = $zone_conf['server_id'];
				$is_test = isset($zone_conf['test']) ? $zone_conf['test'] : 0;
				$min_ver = isset($zone_conf['min_ver']) ? $zone_conf['min_ver'] : 0;
				$max_ver = isset($zone_conf['max_ver']) ? $zone_conf['max_ver'] : 0;
				$weixin = isset($zone_conf['weixin']) ? $zone_conf['weixin'] : 0;
				$time_str = isset($zone_conf['start_time']) ? $zone_conf['start_time'] : '2037-12-31';
				$start_ts = strtotime($time_str); 
				$time_now = time();

				$is_user_test = in_array($username, $g_test_users);
				//is_test: 0 非测试服，所有人可见; 1 仅测试号可见 ; 2 特殊，仅测试号不可见 
				$show_chk1 = $is_test == 0 ? true : ($is_test == 1 ? $is_user_test : !$is_user_test);
				//$show_chk1 = (!$is_test || in_array($username, $g_test_users));

				//if ($channel == LENOVO_CHANNEL && $show_chk1) $show_chk1 = ($zone_id == 100);//联想渠道特殊处理
				$visible = ($start_ts !== false && $time_now >= $start_ts
						&& $wx == $weixin
						&& $show_chk1
						&& ($min_ver == 0 || $ver >= $min_ver)
						&& ($max_ver == 0 || $ver <= $max_ver));

                if ($visible) {
                    //这个服的基本信息
                    $ret_arr ['zone_id_' . $i] = $zone_id;
                    $ret_arr ['zone_name_' . $i] = $zone_conf ['name'];
                    $ret_arr ['zone_ip_' . $i] = $zone_conf ['online_ip'];
                    $ret_arr ['zone_port_' . $i] = $zone_conf ['online_port'];

					//0 无状态, 1 推荐, 2 爆满, 3 特殊推荐(无角色时) 至少有一个服的状态是3
                    if ($zone_conf ['state'] != 3 && isset ( $zone_conf ['user_count'] ) && $zone_conf ['user_count'] > 1000) {
                        $ret_arr ['zone_state_' . $i] = 2;
                    } else {
                        $ret_arr ['zone_state_' . $i] = $zone_conf ['state'];
                    }

                    //这个服上的角色信息
                    $ret_arr ['has_role_' . $i] = 0;
                    $ret_arr ['role_uid_' . $i] = 0;
                    $ret_arr ['role_type_' . $i] = 0;
                    $ret_arr ['role_level_' . $i] = 0;
                    if (isset ( $role_info ['zone_count'] ) && count ( $role_info ['zone_count'] )) {
                        for($j = 0; $j < $role_info ['zone_count']; $j ++) {
                            if (isset($role_info['zone_id_' . $j]) && $zone_id == $role_info ['zone_id_' . $j]) {
                                $ret_arr ['has_role_' . $i] = 1;
                                //$ret_arr['nick'.$i] = $role_info['nick_'.$j];
                                $ret_arr ['role_uid_' . $i] = $role_info ['role_uid_' . $j];
                                $ret_arr ['role_type_' . $i] = $role_info ['role_type_' . $j];
                                $ret_arr ['role_level_' . $i] = $role_info ['role_level_' . $j];
                                break;
                            }
                        }
                    }
                    $ret_arr ['Count'] += 1;
                    $i += 1;
                } else {
                    debug ( "$prompt get svr list ver mismatch zone_id=" . $zone_id . " start_time=" . $time_str  .
							" zone_ver=$min_ver $max_ver cli_ver=$ver weixin=$weixin cli_wx=$wx");
                }
            }
        }
        return $ret_arr;
    }
}

if (! function_exists ( 'get_svr_online_ip_port' )) {

    /* @brief 获取单个服的online的ip port*/
    /* return array(ip,port,user_count) */
    function get_svr_online_ip_port($ip, $port) {
        debug ( "trying to get zone online ip from gw: " . $ip . " " . $port );
        $CI = &get_instance ();
        $socket = create_sock ( 'Protosocket', $ip, $port, 0 );
        if ($socket->is_connect ()) {
            $sendbuf = @pack ( "L2SL3", 22, 0, 60101, 1, 0, 6666 );
            $recvbuf = $socket->sendmsg ( $sendbuf );
            $socket->close ();
            if ($recvbuf != false) {
                $rethead = @unpack ( "Lproto_len/Lproto_id/Scommandid/Lsvrid/Lresult/Luserid", $recvbuf );
                if ($rethead ['result'] == 0) {
                    $pkg_arr = @unpack ( "Lproto_len/Lproto_id/Scommandid/Lsvrid/Lresult/Luserid" . "/Sonline_id/a16ip/Sport/Suser_count", $recvbuf );
                    debug ( "get zone online ip SUCC ol_id=" . $pkg_arr ['online_id'] . " user_cnt=" . $pkg_arr ['user_count'] . " ip_port=" . $pkg_arr ['ip'] . " " . $pkg_arr ['port'] );
                    return array ('ret' => 0, 'ip' => $pkg_arr ['ip'], 'port' => $pkg_arr ['port'], 'user_count' => $pkg_arr ['user_count'] );
                } else {
                    debug ( "get zone online ip ERR aa: err=" . $rethead ['result'] );
                }
            } else {
                debug ( "get zone online ip ERR bb gateway timeout" );
            }
        } else {
            debug ( "get zone online ip ERR cc fail to connect gateway" );
        }
        debug ( "get zone online ip FAIL" );
        return array ('ret' => 10003 );
    }
}
if (! function_exists ( 'create_sock' )) {

    function create_sock($library, $ip, $port, $svrid = 0) {
		$CI = &get_instance();
        if ($library == 'Cproto') {
            $params = array ($ip, $port, $svrid );
        } elseif ($library == 'Protosocket') {
            $params = array ($ip, $port );
        }
        $handler_name = "{$library}_".implode('_',$params);
        if (isset ( $CI->{md5 ( $handler_name )} ) && $CI->{md5 ( $handler_name )}->is_connect ()) {
        } else {
            unset ( $CI->{md5 ( $handler_name )} );
            $CI->load->library ( $library, $params, md5 ( $handler_name ) );
        }
        return $CI->{md5 ( $handler_name )};
    }
}
if (! function_exists ( 'gen_online_session_hash32' )) {

    function gen_online_session_hash32($userid, $zone_id, $time) {
        $session_buff = 'UserID=' . $userid . 'LoGIn To zoneID=' . $zone_id . ' At TimE=' . $time;
        return md5 ( $session_buff );
    }
}
if (! function_exists ( 'check_online_session' )) {

    function check_online_session($userid, $zone_id, $time, $session) {
        $online_session = gen_online_session_hash32 ( $userid, $zone_id, $time );
        echo 'gen session:' . $online_session . "\r\n";
        return strcasecmp ( $session, $online_session );
    }
}
if (! function_exists ( 'gen_session_hash32' )) {

    function gen_session_hash32($userid, $time, $device_id, $chnl) {
        if (empty ( $time ) || empty ( $device_id )) {
            return false;
        }

        $hash_buffer = 'userId=' . $userid . '&time=' . $time . '&deviceID=' . $device_id . '&deviceType=' . ($chnl % 100) . '&platfrom=' . ($chnl / 100 % 100);

        #$hash_buffer = substr($hash_buffer . $data, 0, MAX_HASH_BUFFER_LENGTH);
        return md5 ( $hash_buffer );
    }
}
if (! function_exists ( 'check_user_session' )) {

    function check_user_session($userid, $time, $device_id, $chnl, $session) {
        $gen_session = gen_session_hash32 ( $userid, $time, $device_id, $chnl );
        echo 'gen session:' . $gen_session . "\r\n";
        return strcasecmp ( $session, $gen_session );
    }
}
if (! function_exists ( 'num2str' )) {

    function num2str($num, $length) {
        $num_str = ( string ) $num;
        $num_strlength = count ( $num_str );
        if ($length > $num_strlength) {
            $num_str = str_pad ( $num_str, $length, "0", STR_PAD_LEFT );
        }
        return $num_str;
    }
}
if (! function_exists ( 'str2hex' )) {

    function str2hex($utf8_str) {
        $str = '';
        for($i = 0; $i < strlen ( $utf8_str ); $i ++) {
            $str .= sprintf ( "%02x", ord ( substr ( $utf8_str, $i, 1 ) ) );
        }
        return $str;
    }
}
#utf8 string to num，只转前4位
if (! function_exists ( 'str2num' )) {

    function str2num($utf8_str, $is_big_endian) {
        $num = 0;
        for($i = 0; $i < strlen ( $utf8_str ) && $i < 4; $i ++) {
            if ($is_big_endian) {
                $num += ord ( substr ( $utf8_str, $i, 1 ) ) << ((3 - $i) * 8);
            } else {
                $num += ord ( substr ( $utf8_str, $i, 1 ) ) << (($i) * 8);
            }
        }
        return $num;
    }
}
if (! function_exists ( 'hex2bin' )) {

    function hex2bin($hexdata) {
        $bindata = '';
        for($i = 0; $i < strlen ( $hexdata ); $i += 2) {
            $bindata .= chr ( hexdec ( substr ( $hexdata, $i, 2 ) ) );
        }
        return $bindata;
    }
}
if (! function_exists ( 'sendtoqueue' )) {

    function sendtoqueue($userid, $zone_id, $server_id) {
        $message_queue_key = 0x34343456;

        $message_queue = msg_get_queue ( $message_queue_key, 0666 );
        //向消息队列中写
        $data = pack ( 'llll', time (), $userid, $zone_id, $server_id );
        msg_send ( $message_queue, 1, $data, false, false );
    }
}

if (! function_exists ( 'my_error_handler' )) {
	function my_error_handler($severity, $message, $filepath, $line) {
		load_class('Exceptions', 'core')->log_exception($severity, $message, $filepath, $line);
	}
}

if (! function_exists ( 'send_to_queue' )) {

    function send_to_queue($key, $data) {
        $message_queue = msg_get_queue ( $key, 0666 );
        //向消息队列中写
		$errcode = 0;
		$old_handler = set_error_handler('my_error_handler');//屏蔽CI的错误处理函数，抑制不必要的错误输出
        $ret = msg_send ( $message_queue, 1, $data, true, false, $errcode);
		set_error_handler($old_handler);
		if ($ret == false) {
            load_class ( 'Log' )->write_log ( 'ERROR', "queue msg_send fail errcode=$errcode err:".var_export(error_get_last(), true)." data:" . var_export ( $data, true ) );
		}
        return $ret;
    }
}
if (! function_exists ( 'receive_from_queue' )) {

    function receive_from_queue($key) {
        $message_queue = msg_get_queue ( $key, 0666 );
        $type = 1;
        if (msg_receive ( $message_queue, 0, $type, 1024, $data, 1, 0, $errno )) {
            load_class ( 'Log' )->write_log ( 'NOTI', "receive_from_queue:" . var_export ( $data, true ) );
            return $data;
        }
        var_dump ( $errno );
        load_class ( 'Log' )->write_log ( 'ERROR', "receive_from_queue error,errno:{$errno},errmsg:" . var_export ( error_get_last (), true ) );
        return false;
    }
}
if (! function_exists ( 'debug' )) {

    function debug($str) {
        echo '[' . date ( 'Y-m-d H:i:s' ) . '] ' . $str . "\n";
    }
}
