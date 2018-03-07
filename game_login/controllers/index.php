<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Index extends CI_Controller {
	
	public function __construct(){
		parent::__construct();
		$this->load->model('MRole');
		$this->mRole = new MRole();
	}

	public function recordinfo()
	{
        $get_params = $this->input->get();
        $ver = isset ( $get_params['ver'] ) ? intval ($get_params['ver']) : 0;
        $pkg_ver = isset ( $get_params['pkg_ver'] ) ? intval ($get_params['pkg_ver']) : 0;
		//$pkg_ver = $pkg_ver >= $ver ? $pkg_ver : $ver;
		$channel = isset ( $get_params['chn'] ) ? $get_params['chn'] : 'qq';

        $remote_ip = $_SERVER['REMOTE_ADDR'];
        $client_ip = isset($_SERVER['HTTP_TRUE_CLIENT_IP']) ? $_SERVER['HTTP_TRUE_CLIENT_IP'] : '';
        //$channel = isset($get_params['chn']) ? $get_params['chn'] : '';
        load_class('Log')->write_log('NOTI', "recordinfo remote_ip=$remote_ip  client_ip=$client_ip channel_str=$channel ver=$ver pkg_ver=$pkg_ver" );
		$data = array();
		$data['success'] = true;
		$data['review'] = 1;//是否正在审核中
		$data['time'] = time();
		$data['inform'] = array('cnt'=>1, 'url'=>BULLETIN_URL);//登陆公告页
		$data['inform_new'] = array('cnt'=>1, 'url'=>BULLETIN2_URL.'?p=1');//登陆公告页
		$data['share_icon'] = array('cnt'=>1, 'url'=>SHARE_ICON_URL);//分享所需icon

		//登陆网址
		if ($ver <= LATEST_CLI_VERSION) {
			$data['login_url'] = array('cnt'=>1, 'url'=>LOGIN_URL);
		} else {
			$data['login_url'] = array('cnt'=>1, 'url'=>TEST_LOGIN_URL);//测试网址
		}

		//vers_chk:只有QQ才强制更新 0 不需更新 1 有更新非强制 2 强制全量更新 3 强制应用宝差量更新
		$data['vers_chk'] = array('cnt'=>0, 'url'=>UPDATE_URL);
        include(APPPATH.'config/update_pkg_new.php');
		if ($ver < REQUIED_CLI_VERSION) {
			$time_now = time();
			if ($time_now >= $g_pkg_update['opentime']) {
				$pkg_update = isset($g_pkg_update["$channel"]) ? $g_pkg_update["$channel"] : $g_pkg_update["tencent"];
				if ($time_now >= $pkg_update['opentime']) {
					$data['vers_chk']['cnt'] = $pkg_update['mode'];
					$data['vers_chk']['url'] = $pkg_update['url'];
				}
			}
		}

		//新版资源更新
        include(APPPATH.'config/update_res_new.php');
		//$g_new_res_updates['test']
		if ($g_new_res_updates['rel']['active'] && $channel != "82000000") {//内网测试排除韩国版
			$data['new_res_pkgs'] = $g_new_res_updates['rel']['data']; //不赋值表示关闭资源更新
		}

        //include(APPPATH.'config/res_updates.php');

        include(APPPATH.'config/update_so_new.php');
		//$g_so_update['test']
		if ($ver < $g_so_update['all']['so_ver'] && $channel != "82000000") {//内网测试排除韩国版
			$data['so_pkgs'] = $g_so_update['all'];
		}
		return returnResult(CI_Controller::JSON,$data);
	}

}

