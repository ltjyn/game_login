<?php
if (! defined ( 'BASEPATH' ))
    exit ( 'No direct script access allowed' );

include(APPPATH.'helpers/platform_login_helper.php');

/*
    cli_login_session_error          = 10005,   //登陆session出错
	cli_user_zoneid_invalid_err      = 10083,   //分区id不合法
    cli_user_max_login_num_err       = 10084,   //登陆人数过多，请稍后再试
    cli_user_max_zone_user_num_err   = 10085,   //达到分区登陆人数上限，请稍后再试
    cli_user_max_online_user_num_err = 10086,   //online人数超限，请稍后再试
	cli_user_version_low_err         = 10088,   //客户端版本过低,请更新
	cli_server_not_open_err          = 10089,   //暂未开服，请关注官网信息
	cli_user_channel_err             = 10115,    //渠道号错误
	HTTP_LOGIN_NEED_RETRY   		 = 11900,   //http登陆正常排队，客户端需重试(不提示错误)
*/

class Login extends CI_Controller {

    public function __construct() {
        parent::__construct ();
        $this->load->driver('cache');
        //需要保存一个请求状态信息列表,记录每次请求情况
        //然后每次请求前都对该请求信息进行校对,主要是重试次数间隔时间,1 可以判断是否需要发请求队列 2是否需要适当的时候告诉前端超时
        //该数据放到memcache中,status_cmdid_参数键,值array('begin_time'=>'第一次请求时间戳','count'=>'请求次数')
     }

    public function index() {
        $this->cmd_list = array (200, 201, 202, 203, 205 );
        $this->result = array ();
        $this->result ['result'] = 0;

        $this->cmdid = intval ( $this->input->get ( 'cmdid' ) );
        if (!in_array ( $this->cmdid, $this->cmd_list )) {
            $this->result ['result'] = 1; //具体错误信息再定
            return returnResult ( CI_Controller::JSON, $this->result );
        }

		$remote_ip = $_SERVER['REMOTE_ADDR'];
		$client_ip = isset($_SERVER['HTTP_TRUE_CLIENT_IP']) ? $_SERVER['HTTP_TRUE_CLIENT_IP'] : '';
		$input_channel = $this->input->get('chn');
		$channel = isset($input_channel) ? $input_channel : '';
		load_class('Log')->write_log('NOTI', "login remote_ip=$remote_ip  client_ip=$client_ip channel_str=$channel" );
		//检测开服时间
		//$time_now = time();
		//$beg_time = strtotime('2014-04-22 10:00:00');
		//$end_time = strtotime('2014-04-30 00:00:00');
		//$is_close = $time_now < $beg_time || $time_now > $end_time;
		$is_close = 0;
		if ($is_close) {
            $this->result ['result'] = 10089; //暂未开服，请关注官网信息
            //$this->result ['errormsg'] = "暂未开服，请关注官网信息";//若设置了errormsg,客户端优先显示此提示
            return returnResult ( CI_Controller::JSON, $this->result );
		}

		$get_params = $this->input->get();
		$ver = isset ( $get_params['ver'] ) ? intval ($get_params['ver']) : 0;
		if ($ver < REQUIED_CLI_VERSION) {//检查最低版本号 
			$opened = false;
            $time_now = time();
			include(APPPATH.'config/update_pkg_new.php');
            if ($time_now >= $g_pkg_update['opentime']) {
                $pkg_update = isset($g_pkg_update["$channel"]) ? $g_pkg_update["$channel"] : $g_pkg_update["tencent"];
                if ($time_now >= $pkg_update['opentime']) {
					$opened = true;
                }
            }
			if ($opened) {
				$this->result ['result'] = 10088; //客户端版本过低,请更新
				//$this->result ['updateurl'] = UPDATE_URL; 
				$this->result ['updateurl'] = $pkg_update['url']; 
				return returnResult ( CI_Controller::JSON, $this->result );
			} else {
				$this->result ['result'] = 10089; //暂未开服，请关注官网信息
				$this->result ['errormsg'] = "服务器维护中";//若设置了errormsg,客户端优先显示此提示
				return returnResult ( CI_Controller::JSON, $this->result );
			}
		}

        $func_name = "login_{$this->cmdid}";
        return returnResult ( CI_Controller::JSON, $this->{$func_name} ( $get_params, $remote_ip) );
    }

    /**
     * cmd=200平台注册,返回服务器列表
     * @method login_200
     * @param array $notify_data
     * @return array
     */
    private function login_200($notify_data, $client_ip) {
        if (! isset ( $notify_data ['username'] )) {
            load_class('Log')->write_log('ERROR', "no username in post!" );
            exit;
        }
        if (! isset ( $notify_data ['passwd'] ) || strlen ( $notify_data ['passwd'] ) < 32) {
            load_class('Log')->write_log('ERROR', $notify_data ['username'] . " register without passwd in post!" );
            exit;
        }
        if (! isset ( $notify_data ['email'] )) {
            load_class('Log')->write_log('ERROR', $notify_data ['username'] . " register without email in post!" );
            exit;
        }
        if (! isset ( $notify_data ['time'] )) {
            load_class('Log')->write_log('ERROR', $notify_data ['username'] . " register without time in post!" );
            exit;
        }

        //将注册信息放到memcache['create_login']里面去
        $data = array();
		$data['client_ip'] = $client_ip;
        $data['username'] = $notify_data ['username'];
        $data['passwd'] = $notify_data['passwd'];
        $data['email'] = $notify_data['email'];
        $data['time'] = $notify_data['time'];
        $data['ver'] = isset($notify_data['ver']) ? intval($notify_data['ver']) : 0;
		$data['channel'] = isset($notify_data['chn']) ? $notify_data['chn'] : "0";

		if ($data['channel'] == APPLE_CHANNEL) {
			$valid_name = true;
			if (strlen($data['username']) < 6 || strlen($data['username']) > 30) {
				$invalid_name = false;
			}
			if ($valid_name) {
				$rule  = "/[^a-zA-Z0-9._]+/";
				preg_match($rule, $data['username'], $preg_res);//返回匹配次数，出错返回FALSE
				if (count($preg_res)) {
					$valid_name = false;
				}
			}
			if (!$valid_name) {
				log_message('ERROR', "userinfo_register ERR invalid username=" . $notify_data['username'] );
				$ret_arr ['result'] = 10081;
            	$ret_arr ['errormsg'] = "账号名不合规则";
				return $ret_arr;
			}
		}

        $key_name = "waitcreate_" . md5($data['username']);
        $status_key_name = "status_200_" . md5($data['username'].$data['passwd']);
        $ret = $this->cache->memcached->get ( $key_name );
        if (is_array ( $ret ) && isset ( $ret ['result'] )) {
			$ret['username'] = $data['username'];//username传回客户端
            if ($ret ['result'] != 0) {
                load_class ( 'Log' )->write_log ( 'ERROR', "userinfo_register ERR err=" . $ret ['result'] . " username=" . $notify_data ['username'] );
            } else {
                load_class ( 'Log' )->write_log ( 'NOTI', "userinfo_register succ " . $notify_data ['username'] . " userid=" . $ret ['user_id'] . " last_zone_id=" . (isset ( $ret ['last_zone_id'] ) ? $ret ['last_zone_id'] : '') . " zone_cnt=" . (isset ( $ret ['zone_count'] ) ? $ret ['zone_count'] : '') );
            }
			$this->cache->memcached->delete ( $key_name );
			$this->cache->memcached->delete ( $status_key_name );
			return $ret;
        }
        $status_ret = $this->cache->memcached->get($status_key_name);
        //如果没有该值就
        if (!$status_ret || !isset($notify_data['retry'])) {
            if (!send_to_queue(0x54321076, $data)) {
                load_class ( 'Log' )->write_log ( 'ERROR', "userinfo_register ERR send_to_queue username=".$notify_data['username']);
				return array ('result' => 10084); //消息队列写失败
			}
            $params['begin_time'] = time();
            $params['count'] = 0;
            $this->cache->memcached->save($status_key_name, $params, 60);
        } else {
            $status_ret['count'] += 1;
            $this->cache->memcached->save($status_key_name, $status_ret, 60 - $status_ret['count'] * 5);
            if ($status_ret && ((time() - $status_ret['begin_time'] > 10) || ($status_ret['count'] >= 10))) {
				$this->cache->memcached->delete ( $status_key_name );
                return array('result' =>10084);
            }
        }
        return array ('result' => 11900 ); //收到后需要重新请求一次,一般重试次数控制在5次内
    }

    /**
     * cmd=201平台登录,拉取服务器列表
     * @method login_201
     * @param array $notify_data
     * @return array
     */
    private function login_201($notify_data, $client_ip) {
        if (! isset ( $notify_data ['username'] )) {
            load_class('Log')->write_log('ERROR', "no username in post!" );
            exit;
        }
        if (! isset ( $notify_data ['passwd'] ) || strlen ( $notify_data ['passwd'] ) < 32) {
            load_class('Log')->write_log('ERROR', $notify_data ['username'] . " login without passwd in post!" );
            exit;
        }
        if (! isset ( $notify_data ['time'] )) {
            load_class('Log')->write_log('ERROR', $notify_data ['username'] . " login without time in post!" );
            exit;
        }

        //将登陆信息放到memcache['wait_login']里面去
        $data = array ();
		$data['client_ip'] = $client_ip;
        $data ['username'] = $notify_data ['username'];
        $data ['passwd'] = $notify_data ['passwd'];
        $data ['time'] = $notify_data ['time'];
        $data ['ver'] = isset($notify_data['ver']) ? intval($notify_data['ver']) : 0;
		$data['channel'] = isset($notify_data['chn']) ? $notify_data['chn'] : "0";

        $key_name = "waitlogin_" . md5($data['username']);
        $status_key_name = "status_201_" . md5($data['username'].$data['passwd']);
        $ret = $this->cache->memcached->get ( $key_name );
		if (is_array ( $ret ) && isset ( $ret ['result'] )) {
			$ret['username'] = $data['username'];//username传回客户端
            if ($ret ['result'] != 0) {
                load_class ( 'Log' )->write_log ( 'ERROR', "userinfo_login ERR err=" . $ret ['result'] . " username=" . $notify_data ['username'] . " src-data=" . var_export ( $ret, true ) );
            } else {
                load_class ( 'Log' )->write_log ( 'NOTI', "userinfo_login succ " . $notify_data ['username'] . " userid=" . $ret ['user_id'] . " last_zone_id=" . $ret ['last_zone_id'] . " zone_cnt=" . (isset ( $ret ['zone_count'] ) ? $ret ['zone_count'] : '') . " src-data=" . var_export ( $ret, true ) );
            }
			$this->cache->memcached->delete ( $key_name );
			$this->cache->memcached->delete ( $status_key_name );
			return $ret;
        }
        $status_ret = $this->cache->memcached->get($status_key_name);
        //如果没有该值就
        if (!$status_ret || !isset($notify_data['retry'])) {
            //第一次请求
            if (!send_to_queue(0x65432107, $data)) {
                load_class ( 'Log' )->write_log ( 'ERROR', "userinfo_login ERR send_to_queue username=".$notify_data['username']);
				return array ('result' => 10084); //消息队列写失败
			}
            $params['begin_time'] = time();
            $params['count'] = 0;
            $this->cache->memcached->save($status_key_name, $params, 60);
        } else {
            $status_ret['count'] += 1;
            $this->cache->memcached->save($status_key_name, $status_ret, 60 - $status_ret['count'] * 5);
            if ($status_ret && ((time() - $status_ret['begin_time'] > 10) || ($status_ret['count'] > 10))) {
				$this->cache->memcached->delete ( $status_key_name );
                return array('result' => 10084);
            }
        }
        return array('result'=> 11900);//收到后需要重新请求一次,一般重试次数控制在5次内
	}

    /**
     * cmd=202 选择分区
     * @method login_202
     * @param array $notify_data
     * @return array
     */
    private function login_202($notify_data, $client_ip) {
        if (! isset ( $notify_data ['user_id'] )) {
            load_class('Log')->write_log('ERROR', "no userid in post!" );
            exit;
        }
        if (! isset ( $notify_data ['zone_id'] )) {
            load_class('Log')->write_log('ERROR', $notify_data ['user_id'] . " select zone without zone id in post!" );
            exit;
        }
        if (! isset ( $notify_data ['session'] ) || strlen ( $notify_data ['session'] ) < 32) {
            load_class('Log')->write_log('ERROR', $notify_data ['user_id'] . " select zone without session in post!" );
            exit;
        }
        if (! isset ( $notify_data ['time'] )) {
            load_class('Log')->write_log('ERROR', $notify_data ['user_id'] . " select zone without time in post!" );
            exit;
        }

        // 检验session
        $sess_time = $notify_data ['time']; //小端
        $sess_hex = $notify_data ['session'];
        $zone_id = $notify_data ['zone_id'];

        // 检验sess:time+session
        $md5_str = "USEr_sELecT+ZonE_WiTh?uSEriD=" . $notify_data ['user_id'] . 
				"&zoNeId=" . $notify_data ['zone_id'] . "&TIMe=" . $sess_time;
        $md5_key = md5 ( $md5_str );
        load_class('Log')->write_log('NOTI', "user_select_zone input userid:" . $notify_data ['user_id'] . 
				"---time:" . $sess_time . "---sess:" . $sess_hex . "---md5_key:" . $md5_key );

		if (strcasecmp ( $md5_key, $sess_hex ) != 0) {
            load_class('Log')->write_log('ERROR', "user_select_zone sess ERR userid=" . $notify_data ['user_id'] );
            $ret_arr ['result'] = 10005;
            return $ret_arr;
        }

        $data = array();
		$data['client_ip'] = $client_ip;
        $data['user_id'] = $notify_data ['user_id'];
        $data['zone_id'] = $notify_data['zone_id'];
        $data['session'] = $notify_data['session'];
        $data['time'] = $notify_data['time'];
        $data['ver'] = isset($notify_data['ver']) ? intval($notify_data['ver']) : 0;

		//TODO:DEL
        $key_name = "wait_selectzone" . md5($data['user_id'].'-'.$data['zone_id']);
        $ret = $this->cache->memcached->get ( $key_name );
        $status_key_name = "status_202_" . md5($data['user_id'].$data['zone_id']);
        if (is_array ( $ret ) && isset ( $ret ['result'] )) {
            if ($ret ['result'] != 0) {
                load_class ( 'Log' )->write_log ( 'ERROR', "select_zone ERR ret=" . $ret ['result'] . " zone_id=$zone_id userid=" . $notify_data ['user_id'] . " src-data=" . var_export ( $ret, true ) );
            } else {
                load_class ( 'Log' )->write_log ( 'NOTI', "select_zone succ userid=" . $notify_data ['user_id'] . " zone_id=$zone_id" . " src-data=" . var_export ( $ret, true ) );
            }
			$this->cache->memcached->delete ( $key_name );
			$this->cache->memcached->delete ( $status_key_name );
			return $ret;
        }//TODO:DEL

		$zone_pcu = 5000;//单服最大在线人数
		$server_list = $this->cache->memcached->get ( 'server_list' );
		if (is_array ( $server_list ) && count ( $server_list )) {
			if (isset($server_list['server_'.$zone_id])) {
				if ($server_list['server_'.$zone_id]['user_count'] < $zone_pcu) {
					$server_list['server_'.$zone_id]['user_count'] += 1;
					$this->cache->memcached->save ('server_list', $server_list, 20);
					load_class ( 'Log' )->write_log ( 'NOTI', "select_zone succ".
						" user_num=".$server_list['server_'.$zone_id]['user_count'].
						" user_id=".$notify_data['user_id']." zone_id=$zone_id");
					return array ('result' => 0 );//zone server可用,返回成功
				}
				load_class ( 'Log' )->write_log ( 'ERROR', "select_zone ERR no available server".
						" user_num=".$server_list['server_'.$zone_id]['user_count'].
						" user_id=".$notify_data['user_id']." zone_id=$zone_id");
				return array ('result' => 10085 ); //达到分区登陆人数上限，请稍后再试
			}
			load_class ( 'Log' )->write_log ( 'ERROR', "select_zone ERR no select server ".
				" user_id=".$notify_data['user_id']." zone_id=$zone_id");
			return array ('result' => 10083); //分区id不合法
		}
		load_class ( 'Log' )->write_log ( 'ERROR', "select_zone ERR no server_list".
			" user_id=".$notify_data['user_id']." zone_id=$zone_id");
        return array ('result' => 10083 ); //分区id不合法

        $status_ret = $this->cache->memcached->get($status_key_name);
        //如果没有该值就
        if (!$status_ret || !isset($notify_data['retry'])) {
            if (!send_to_queue(0x43210765, $data)) {
                load_class ( 'Log' )->write_log ( 'ERROR', "select_zone send_to_queue ERR".
						" user_id=".$notify_data['user_id']." zone_id=$zone_id");
				return array ('result' => 10084); //消息队列写失败
			}
            $params['begin_time'] = time();
            $params['count'] = 0;
            $this->cache->memcached->save($status_key_name, $params, 60);
        } else {
            if ($status_ret && ((time() - $status_ret['begin_time'] > 10) || ($status_ret['count'] >= 10))) {
				$this->cache->memcached->delete ( $status_key_name );
                return array('result' => 10084);
            }
            $status_ret['count'] += 1;
            $this->cache->memcached->save($status_key_name, $status_ret, 60 - $status_ret['count'] * 5);
        }
        return array ('result' => 11900 ); //收到后需要重新请求一次,一般重试次数控制在5次内
    }

    /**
     * cmd=203 创建角色
     * @method login_203
     * @param array $notify_data
     * @return array
     */
    private function login_203($notify_data, $client_ip) {
        if (! isset ( $notify_data ['user_id'] )) {
            load_class('Log')->write_log('ERROR', "no user info id in post!" );
            exit;
        }
        if (! isset ( $notify_data ['zone_id'] )) {
            load_class('Log')->write_log('ERROR', $notify_data ['user_id'] . " create role without zone id in post!" );
        }
        if (! isset ( $notify_data ['role_type'] )) {
            load_class('Log')->write_log('ERROR', $notify_data ['user_id'] . " create role without role type in post!" );
        }
        if (! isset ( $notify_data ['role_nick'] )) {
            load_class('Log')->write_log('ERROR', $notify_data ['user_id'] . " create role without role nick in post!" );
        }
        if (! isset ( $notify_data ['session'] ) || strlen ( $notify_data ['session'] ) < 32) {
            load_class('Log')->write_log('ERROR', $notify_data ['user_id'] . " create role without session in post!" );
        }
        if (! isset ( $notify_data ['time'] )) {
            load_class('Log')->write_log('ERROR', $notify_data ['user_id'] . " create role without time in post!" );
        }
        // 检验session
        $sess_time = $notify_data ['time']; //小端
        $sess_hex = $notify_data ['session'];

        // 检验sess:time+session
        $md5_str = "usER_CreATe(RolE#wiTH?UsERId=" . $notify_data ['user_id'] . "&ZonEID=" . $notify_data ['zone_id'] . "&rOLeTyPE=" . $notify_data ['role_type'] . "&roleNICk=" . $notify_data ['role_nick'] . "&TIMe=" . $sess_time;
        $md5_key = md5 ( $md5_str );

        $data = array();
		$data['client_ip'] = $client_ip;
        $data['user_id'] = $notify_data ['user_id'];
        $data['zone_id'] = $notify_data['zone_id'];
        $data['role_type'] = $notify_data['role_type'];
        $data['role_nick'] = $notify_data['role_nick'];
        $data['session'] = $notify_data['session'];
        $data['time'] = $notify_data['time'];
        $data['ver'] = isset($notify_data['ver']) ? intval($notify_data['ver']) : 0;
		$data['cmdid'] = $notify_data['cmdid'];

        $key_name = "wait_createrole" . md5($data['user_id'].'-'.$data['zone_id'].'-'.$data['role_type']);
        $status_key_name = "status_203_" . md5($data['user_id'].$data['zone_id'].$data['role_type'].$data['role_nick']);
        $ret = $this->cache->memcached->get ( $key_name );
        if (is_array ( $ret ) && isset ( $ret ['result'] )) {
            if ($ret ['result'] != 0) {
                load_class ( 'Log' )->write_log ( 'ERROR', "create_role ERR ret=" . $ret ['result'] . " zone_id=" . $data ['zone_id'] . " userid=" . $notify_data ['user_id'] . " src-data=" . var_export ( $ret, true ) );
            } else {
                load_class ( 'Log' )->write_log ( 'NOTI', "create_role succ userid=" . $notify_data ['user_id'] . " zone_id=" . $data ['zone_id'] . " src-data=" . var_export ( $ret, true ) );
            }
			$this->cache->memcached->delete ( $key_name );
			$this->cache->memcached->delete ( $status_key_name );
			return $ret;
        }
        $status_ret = $this->cache->memcached->get($status_key_name);
        //如果没有该值就
        if (!$status_ret || !isset($notify_data['retry'])) {
            if (!send_to_queue(0x32107654, $data)) {
                load_class ( 'Log' )->write_log ( 'ERROR', "create_role send_to_queue ERR".
						" user_id=".$notify_data['user_id']." zone_id=".$notify_data['zone_id']);
				return array ('result' => 10084); //消息队列写失败
			}
            $params['begin_time'] = time();
            $params['count'] = 0;
            $this->cache->memcached->save($status_key_name, $params, 60);
        } else {
            $status_ret['count'] += 1;
            $this->cache->memcached->save($status_key_name, $status_ret, 60 - $status_ret['count'] * 5);
            if ($status_ret && ((time() - $status_ret['begin_time'] > 10) || ($status_ret['count'] >= 10))) {
				$this->cache->memcached->delete ( $status_key_name );
                return array('result' => 10084);
            }
        }
        return array ('result' => 11900 ); //收到后需要重新请求一次,一般重试次数控制在5次内
     }

    /**
     * cmd=205平台注册,返回服务器列表,如果用户没有记录，则直接创建
     * @method login_205
     * @param array $notify_data
     * @return array
     */
    private function login_205($notify_data, $client_ip) {
		//log_message ( 'NOTI', "platfrom_user input: " . var_export($notify_data, true));
		$channel_str = isset($notify_data['chn']) ? $notify_data['chn'] : "0";
        if (! isset ( $notify_data ['username'] )) {
            load_class('Log')->write_log('ERROR', "platfrom_user no username in post!" );
            exit;
        }
        if (! isset ( $notify_data ['session'] ) || strlen ( $notify_data ['session'] ) < 32) {
            load_class('Log')->write_log('ERROR', $notify_data ['username'] . " platfrom_user without session in post!" );
            exit;
        }
        if (! isset ( $notify_data ['time'] )) {
            load_class('Log')->write_log('ERROR', $notify_data ['username'] . " platfrom_user without time in post!" );
            exit;
        }
	
		if ($channel_str != APPLE_CHANNEL && $channel_str != BD91_CHANNEL && $channel_str != TBT_CHANNEL 
			&& $channel_str != KY_CHANNEL && $channel_str != PP_CHANNEL && $channel_str != I4_CHANNEL 
			&& $channel_str != ITOOLS_CHANNEL && $channel_str != AIBEI_CHANNEL && $channel_str != XY_CHANNEL && $channel_str != PPW_CHANNEL) {
			if (! isset ( $notify_data ['openkey'] )) {
				load_class('Log')->write_log('ERROR', $notify_data ['username'] . " platfrom_user without openkey in post!" );
				exit;
			}

			if (! isset ( $notify_data ['pay_token'] )) {
				load_class('Log')->write_log('ERROR', $notify_data ['username'] . " platfrom_user without pay_token in post!" );
				exit;
			}
			if (! isset ( $notify_data ['pfkey'] )) {
				load_class('Log')->write_log('ERROR', $notify_data ['username'] . " platfrom_user without pfkey in post!" );
				exit;
			}
			if (! isset ( $notify_data ['pf'] )) {
				load_class('Log')->write_log('ERROR', $notify_data ['username'] . " platfrom_user without pf in post!" );
				exit;
			}
		}

        // 检验session
        $sess_time = $notify_data ['time']; //小端
        $sess_hex = $notify_data ['session'];
		$spec_chn = ($channel_str == OPPO_CHANNEL || $channel_str == HUAWEI_CHANNEL || $channel_str == VIVO_CHANNEL
				|| $channel_str == JIFENG_CHANNEL) ? true : false;
		$username_md5 = ($spec_chn == false) ? $notify_data ['username'] : urlencode($notify_data ['username']);
        $md5_str = "PLatfROm uSEr GeT sVR LisT WitH?nAMe=" . $username_md5 . "&TIMe=" . $sess_time;

        $md5_key = md5 ( md5 ( $md5_str ) );
        log_message ('NOTI', "platfrom_user input username:" . $notify_data ['username'] . 
				" time:" . $sess_time . " sess:" . $sess_hex . " md5_key:" . $md5_key );
        if (strcasecmp ( $md5_key, $sess_hex ) != 0) {
            log_message('ERROR', "platfrom_user sess ERR username=" . $notify_data ['username'] );
            $ret_arr ['result'] = 10005;
            return $ret_arr;
        }
        //load_class('Log')->write_log('NOTI', "platfrom_user check sess succ " . $notify_data ['username'] );

        //将平台过来的数据角色信息放到memcache['wait_createbyplatform']里面去
        $data = array ();
		$data['client_ip'] = $client_ip;
        $data['username'] = $notify_data['username'];
        $data['session'] = $notify_data['session'];
        $data['time'] = $notify_data['time'];
        $data['ver'] = isset($notify_data['ver']) ? intval($notify_data['ver']) : 0;
        $data['os'] = isset($notify_data['os']) ? intval($notify_data['os']) : 0;
		if ($channel_str != APPLE_CHANNEL && $channel_str != BD91_CHANNEL && $channel_str != TBT_CHANNEL 
			&& $channel_str != WDJ_CHANNEL && $channel_str != KY_CHANNEL && $channel_str != PP_CHANNEL 
			&& $channel_str != I4_CHANNEL && $channel_str != ITOOLS_CHANNEL && $channel_str != XY_CHANNEL
			&& $channel_str != AIBEI_CHANNEL && $channel_str != VIVO_CHANNEL && $channel_str != PPW_CHANNEL) {
			$data['openkey'] = $notify_data['openkey'];
			$data['pay_token'] = $notify_data['pay_token'];
			$data['pfkey'] = $notify_data['pfkey'];
			$data['pf'] = $notify_data['pf'];
		}
        $data['userip'] = $_SERVER['REMOTE_ADDR'];
		$data['channel'] = isset($notify_data['chn']) ? $notify_data['chn'] : "0";
		$data['wx'] = isset($notify_data['wx']) ? $notify_data['wx'] : 0;

		$name_found = false;
		if ($channel_str == PP_CHANNEL) {
			$key_name = 'pp'.$data ['username'];//转化前的token
			$res = $this->cache->memcached->get ( $key_name );
			if ($res) {
				$data['username'] = $res;
				$name_found = true;
			}
		}

		if (!$name_found && !my_pre_login_chk($data)) {
			$ret_arr ['result'] = 10005;//登陆session出错(支付session出错)
			return $ret_arr;
		}
		$real_username = $data['username'];
		$ch_tag = "";
		$ch_ret = ChannelConfig::get_channel_tag($channel_str, $ch_tag);
		if ($ch_ret == 0) {//ok
            log_message ( 'NOTI', "platfrom_user get_userid_by_username ok channel=$channel_str tag=$ch_tag chk_ret=$ch_ret username=$real_username");
			$data['username'] = $ch_tag.$real_username;
		} else {
            log_message ( 'ERROR', "platfrom_user get_userid_by_username ERR invalid channel=$channel_str tag=$ch_tag chk_ret=$ch_ret username=$real_username");
			$ret_arr ['result'] = 10115;//渠道号错误
            $ret_arr ['errormsg'] = "渠道号错误";
			return $ret_arr;
		}

		if ($channel_str == PP_CHANNEL && !$name_found) {
			$this->cache->memcached->save($key_name, $data ['username'], 30);
		}

        $key_name = "wait_createbyplatform" . $data ['username'];
        $status_key_name = "status_205_" . $data['username'];
        $ret = $this->cache->memcached->get ( $key_name );
        if (is_array ( $ret ) && isset ( $ret ['result'] )) {
			//$ret['username'] = $data['username'];//username传回客户端
			$ret['username'] = $real_username;//username传回客户端
            if ($ret ['result'] != 0) {
                log_message ( 'ERROR', "platfrom_user get_userid_by_username ERR ret=" . $ret ['result'] . " username=" . $data ['username'] . " channel=" . $data['channel'] . " src-data=" . var_export ( $ret, true ) );
            } else {
                log_message ( 'NOTI', "platfrom_user get_userid_by_username succ username=" . $data ['username'] . " channel=" . $data['channel'] . " userid=" . $ret ['user_id'] . " is_new=" . $ret ['is_new'] . " last_zone=" . $ret ['last_zone_id'] );
            }
			$this->cache->memcached->delete ( $key_name );
			$this->cache->memcached->delete ( $status_key_name );
			return $ret;
        }
        $status_ret = $this->cache->memcached->get($status_key_name);
        //如果没有该值就
        if (!$status_ret || !isset($notify_data['retry'])) {
            $params['begin_time'] = time();
            $params['count'] = 0;
            $params['data'] = $data;
            $this->cache->memcached->save($status_key_name, $params, 60);

			//$queue_data = $data;
			$queue_data = array('username' => $data['username']);
            if (!send_to_queue(0x21076543, $queue_data)) {
                log_message ( 'ERROR', "platfrom_user send_to_queue ERR".
						" username=".$notify_data['username']);
				$this->cache->memcached->delete ( $status_key_name );
				return array ('result' => 10084); //消息队列写失败
			}
        } else {
            $status_ret['count'] += 1;
            $status_ret['data'] = $data;
            $this->cache->memcached->save($status_key_name, $status_ret, 60 - $status_ret['count'] * 5);
            if ($status_ret && ((time() - $status_ret['begin_time'] > 10) || ($status_ret['count'] >= 10))) {
				$this->cache->memcached->delete ( $status_key_name );
                return array('result' => 10084);
            }
        }
        return array ('result' => 11900 ); //收到后需要重新请求一次,一般重试次数控制在5次内
    }

	//平台登陆时 预处理客户端传来的数据，主要是对username进行预处理
    private function pre_platform_login($data) {
	
	}

    public function test() {
		echo <<<EOT
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<title>内网-服务器列表</title>
<pre>
EOT;
		$get_params = $this->input->get();
		$ver = isset ( $get_params['ver'] ) ? intval ($get_params['ver']) : 0;
		$wx = isset ( $get_params['wx'] ) ? intval ($get_params['wx']) : 0;
		$chn = isset ( $get_params['chn'] ) ? $get_params['chn'] : "";
		$remote_ip = $_SERVER['REMOTE_ADDR'];
		//var_dump(get_svr_list(array(), $ver));
	    //print_r(get_svr_list(array(), $ver));
	    var_export(get_svr_list(array(), $ver, 'testqqq', $wx == 1, $chn, "", $remote_ip), false);
	    exit;
		echo <<<EOT
</pre>
</html>
EOT;
    }
}

