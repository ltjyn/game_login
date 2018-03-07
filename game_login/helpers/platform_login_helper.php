<?php
if (! function_exists ( 'my_pre_login_chk' )) {
#include(APPPATH.'config/constants.php');

//若校验成功(或不需要校验)返回true, 否则返回false
function my_pre_login_chk(&$login_data) {
	$chk_data = null;
	if ($login_data['channel'] == LENOVO_CHANNEL) {//联想的需先验证Lenovo ID Token(ST)得到Account信息
		$chk_data = my_pre_login_chk_lenovo($login_data['username'], '1410131001526.app.ln');//腾讯代发渠道
	} else if ($login_data['channel'] == UC_CHANNEL) {
		$chk_data = my_pre_login_chk_uc($login_data['username']);
	} else if ($login_data['channel'] == MZW_CHANNEL) {
		$chk_data = my_pre_login_chk_mzw($login_data['username']);
	} else if ($login_data['channel'] == Q360_CHANNEL) {
		$chk_data = my_pre_login_chk_360($login_data['username']);
	} else if ($login_data['channel'] == OPPO_CHANNEL) {
		$chk_data = my_pre_login_chk_oppo($login_data['username']);
	} else if ($login_data['channel'] == JINLI_CHANNEL) {
		$chk_data = my_pre_login_chk_jinli($login_data['username']);
	} else if ($login_data['channel'] == HUAWEI_CHANNEL) {
		$chk_data = my_pre_login_chk_huawei($login_data['username']);
	} else if ($login_data['channel'] == LX_CHANNEL) {//参见LENOVO_CHANNEL
		$chk_data = my_pre_login_chk_lenovo($login_data['username'], '1412160518852.app.ln');//自己联运渠道
	} else if ($login_data['channel'] == WDJ_CHANNEL) {
		$chk_data = my_pre_login_chk_wdj($login_data['username']);
	} else if ($login_data['channel'] == DANGLE_CHANNEL) {
		$chk_data = my_pre_login_chk_dangle_android($login_data['username']);
	} else if ($login_data['channel'] == VIVO_CHANNEL) {
		$chk_data = my_pre_login_chk_vivo($login_data['username']);
	} else if ($login_data['channel'] == SHUYOU_CHANNEL) {
		$chk_data = my_pre_login_chk_shuyou($login_data['username']);
	} else if ($login_data['channel'] == YYH_CHANNEL) {
		$chk_data = my_pre_login_chk_yyh($login_data['username']);
	} else if ($login_data['channel'] == AI4399_CHANNEL) {
		$chk_data = my_pre_login_chk_4399($login_data['username']);
	} else if ($login_data['channel'] == JIFENG_CHANNEL) {
		$chk_data = my_pre_login_chk_jifeng($login_data['username']);
	} else if ($login_data['channel'] == BD_CHANNEL) {
		$chk_data = my_pre_login_chk_bd($login_data['username']);
	} else if ($login_data['channel'] == PPW_CHANNEL) {
		$chk_data = my_pre_login_chk_ppw($login_data['username']);
	}

	//IOS
   	else if ($login_data['channel'] == TBT_CHANNEL) { 
		$chk_data = my_pre_login_chk_tbt($login_data['username']);
	}
	//else if ($login_data['channel'] == BD91_CHANNEL) {
	//	$chk_data = my_pre_login_chk_bd91($login_data['username']);
	//}
	else if ($login_data['channel'] == KY_CHANNEL) {
		$chk_data = my_pre_login_chk_ky($login_data['username']);
	}
	else if ($login_data['channel'] == PP_CHANNEL) {
		$chk_data = my_pre_login_chk_pp($login_data['username']);
	}
	else if ($login_data['channel'] == I4_CHANNEL) {
		$chk_data = my_pre_login_chk_I4($login_data['username']);
	}
	else if ($login_data['channel'] == ITOOLS_CHANNEL) {
		$chk_data = my_pre_login_chk_itools($login_data['username']);
	}
	else if ($login_data['channel'] == XY_CHANNEL) {
		$chk_data = my_pre_login_chk_xy($login_data['username']);
	}
	else if ($login_data['channel'] == IDANGLE_CHANNEL) {
		$chk_data = my_pre_login_chk_dangle_ios($login_data['username']);
	}
	else {
		return true;
	}

	if (!$chk_data) {
		return false;
	}
	$login_data['username'] = $chk_data['Account'];//修正username为真正的平台账号id
	return true;
}

//联想渠道验证Lenovo ID Token(ST)得到Account信息
//失败返回false, 成功返回 array ('Account' => 'abcxyz');
function my_pre_login_chk_lenovo($token, $appid) {
	$auth_url = 'http://passport.lenovo.com/interserver/authen/1.2/getaccountid';
	//$appid = '1410131001526.app.ln';//原腾讯代发的appid
	$auth_get_params = array ( 'lpsust' => $token, 'realm' => $appid);
	$response_data = request_by_curl($auth_url, $auth_get_params);
	log_message('NOTI', "lenovo login chk_res:".var_export($response_data, true));

	$xmldata = simplexml_load_string($response_data);//SimpleXMLElement::__set_state()
	$xmldata = str_replace('SimpleXMLElement::__set_state(', '', var_export($xmldata, true));
	$xmldata = substr($xmldata, 0, -1);

	eval("\$resdata = $xmldata;");//array('AccountID', 'Username', 'DeviceID', 'verified'=>'1')
	log_message('NOTI', "lenovo login resdata:".var_export($resdata, true));
	if (!isset($resdata['AccountID'])) {//$resdata['verified'] == 1
		log_message('ERROR', "err lenovo login chk lpsust=" . $token );
		return false;
	}
	return array('Account' => $resdata['AccountID']);
}

//UC渠道验证 得到Account信息
//失败返回false, 成功返回 array ('Account' => 'abcxyz');
function my_pre_login_chk_uc($token) {
	$uri = 'account.verifySession';
	$auth_url = "http://sdk.g.uc.cn/cp/".$uri;
	$cpid = 47718;
	$appid = 540579;//即gameId
	$appkey = "d17da79f6c6769ae2ea30a893739f54b";
	$game_params = array (
		'gameId' => $appid, 
		);
	$data_params = array ('sid' => $token);

	//生成签名
	ksort($data_params);
	$str_data = '';
	foreach( $data_params as $key=>$val ){
		$str_data .= $key.'='.$val;
	}
	//$sign = md5($cpid.$str_data.$appkey);
	$sign = md5($str_data.$appkey);

	$request_id = time();//or 毫秒数?
	//list($t1, $t2) = explode(' ', microtime());
	//$request_id = (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
	$params = array (
			'id' => $request_id, 
			'service' => $uri,
			'data' => $data_params,
			'game' => $game_params,
			'sign' => $sign,
			);
	
	$auth_get_params = array ();
	$post_string = json_encode($params);
	//log_message('NOTI', "uc login param_string:".var_export($post_string, true));
	$response_data = request_by_curl($auth_url, $auth_get_params, $post_string);
	log_message('NOTI', "uc login chk_res:".var_export($response_data, true));
	$resdata = json_decode($response_data, true);
	log_message('NOTI', "uc login chk_res json:".var_export($resdata, true));
	if (!isset($resdata['state']['code']) || $resdata['state']['code'] != 1
			|| !isset($resdata['data']['accountId']) || !$resdata['data']['accountId']) {
		log_message('ERROR', "err uc login chk sid=" . $token );
		return false;
	}
	return array('Account' => $resdata['data']['accountId']);
}
	
//拇指玩渠道验证 得到Account信息
//失败返回false, 成功返回 array ('Account' => 'abcxyz');
function my_pre_login_chk_mzw($token) {
	$auth_url = 'http://sdk.muzhiwan.com/oauth2/getuser.php';
	$oldAppKey = "d84c1a972d1760365411909954ddeca8";
	$oldSignKey = "5456ed73f25d8";
	$newAppKey = "979b19ae213a9c410cd9c76ccfa95661";
	$newSignKey = "5493c3f67f03c";

	//$appkey = $oldAppKey;
	$appkey = $newAppKey;
	$auth_get_params = array ( 'token' => $token, 'appkey' => $appkey);
	$response_data = request_by_curl($auth_url, $auth_get_params);
	log_message('NOTI', "mzw login chk_res:".var_export($response_data, true));

	$resdata = json_decode($response_data, true);
	if (!isset($resdata['code']) || $resdata['code'] != 1) {//$resdata['verified'] == 1
		log_message('ERROR', "err mzw login chk lpsust=" . $token );
		return false;
	}
	return array('Account' => $resdata['user']['username']);
}

//360渠道验证 得到Account信息
//失败返回false, 成功返回 array ('Account' => 'abcxyz');
function my_pre_login_chk_360($token) {
	$auth_url = 'https://openapi.360.cn/user/me.json';
	//下面3个参数实际都没有用到
	$appid=202169766;
	$appkey = 'aca38090fde4d2f1cda901b2979f90e5';
	$appsecret = 'c2e29ba441d379c16a93bcaefb9faede';
	//$auth_get_params = array ( 'token' => $token, 'appkey' => $appkey);
	$auth_get_params = array ( 'access_token' => $token);
	$response_data = request_by_curl($auth_url, $auth_get_params);
	log_message('NOTI', "Q360 login chk_res:".var_export($response_data, true));

	$resdata = json_decode($response_data, true);
	if (!isset($resdata['id'])) {//$resdata['verified'] == 1
		log_message('ERROR', "err Q360 login chk token=" . $token );
		return false;
	}
	return array('Account' => $resdata['id']);
}

//豌豆荚渠道验证 得到Account信息
//失败返回false, 成功返回 array ('Account' => 'abcxyz');
function my_pre_login_chk_wdj($token) {
	$arr = explode(";", $token);
	return array('Account' => $arr[0]);
/*    $tokenAPI="https://pay.wandoujia.com/api/uid/check";
    $uid="8139480";
    $token=urlencode("TdIKyj1Wqor3XrlamnLQcmCaqlHeClkPXg4OmPo3iBo=");
    $auth_url=$tokenAPI."?uid=".$uid."&token=".$token."&appkey_id=100000000";
	//$auth_get_params = array ('uid' => $uid, 'token' => $token, 'appkey_id' => '123456');//返回值？
	$response_data = request_by_curl($auth_url);

	log_message('NOTI', "WDJ login chk_res:".var_export($response_data, true));

	$resdata = json_decode($response_data, true);
	if (!isset($resdata['id'])) {//$resdata['verified'] == 1
		log_message('ERROR', "err WDJ login chk token=" . $token );
		return false;
	}
	return array('Account' => $resdata['id']);*/
}

//OPPO渠道验证 得到Account信息
//失败返回false, 成功返回 array ('Account' => 'abcxyz');
function my_pre_login_chk_oppo($access_token) {
	//$access_token = urldecode($access_token);
	//access_token格式：oauth_token=10e96c9e4ee3d37b8bd2d2bb540272d3&oauth_token_secret=ce0c5457dc11f87c8a8b3f7edbfad7a6
	//log_message('NOTI', "OPPO login chk access_token:".var_export($access_token, true));
	$name_vals=explode("&", $access_token);
	if (count($name_vals) == 2) {
		$arr0 = explode("=", $name_vals[0]);
		$token = count($arr0) == 2 ? $arr0[1] : null;
		$arr1 = explode("=", $name_vals[1]);
		$token_secret = count($arr1) == 2 ? $arr1[1] : null;
	}
	if (!isset($token) || !isset($token_secret)) {
		log_message('ERROR', "err OPPO login chk params invalid token=" . var_export($token,true) 
			. " secret=" . var_export($token_secret,true));
		return false;
	}
	$AppID=2810;
	$AppKey='4L9Ccw2ly1oG4S4css8w8k84O';
	$appSecret='50C09acA1c0c1890a1617b4285cf5d6a';
    $auth_url="http://thapi.nearme.com.cn/account/GetUserInfoByGame";
	require_once __DIR__."/"."oppo_login.class.php";

	$oppo_login= new oppo_login_base($token_secret, $token);
	$resdata = $oppo_login->getUserInfo();
	log_message('NOTI', "OPPO login chk_res:".var_export($resdata, true));
	if (!isset($resdata['BriefUser']['id'])) {
		log_message('ERROR', "err OPPO login chk token=" . $token . " secret=" . $token_secret);
		return false;
	}
	return array('Account' => $resdata['BriefUser']['id']);
}

//huawei登陆校验
function my_pre_login_chk_huawei($token) {
	log_message('NOTI', "HUAWEI login input:".$token);
    $arr = explode(" ", $token);
    return array('Account' => $arr[0]);
	//必要时，用如下代码进行验证
	$access_token = $arr[1];
    $auth_url="https://api.vmall.com/rest.php";
	$get_params = array(
		'nsp_svc' => 'OpenUP.User.getInfo',
		'nsp_ts' => time(0),
		'access_token' => $access_token
	);
	$response_data = request_by_curl($auth_url, $get_params);
	log_message('NOTI', "HUAWEI login chk_res:".var_export($response_data, true));
	$resdata = json_decode($response_data, true);
	if (!isset($resdata['userID'])) {//$resdata['userState'] == 1
		log_message('ERROR', "err HUAWEI login chk token=" . $token );
		return false;
	}
	return array('Account' => $resdata['userID']);
}

//同步推登陆校验
function my_pre_login_chk_tbt($token) {
	$arr = explode(";", $token);
	return array('Account' => $arr[0]);
/*
    $auth_url="http://tgi.tongbu.com/checkv2.aspx";
	$response_data = request_by_curl($auth_url, $token);

	log_message('NOTI', "TBT login chk_res:".var_export($response_data, true));
	//大于1:
	//Token 有效（请求的Uid）
	//0：失效
	//-1：格式有错
	if ($response_data == 0) {
		log_message('ERROR', "err TBT login chk invalid token=" . $token );
		return false;
	} elseif ($response_data == -1) {
		log_message('ERROR', "err TBT login chk format err token=" . $token );
		return false;
	} elseif ($response_data > 1) {
		return array('Account' => "$response_data");
	}
*/
}
//91
function my_pre_login_chk_bd91($token) {
/*	$auth_url = 'http://sdk.muzhiwan.com/oauth2/getuser.php';
	$appkey = 'd52771de0898a10fa8d49a3570d2d31b';
	$auth_get_params = array ( 'token' => $token, 'appkey' => $appkey);
	$response_data = request_by_curl($auth_url, $auth_get_params);
	log_message('NOTI', "mzw login chk_res:".var_export($response_data, true));

	$resdata = json_decode($response_data, true);
	if (!isset($resdata['code']) || $resdata['code'] != 1) {//$resdata['verified'] == 1
		log_message('ERROR', "err mzw login chk lpsust=" . $token );
		return false;
	}
	return array('Account' => $resdata['user']['username']);
	*/
}

function my_pre_login_chk_jinli($token) {
	$verify_url = "https://id.gionee.com/account/verify.do";
	$apiKey = "16E2109214CD4608B7C0FB3F84298840";     //替换成商户申请获取的APIKey
	$secretKey = "448FE86E90B248818AEC290ABA0EB070";  //替换成商户申请获取的SecretKey
	$host = "id.gionee.com";
	$port = "443";
	$uri = "/account/verify.do";
	$method = "POST";	

	$ts =  time();
	$nonce = strtoupper(substr(uniqid(),0,8));
	$signature_str = $ts."\n".$nonce."\n".$method."\n".$uri."\n".$host."\n".$port."\n"."\n";
	$signature = base64_encode(hash_hmac('sha1',$signature_str,$secretKey,true));
	$Authorization = "MAC id=\"{$apiKey}\",ts=\"{$ts}\",nonce=\"{$nonce}\",mac=\"{$signature}\"";
	log_message('DEBUG', "Authorization:".$Authorization);
		
	list($playerId, $tokenStr) = explode(";", $token);
	$get_params = array ();
	$cookie_params = array ();
	$auth_data = array('Authorization: '.$Authorization);
	$response_data = request_by_curl($verify_url, $get_params, $tokenStr, $cookie_params, $auth_data);

	$result_arr = json_decode($response_data, true);
	if(isset($result_arr['r']) && $result_arr['r'] != 0){//不包含'r'参数或'r'值为0,表示成功,否则失败
		//由于目前的login.php第一次验证都是失败,要求客户端再发一次验证
		//而金立平台第二次验证会报错,所以当出现这个错误的时候就代表已经验证通过了
		if ($result_arr['r'] == 1011 && $result_arr['err'] === "验证令牌的次数大于最大限制，一般为一次有效") {
			return array('Account' => $playerId);
		}
		log_message('ERROR', "jinli login is failed!");
		return false;
	}
	log_message('NOTI', "jinli login is ok!");
	return array('Account' => $result_arr['ply'][0]['pid']);
}

function my_pre_login_chk_ky($token) {
	$appKey = "d374ba86548c6059afc672216015d200";
	$url = "http://f_signin.bppstore.com/loginCheck.php";
	$sign = md5($appKey.$token);

	$auth_get_params = array ( 'tokenKey' => $token, 'sign' => $sign);
	$response_data = request_by_curl($url, $auth_get_params);
	log_message('NOTI', "kuaiyong login chk_res:".var_export($response_data, true));
	$result_arr = json_decode($response_data, true);
	if($result_arr['code'] != 0){
		log_message('ERROR', "err kuaiyong login check, ret:".$result_arr['code']." token=".$token );
		return false;
	}
	return array('Account' => $result_arr['data']['guid']);
}

function my_pre_login_chk_pp($token) {
	$auth_url = "http://passport_i.25pp.com:8080/account?tunnel-command=2852126760";
	/** * AppID * @var int */
	$app_id = 5145;
	/** * 密钥 * @var string */
	$app_key = '8f81f13c3b0f0c4c79ae0643107af3f6';

	$data_arr = array(
			'id' => time(),
			'service' => 'account.verifySession',
			'game' => array('gameId' => $app_id),
			'data' => array('sid' => $token),
			'encrypt' => 'md5',
			'sign' => md5("sid=".$token.$app_key),
		);
	log_message('NOTI', "pp login chk_send:".var_export($data_arr, true));

	$auth_get_params = array ();
	$post_string = json_encode($data_arr);
	$response_data = request_by_curl($auth_url, $auth_get_params, $post_string);
	log_message('NOTI', "pp login chk_res:".var_export($response_data, true));
	$resdata = json_decode($response_data, true);
	//log_message('NOTI', "pp login chk_res json:".var_export($resdata, true));

	if($resdata['state']['code'] != 1){
		log_message('ERROR', "err pp login check, ret:".$resdata['state']['code']." token=".$token );
		return false;
	}

	log_message('NOTI', "pp login userid :".$resdata['data']['accountId']);
	$new_account = $resdata['data']['creator'].$resdata['data']['accountId'];
	return array('Account' => $new_account);
}

function my_pre_login_chk_i4($token) {
	$auth_url = "https://pay.i4.cn/member_third.action";

	$auth_get_params = array ( 'token' => $token);
	$response_data = request_by_curl($auth_url, $auth_get_params);
	log_message('NOTI', "i4 login chk_res:".var_export($response_data, true));
	$resdata = json_decode($response_data, true);
	if($resdata['status'] != 0){
		log_message('ERROR', "err i4 login check, ret:".$resdata['errcode']." token=".$token );
		return false;
	}

	log_message('NOTI', "i4 login userid :".$resdata['userid']);
	return array('Account' => $resdata['userid']);
}

function my_pre_login_chk_itools($token) {
	$arr = explode(";", $token);
	return array('Account' => $arr[0]);
}

function my_pre_login_chk_xy($token) {
	$arr = explode(";", $token);
	return array('Account' => $arr[0]);
}

function my_pre_login_chk_dangle_android($token) {
	$auth_url = "http://connect.d.cn/open/member/info/";
	$app_id = "2837";
	$app_key = "Z056Oo00";
	list($mid, $tokenStr) = explode(";", $token);
	$sign = md5($tokenStr."|".$app_key);

	$auth_get_params = array ('app_id' => $app_id, 'mid' => $mid, 'token' => $tokenStr, 'sig' => $sign);
	$response_data = request_by_curl($auth_url, $auth_get_params);
	log_message('NOTI', "dangle android login chk_res:".var_export($response_data, true));
	$resdata = json_decode($response_data, true);
	if($resdata['error_code'] != 0 || !isset($resdata['memberId'])){
		log_message('ERROR', "err dangle android login check, ret:".$resdata['error_code']." token=".$token );
		return false;
	}

	log_message('NOTI', "dangle android login userid :".$resdata['memberId']);
	return array('Account' => $resdata['memberId']);
}

function my_pre_login_chk_dangle_ios($token) {
	$auth_url = "http://connect.d.cn/open/member/info/";
	$app_id = "2838";
	$app_key = "CIzhdcMc";
	list($mid, $tokenStr) = explode(";", $token);
	$sign = md5($tokenStr."|".$app_key);

	$auth_get_params = array ('app_id' => $app_id, 'mid' => $mid, 'token' => $tokenStr, 'sig' => $sign);
	$response_data = request_by_curl($auth_url, $auth_get_params);
	log_message('NOTI', "dangle ios login chk_res:".var_export($response_data, true));
	$resdata = json_decode($response_data, true);
	if($resdata['error_code'] != 0 || !isset($resdata['memberId'])){
		log_message('ERROR', "err dangle ios login check, ret:".$resdata['error_code']." token=".$token );
		return false;
	}

	log_message('NOTI', "dangle ios login userid :".$resdata['memberId']);
	return array('Account' => $resdata['memberId']);
}

function my_pre_login_chk_vivo($token) {
	$auth_url = "https://usrsys.inner.bbk.com/auth/user/info";
	$auth_post_params = array ('access_token' => $token);
	$auth_get_params = array ();
	$response_data = request_by_curl($auth_url, $auth_get_params, $auth_post_params);
	log_message('NOTI', "vivo login chk_res:".var_export($response_data, true));
	$resdata = json_decode($response_data, true);
	if(!isset($resdata['uid'])){
		log_message('ERROR', "err vivo login check, ret:".$resdata['stat']." token=".$token );
		return false;
	}

	return array('Account' => $resdata['uid']);
}

function my_pre_login_chk_shuyou($token) {
	$auth_url = "http://sdk.07073sy.com/index.php/User/v3";
	$app_id = "134";
	$app_key = "MMsHIVGUi8890.td66c45KMH";
	list($username, $session) = explode(";", $token);
	$sign = md5("pid=".$app_id."&sessionid=".$session."&username=".$username.$app_key);

	$auth_get_params = array ();
	$auth_post_params = array ('username' => $username, 'sessionid' => $session, 'pid' => $app_id, 'sign' => $sign);
	$response_data = request_by_curl($auth_url, $auth_get_params, $auth_post_params);
	log_message('NOTI', "shuyou login chk_res:".var_export($response_data, true));
	$resdata = json_decode($response_data, true);
	if($resdata['state'] != 1){
		log_message('ERROR', "err shuyou login check, ret:".$resdata['state'].", msg:".$resdata['msg'].", token=".$token );
		return false;
	}

	log_message('NOTI', "shuyou login userid :".$username);
	return array('Account' => $username);
}

function my_pre_login_chk_yyh($token) {
	$auth_url = "http://api.appchina.com/appchina-usersdk/user/get.json";
	$app_id = "10538";
	$app_key = "pHU0j8TdwxK1IAkD";

	$auth_get_params = array ('app_id' => $app_id, 'app_key' => $app_key, 'ticket' => $token);
	$response_data = request_by_curl($auth_url, $auth_get_params);
	log_message('NOTI', "yyh login chk_res:".var_export($response_data, true));
	$resdata = json_decode($response_data, true);
	if($resdata['status'] != 0 || !isset($resdata['data']['ticket'])){
		log_message('ERROR', "err yyh login check, ret:".$resdata['status']." ticket=".$token );
		return false;
	}

	log_message('NOTI', "yyh login userid :".$resdata['data']['ticket']);
	return array('Account' => $resdata['data']['ticket']);
}

function my_pre_login_chk_4399($token) {
	$auth_url = "http://m.4399api.com/openapi/oauth-check.html";
	$app_key = "101240";
	$secrect = "";
	list($uid, $state) = explode(";", $token);
	$sign = md5($app_key.$uid.$state.$secrect);

	$auth_get_params = array ('uid' => $uid, 'state' => $state, 'key' => $app_key, 'sign' => $sign);
	$response_data = request_by_curl($auth_url, $auth_get_params);
	log_message('NOTI', "4399 login chk_res:".var_export($response_data, true));
	$resdata = json_decode($response_data, true);
	if(!isset($resdata['code']) || $resdata['code'] != 100){
		log_message('ERROR', "err 4399 login check, ret:".$resdata['code'].", msg:".$resdata['message'].", token=".$token );
		return false;
	}

	log_message('NOTI', "4399 login userid :".$uid);
	return array('Account' => $uid);
}

function my_pre_login_chk_jifeng($token) {
	$auth_url = "http://api.gfan.com/uc1/common/verify_token";

	$auth_get_params = array ('token' => $token);
	$response_data = request_by_curl($auth_url, $auth_get_params);
	log_message('NOTI', "jifeng login chk_res:".var_export($response_data, true));
	$resdata = json_decode($response_data, true);
	if(!isset($resdata['resultCode']) || $resdata['resultCode'] != 1){
		log_message('ERROR', "err jifeng login check, ret:".$resdata['resultCode'].", token=".$token );
		return false;
	}

	log_message('NOTI', "jifeng login userid :".$resdata['uid']);
	return array('Account' => $resdata['uid']);
}

//百度登陆校验(android)
function my_pre_login_chk_bd($token) {
	$arr = explode(";", $token);
	return array('Account' => $arr[0]);
}

function my_pre_login_chk_ppw($token) {
	$arr = explode(";", $token);
	$username = $arr[0];
	$appId = 11791421917323;
	$merchantId = 1179;
	$merchantAppId = 1210;
	$sid = $arr[1];
	$time = $arr[2];
	$auth_url = "http://sdk.pipaw.com/appuser/Checksid";

	log_message('NOTI', "pipawang login check, ret:"." username=".$username );
	log_message('NOTI', "pipawang login check, ret:"." sid=".$sid );
	$auth_post_params = array ('username' => $username, 'appId' => $appId, 'merchantId' => $merchantId, 'merchantAppId' => $merchantAppId, 'sid' => $sid, 'time' => $time);
	$auth_get_params = array ();
	//$post_str = json_encode($auth_post_params);
	$response_data = request_by_curl($auth_url, $auth_get_params, $auth_post_params);
	log_message('NOTI', "pipawang login chk_res:".var_export($response_data, true));
	$resdata = json_decode($response_data, true);
	if(!($resdata['result'])){
		log_message('ERROR', "err pipawang login check, ret:".$resdata['username']." username=".$username );
		return false;
	}

	return array('Account' => $resdata['uid']);
}

}
?>
