<?php
if (!defined('SUCC')) {
	define('SUCC', 0);
}
class Cproto {
    var $sock;
    var $svr_id;

    function __construct($params = array('serip', 'port', 'svrid'=>0)) {
        extract ( $params );
        $serip = isset($params['serip']) ? $params['serip'] : $params[0];
        $port = isset($params['port']) ? $params['port'] : $params[1];
        $svrid = isset($params['svrid']) ? intval($params['svrid']) : intval($params[2]);

        $CI = & get_instance();
        $sock_params = array($serip, $port);
        $sock_name = "{$serip}:{$port}";
        $CI->load->library('Protosocket', $sock_params, $sock_name);
        $this->sock = $CI->$sock_name;
        $this->svr_id = $svrid;
    }

    function __destruct() {
        if ($this->sock)
            $this->sock->close ();
    }

    function park($cmdid, $userid, $private_msg) {
        //22：报文头部长度
        $pkg_len = 22 + strlen ( $private_msg );
        $result = 0;
        $proto_id = 0x00000000;
        return pack ( "L2SL3", $pkg_len, $proto_id, hexdec ( $cmdid ), $this->svr_id, $result, $userid ) . $private_msg;
    }

    function unpark($sockpkg, $private_fmt) {
		if (strlen($sockpkg) > 18) {
			$pkg_arr = @unpack ( "Lproto_len/Lproto_id/Scommandid/Lsvr_id/Lresult/Luserid", $sockpkg );
			if ($private_fmt != "" && $pkg_arr ["result"] == 0) { //成功
				$pkg_arr = @unpack ( "Lproto_len/Lproto_id/Scommandid/Lsvr_id/Lresult/Luserid/" . $private_fmt, $sockpkg );
			}
			if ($pkg_arr) {
				return $pkg_arr;
			}
		}
		return array ("result" => 1003 );
    }

    function send_cmd($cmdid, $userid, $pri_msg, $out_msg) {
        $sendbuf = $this->park ( $cmdid, $userid, $pri_msg );
        return $this->unpark ( $this->sock->sendmsg ( $sendbuf ), $out_msg );
    }

    //DB_USERINFO_REGISTER_CMD = 0x1000
    function userinfo_register($user_name, $passwd, $email, $time, $channel) {
        $pri_msg = pack ( "a64a32a128La16", $user_name, $passwd, $email, $time, $channel);
        $sendbuf = $this->park ( "1000", 8888, $pri_msg );
        return $this->unpark ( $this->sock->sendmsg ( $sendbuf ), "Luser_id/a32sess" );//sess
    }

    //DB_USERINFO_LOGIN_CMD = 0x1001
    function userinfo_login($user_name, $passwd, $time, $channel, $userid = 0) {
		if ($userid == 0) {
			$pri_msg = pack ( "a64a32La16", $user_name, $passwd, $time, $channel );
			$sendbuf = $this->park ( "1001", 8888, $pri_msg );
		} else {
			$pri_msg = pack ( "a64a32La16", $user_name, "aDmIn477", $time, $channel );
			$sendbuf = $this->park ( "1001", $userid, $pri_msg );
		}
        $recvbuf = $this->sock->sendmsg ( $sendbuf );

        $fmt = "Luser_id/a32sess/Llast_zone_id/Lzone_count";//sess
        $recvarr = $this->unpark ( $recvbuf, $fmt );
        if ($recvarr && $recvarr ["result"] != SUCC) {
            return $recvarr;
        }

        $zone_count = $recvarr ['zone_count'];
        for($i = 0; $i < $zone_count; $i ++) {
            $fmt = $fmt . "/Lzone_id_$i/Lrole_uid_$i/a32nick_$i/Lrole_type_$i/Lrole_level_$i";
        }
        return $this->unpark ( $recvbuf, $fmt );
    }

	//DB_USER_CREATE_ROLE_ALL_CMD = 0x1012,
    function user_create_role_all($userid, $nick, $role_type, $invite_code, $zone_id) {
        $pri_msg = pack ( "LLa32LL", $userid, $role_type, $nick, $invite_code, $zone_id);
        $sendbuf = $this->park ( "1012", $userid, $pri_msg );
        $recvbuf = $this->sock->sendmsg ( $sendbuf );

        return $this->unpark ( $this->sock->sendmsg ( $sendbuf ), "Luser_id" );
    }

    function user_set_new_exp($user_id, $new_exp) {
        $pri_msg = pack ( "LL", $user_id, $new_exp );
        $sendbuf = $this->park ( "20A6", 8888, $pri_msg );
        $return = $this->unpark ( $this->sock->sendmsg ( $sendbuf ), "" );
        return isset ( $return ['result'] ) ? intval ( $return ['result'] ) : - 1;
    }

    function user_set_finish_task_by_taskid($user_id, $task_id) {
        $pri_msg = pack ( "LL", $user_id, $task_id );
        $sendbuf = $this->park ( "20A7", 8888, $pri_msg );
        $return = $this->unpark ( $this->sock->sendmsg ( $sendbuf ), "" );
        return isset ( $return ['result'] ) ? intval ( $return ['result'] ) : - 1;
    }

    function user_set_finish_one_task_by_taskid($user_id, $task_id, $is_finished) {
        $pri_msg = pack ( "LLL", $user_id, $task_id, ($is_finished ? 1 : 0) );
        $sendbuf = $this->park ( "20A8", 8888, $pri_msg );
        $return = $this->unpark ( $this->sock->sendmsg ( $sendbuf ), "" );
        return isset ( $return ['result'] ) ? intval ( $return ['result'] ) : - 1;
    }

    function add_shop_product($product_id, $title, $des, $shop_type, $flag1, $show_price, $now_price, $vip_price, $vip_level, $times, $begin_time, $end_time, $limit_tag, $items) {
        $items_str_len = 4;
        $d = pack ( "L", count ( $items ) );
        foreach ( $items as $item ) {
            $d .= pack ( "LL", intval ( $item ['item_id'] ), intval ( $item ['item_cnt'] ) );
            $items_str_len += 8;
        }
        if ($items_str_len < 255) {
            $d .= str_pad ( "", 255 - $items_str_len, "\0" );
        }
        $pri_msg = pack ( "LLa24a40L9", $product_id, $shop_type, $title, $des, $flag1, $show_price, $now_price, $vip_price, $vip_level, $times, $begin_time, $end_time, $limit_tag );
        $sendbuf = $this->park ( "7002", 8888, $pri_msg . $d );
        $return = $this->unpark ( $this->sock->sendmsg ( $sendbuf ), "" );
        return isset ( $return ['result'] ) ? intval ( $return ['result'] ) : - 1;
    }

    function edit_shop_product($product_id, $title, $des, $shop_type, $flag1, $show_price, $now_price, $vip_price, $vip_level, $times, $begin_time, $end_time, $limit_tag, $items) {
        $items_str_len = 4;
        $d = pack ( "L", count ( $items ) );
        foreach ( $items as $item ) {
            $d .= pack ( "LL", intval ( $item ['item_id'] ), intval ( $item ['item_cnt'] ) );
            $items_str_len += 8;
        }
        if ($items_str_len < 255) {
            $d .= str_pad ( "", 255 - $items_str_len, "\0" );
        }
        $pri_msg = pack ( "LLa24a40L9", $product_id, $shop_type, $title, $des, $flag1, $show_price, $now_price, $vip_price, $vip_level, $times, $begin_time, $end_time, $limit_tag );
        $sendbuf = $this->park ( "7007", 8888, $pri_msg . $d );
        $return = $this->unpark ( $this->sock->sendmsg ( $sendbuf ), "" );
        return isset ( $return ['result'] ) ? intval ( $return ['result'] ) : - 1;
    }

    function del_shop_product($product_id) {
        $product_id = pack ( 'L', $product_id );
        $return = $this->unpark ( $this->sock->sendmsg ( $this->park ( '7006', 8888, $product_id ) ), "" );
        return isset ( $return ['result'] ) ? intval ( $return ['result'] ) : - 1;
    }

    function get_shop_product_list($page_no, $shop_type) {
        $msg = pack ( "LLL", $shop_type, $page_no, 0 );
        $sendbuf = $this->park ( "7000", 8888, $msg );
        $result_array = array ('success' => true, 'record_num' => 0, 'count' => 0, 'product_list' => array () );
        $result_fmt_header = "Lcount/Lrecord_num";
        $response_data = $this->sock->sendmsg ( $sendbuf ) . "---";
        $return = $this->unpark ( $response_data, $result_fmt_header );
        if ($return ['result'] == 0) {
            $result_fmt_header = sprintf ( "Lcount/Lrecord_num/a%ddata", $return ['count'] * 363 + 3 );
            $return = $this->unpark ( $response_data, $result_fmt_header );
            $result_array ['record_num'] = intval ( $return ['record_num'] );
            $result_array ['count'] = intval ( $return ['count'] );

            $product_one_fmt = "Lproduct_id/Lshop_type/a24title/a40des/Lflag1/Lshow_price/Lnow_price/Lvip_price/Lvip_level/Ltimes/Llimit_tag/Lbegin_time/Lend_time/a255items";
            $product = array ();
            for($i = 0; $i < $return ['count']; $i ++) {
                $product [] = unpack ( $product_one_fmt, substr ( $return ['data'], $i * 363, 363 ) );
            }

            //fomart product-items
            function format_items($bin_items) {
                $return = '';
                $bin_items .= "\0\0\0\0\0\0\0\0\0\0\0\0";
                $data = unpack ( 'Litems_cnt', $bin_items );
                if (isset ( $data ['items_cnt'] ) && $data ['items_cnt']) {
                    $items = array ();
                    for($i = 0; $i < $data ['items_cnt']; $i ++) {
                        $item = unpack ( 'Litem_id/Litem_cnt', substr ( $bin_items, 4 + $i * 8 ) );
                        $items [] = "{$item['item_id']}:{$item['item_cnt']}";
                    }
                    $return = implode ( ';', $items );
                }
                return $return;
            }
            foreach ( $product as $key => $value ) {
                $product [$key] ['items'] = format_items ( $value ['items'] );
            }
            $result_array ['product_list'] = $product;
        } else {
            $result_array ['success'] = false;
        }
        return $result_array;
    }

    function get_global_rank($rank_type) {
        $rank_type = pack ( "L", $rank_type );
        $sendbuf = $this->park ( "7004", 8888, $rank_type );
        return $this->unpark ( $this->sock->sendmsg ( $sendbuf ), "Lrank_id/Luserid/Lrole_type/a32nick/Lfaction/Lvalue" );
    }

    function userinfo_select_zone($user_id, $zone_id) {
        $pri_msg = pack ( "LL", $user_id, $zone_id );
        $sendbuf = $this->park ( "1002", 8888, $pri_msg );
        return $this->unpark ( $this->sock->sendmsg ( $sendbuf ), "Luser_id" );
    }

    function userinfo_create_role($user_id, $role_type, $zone_id) {
        $pri_msg = pack ( "LLL", $user_id, $role_type, $zone_id );
        $sendbuf = $this->park ( "1003", 8888, $pri_msg );
        return $this->unpark ( $this->sock->sendmsg ( $sendbuf ), "Luser_id" );
    }

    function userinfo_get_userid_by_username($username) {
        $pri_msg = pack ( "a64", $username );
        $sendbuf = $this->park ( "1004", 8888, $pri_msg );
        return $this->unpark ( $this->sock->sendmsg ( $sendbuf ), "Luser_id" );
    }

    function global_get_userid_by_nick($nick) {
        $pri_msg = pack ( "a32", $nick );
        $sendbuf = $this->park ( "6012", 8888, $pri_msg );
        return $this->unpark ( $this->sock->sendmsg ( $sendbuf ), "Luser_id" );
    }

    function userinfo_add_userid_by_username($user_name) {
        $pri_msg = pack ( "a64", $user_name );
        $sendbuf = $this->park ( "1005", 8888, $pri_msg );

        $recvbuf = $this->sock->sendmsg ( $sendbuf );
        $fmt = "Luser_id/a32sess/Lis_new/Llast_zone_id/Lzone_count";//sess
        $recvarr = $this->unpark ( $recvbuf, $fmt );
        if ($recvarr && $recvarr ["result"] != SUCC) {
            return $recvarr;
        }

        $zone_count = $recvarr ['zone_count'];
        for($i = 0; $i < $zone_count; $i ++) {
            $fmt = $fmt . "/Lzone_id_$i/Lrole_uid_$i/a32nick_$i/Lrole_type_$i/Lrole_level_$i";
        }
        return $this->unpark ( $recvbuf, $fmt );
    }
    
    function userinfo_add_userid_by_username_new($user_name, $channel) {
        $pri_msg = pack ( "a64a16", $user_name , $channel);
        $sendbuf = $this->park ( "100E", 8888, $pri_msg );

        $recvbuf = $this->sock->sendmsg ( $sendbuf );
        $fmt = "Luser_id/a32sess/Lis_new/Llast_zone_id/Lzone_count";//sess
        $recvarr = $this->unpark ( $recvbuf, $fmt );
        if ($recvarr && $recvarr ["result"] != SUCC) {
            return $recvarr;
        }

        $zone_count = $recvarr ['zone_count'];
        for($i = 0; $i < $zone_count; $i ++) {
            $fmt = $fmt . "/Lzone_id_$i/Lrole_uid_$i/a32nick_$i/Lrole_type_$i/Lrole_level_$i";
        }
        return $this->unpark ( $recvbuf, $fmt );
    }

    function user_create_role($userid, $role_type, $role_nick, $zone_id, $invite_code = 0) {
        $pri_msg = pack ( "LLa32LL", $userid, $role_type, $role_nick, $invite_code, $zone_id );
        $sendbuf = $this->park ( "2000", $userid, $pri_msg );
        return $this->unpark ( $this->sock->sendmsg ( $sendbuf ), "Luserid" );
    }

    function userinfo_check_invite_code($invite_code) {
        $pri_msg = pack ( "L", $invite_code );
        $sendbuf = $this->park ( "1011", 8888, $pri_msg );
        return $this->unpark ( $this->sock->sendmsg ( $sendbuf ) );
    }

    function user_get_info($userid, $other_userid) {
        $pri_msg = pack ( "L", $other_userid );
        $sendbuf = $this->park ( "2001", $userid, $pri_msg );
        $recvbuf = $this->sock->sendmsg ( $sendbuf );

        $fmt = "Luserid/a32nick/Lrole_type/Lexp/Lyxb/Llast_community_id/Lenery/Lpackage_item_cnt/Litem_used_cnt/Lskill_cnt";
        $recvarr = $this->unpark ( $recvbuf, $fmt );
        if ($recvarr && $recvarr ["result"] != SUCC) {
            return $recvarr;
        }

        for($i = 0; $i < $recvarr ['package_item_cnt']; $i ++) {
            $fmt = $fmt . "/Lunique_id$i/Lpackage_id$i/Litem_id$i/Litem_cnt$i/Litem_lvl$i/Lis_used$i";
        }
        for($i = 0; $i < $recvarr ['item_used_cnt']; $i ++) {
            $fmt = $fmt . "/Litem_id$i/Llast_cnt$i";
        }
        for($i = 0; $i < $recvarr ['skill_cnt']; $i ++) {
            $fmt = $fmt . "/Lskill_id$i/Lskill_lvl$i/Lwear_seat$i";
        }
        return $this->unpark ( $recvbuf, $fmt );
    }

    function user_get_base_info($userid) {
        $sendbuf = $this->park ( "2200", $userid, "" );
        return $this->unpark ( $this->sock->sendmsg ( $sendbuf ), "Lrole_type/a32nick/Lexp" );
    }

    function user_clear_all_task($userid) {
        $sendbuf = $this->park ( "2095", $userid, "" );
        return $this->unpark ( $this->sock->sendmsg ( $sendbuf ), "" );
    }

    function user_set_type_event_nocheck($userid, $type, $value, $field_1, $field_2) {
        $pri_msg = pack ( "LLLL", $type, $value, $field_1, $field_2 );
        $sendbuf = $this->park ( "2027", $userid, $pri_msg );
        return $this->unpark ( $this->sock->sendmsg ( $sendbuf ), "" );
    }

    function user_get_type_event($userid, $type) {
        $pri_msg = pack ( "L", $type );
        $sendbuf = $this->park ( "2023", $userid, $pri_msg );
        return $this->unpark ( $this->sock->sendmsg ( $sendbuf ), "Ltype/Lvalue/Lfield_1/Lfield_2" );
    }

    function user_add_attr_value($userid, $type, $value) {
        if ($type > 10) {
            return 0;
        }
        $pri_msg = pack ( "LLLL", 0, $type, $value, 99999999 );
        $sendbuf = $this->park ( "2016", $userid, $pri_msg );
        return $this->unpark ( $this->sock->sendmsg ( $sendbuf ), "" );
    }

    //DB_USER_CHECK_SAME_ROLE_NICK_CMD = 0x6003
    function user_check_same_role_nick($userid, $nick) {
        $pri_msg = pack ( "a32", $nick );
        $sendbuf = $this->park ( "6003", $userid, $pri_msg );
        return $this->unpark ( $this->sock->sendmsg ( $sendbuf ), "Lis_find" );
    }

    function global_user_info_create_role($userid, $unit_id, $nick) {
        $pri_msg = pack ( "a32L", $nick, $unit_id );
        $sendbuf = $this->park ( "6004", $userid, $pri_msg );
        return $this->unpark ( $this->sock->sendmsg ( $sendbuf ), "" );
    }

    //DB_ADD_MSGLOG_CMD   =   0x8000
    function user_add_msglog($uid, $msgid, $msg_time, $v, $v1, $v2) {
        $pri_msg = pack ( "LLLlll", $uid, $msgid, $msg_time, $v, $v1, $v2 );
        $sendbuf = $this->park ( "8000", $uid, $pri_msg );
        return $this->unpark ( $this->sock->sendmsg ( $sendbuf ), "" );
    }

    function is_connect() {
        return $this->sock->is_connect();
    }

    function close() {
        return $this->sock->close();
    }
}
