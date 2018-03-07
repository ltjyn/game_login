<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ',							'rb');
define('FOPEN_READ_WRITE',						'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE',					'ab');
define('FOPEN_READ_WRITE_CREATE',				'a+b');
define('FOPEN_WRITE_CREATE_STRICT',				'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');

//各渠道号定义
require_once (dirname(__FILE__).'/'.'channels.php');

/*
//非腾讯的外发平台
define('XIAOMI_CHANNEL', '18320001');//安卓小米渠道(腾讯)
define('BAIDU_CHANNEL', '18420001');//安卓百度渠道(腾讯)
define('KAKAO_CHANNEL', '18520001');//安卓KAKAO渠道 韩国
define('LENOVO_CHANNEL', '200000174');//安卓联想渠道(腾讯)
//define('QQ_UC_CHANNEL', '2057');//安卓UC渠道(从未发布)

//android渠道
define('MUYOU_CHANNEL', '86100000');//安卓木游自主渠道
define('MZW_CHANNEL', '86100001');	//安卓拇指玩渠道
define('Q360_CHANNEL', '86100002');	//安卓奇虎360渠道, 宏不能以数字开头 
define('WDJ_CHANNEL', '86100003');	//豌豆荚渠道 
define('OPPO_CHANNEL', '86100004');	//OPPO渠道
define('JINLI_CHANNEL', '86100005');//金立渠道
define('ANZHI_CHANNEL', '86100006');//安智渠道
define('HUAWEI_CHANNEL', '86100007');//华为渠道
define('UC_CHANNEL', '86100008');//UC渠道(联运,非腾讯)
define('XM_CHANNEL', '86100009');//小米渠道(联运,非腾讯)
define('BD_CHANNEL', '86100010');//百度渠道(联运,非腾讯)
define('LX_CHANNEL', '86100011');//联想渠道(联运,非腾讯)
define('NDUO_CHANNEL', '86100012');//N多市场渠道
define('DANGLE_CHANNEL', '86100013');//当乐渠道,(另有ios渠道)

//ios渠道
define('APPLE_CHANNEL', '86200001');	//苹果appstore官方渠道
define('BD91_CHANNEL', '86200002');	//91平台渠道，不能以数字开头
define('TBT_CHANNEL', '86200003');	//同步推渠道
define('KY_CHANNEL', '86200004');	//快用
define('PP_CHANNEL', '86200005');	//pp
define('ITOOLS_CHANNEL', '86200006');	//ITOOLS
define('I4_CHANNEL', '86200007');	// IOS爱思渠道
define('AIBEI_CHANNEL', '86200008');	// IOS爱贝渠道
define('XY_CHANNEL', '86200009');	// XY爱贝渠道
define('IDANGLE_CHANNEL', '86200010');//ios当乐渠道(另有android渠道)
*/

define('LENOVO_UPDATE_URL', 'http://app.lenovo.com/appstore/psl/com.tencent.tmgp.gedou.lenovo');//联想版本更新地址

//内网环境
define('INNER_TEST_LOGIN_URL', 'http://192.168.1.210:8088/index.php?c=login&');//测试登陆链接(内网)
define('INNER_LOGIN_URL', 'http://192.168.1.210:8088/index.php?c=login&');//登陆链接(内网)
#define('INNER_UPDATE_URL', 'http://dd.myapp.com/16891/8B7FFE5D7695672AA77BDD7D61A95279.apk?fsname=com.tencent.tmgp.gedou_1.0.1.6_5.apk');
define('INNER_UPDATE_URL', 'http://app.qq.com/#id=detail&appid=1000000477');
define('INNER_BULLETIN_URL', 'http://192.168.1.210:8088/index.htm');//登陆页公告链接(内网)
define('INNER_BULLETIN2_URL', 'http://192.168.1.210:8088/index.xml');//登陆页公告链接(内网)
define('INNER_SHARE_ICON_URL', 'http://192.168.1.210:8088/img/Icon-72.png');//分享所需icon(内网)

//Android环境
define('ANDR_TEST_LOGIN_URL', 'http://test_alogin.gz.1251021720.clb.myqcloud.com/index.php?c=login&');//登陆链接(外网Android测试)
define('ANDR_LOGIN_URL', 'http://alogin.gz.1251021720.clb.myqcloud.com/index.php?c=login&');//登陆链接(外网Android)
#define('ANDR_UPDATE_URL', 'http://dd.myapp.com/16891/8B6E39294A1D420D30A5D1AFE9F7774D.apk?fsname=com.tencent.tmgp.gedou_1.0.5_8.apk');
define('ANDR_UPDATE_URL', 'http://app.qq.com/#id=detail&appid=1000000477');
define('ANDR_BULLETIN_URL', 'http://abulletin.gz.1251021720.clb.myqcloud.com/index.htm');//登陆页公告链接
define('ANDR_BULLETIN2_URL', 'http://abulletin.gz.1251021720.clb.myqcloud.com/index.xml');//登陆页公告链接
define('ANDR_SHARE_ICON_URL', 'http://abulletin.gz.1251021720.clb.myqcloud.com/img/Icon-72.png');//分享所需icon

//iOS环境
define('IOS_TEST_LOGIN_URL', 'http://test_ilogin.gz.1251021720.clb.myqcloud.com/index.php?c=login&');//登陆链接(外网iOS测试)
define('IOS_LOGIN_URL', 'http://ilogin.gz.1251021720.clb.myqcloud.com/index.php?c=login&');//登陆链接(外网iOS)
#define('IOS_UPDATE_URL', 'http://mobgame.qq.com/zone/qmgd/');//iOS平台暂时用官网代替//TODO
define('IOS_UPDATE_URL', 'http://app.qq.com/#id=detail&appid=1000000477');
define('IOS_BULLETIN_URL', 'http://ibulletin.gz.1251021720.clb.myqcloud.com/index.htm');//登陆页公告链接
define('IOS_BULLETIN2_URL', 'http://ibulletin.gz.1251021720.clb.myqcloud.com/index.xml');//登陆页公告链接
define('IOS_SHARE_ICON_URL', 'http://ilogin.gz.1251021720.clb.myqcloud.com/img/Icon-72.png');//分享所需icon

//支付url配置，iOS和安卓都需配置, 用于登陆时校验支付token是否过期
define('ENV_FLAG', 0);//0 内网，1 外网
if (ENV_FLAG == 0) {
	define('IOS_PAY_URL', 'http://192.168.1.210:8069/index.php');//iOS内网支付url
	define('ANDR_PAY_URL', 'http://192.168.1.210:8089/index.php');//安卓内网支付url
	define('TENCENT_SDK_URL', 'http://opensdktest.tencent.com');//内网 登陆校验 沙箱模式
	//define('UC_SDK_URL', 'http://sdk.test4.g.uc.cn/ss');//UC Dev模式
} else {
	define('IOS_PAY_URL', 'http://10.221.25.1:8000/index.php');//iOS外网支付url
	define('ANDR_PAY_URL', 'http://10.221.21.185:8000/index.php');//安卓外网支付url
	define('TENCENT_SDK_URL', 'http://opensdk.tencent.com');//外网 登陆校验 沙箱模式
	//define('UC_SDK_URL', 'http://sdk.g.uc.cn/ss');//UC 正式环境
}
//opensdk配置(用于登陆校验)
define('APPID_TENCENT', '1000000477');//手Q的AppID(安卓和ios登陆共用)
define('APPKEY_TENCENT', 'RR6uT8OCEcM31mXH');//手Q的Appkey(安卓和ios共用)
define('GUEST_APPID_TENCENT', 'G_1000000477');
define('GUEST_APPKEY_TENCENT', 'G_RR6uT8OCEcM31mXH');

//当前配置
define('UPDATE_URL', ANDR_UPDATE_URL);//版本更新地址
define('BULLETIN_URL', INNER_BULLETIN_URL);//登陆页公告链接
define('BULLETIN2_URL', INNER_BULLETIN2_URL);//登陆页公告链接
define('SHARE_ICON_URL', ANDR_SHARE_ICON_URL);//分享所需icon
define('LOGIN_URL', INNER_LOGIN_URL);//登陆链接
define('TEST_LOGIN_URL', INNER_TEST_LOGIN_URL);//测试登陆链接(版本号高于LATEST_CLI_VERSION时使用)

/* 版本号规则: v0.2.9.23 => 00 02 09 23 => 20923
 */
define('LATEST_CLI_VERSION', 21010);//最新版本,版本号高于最新版本的为测试版本,返回测试url
define('REQUIED_CLI_VERSION', 21010);//强制全量更新最低版本Ex: v0.2.9.23 => 00 02 09 23 => 20923

//so更新, iOS不支持热更新so
$g_so_updates = array (
	'qq' => array(
		'so_ver' => 21019, //更新后so版本号(即客户端程序版本号)
    	//'url'=>'http://192.168.1.210:8088/update_so/libgame14.so.zip',
    	//'md5'=>'0ec7b807231c4660b55c41b670be1d6d',
		'url'=>'http://1251021720.cdn.myqcloud.com/1251021720/update_so/libgame13_1.so.zip',
    	'md5'=>'57fcc133c3f30ad8f695a372755dbf24',
	),
	'qq2' => array(
		'so_ver' => 21019, //更新后so版本号(即客户端程序版本号)
		'url'=>'http://1251021720.cdn.myqcloud.com/1251021720/update_so/libgame14.so.zip',
    	'md5'=>'57fcc133c3f30ad8f695a372755dbf24',
	),
	'xm' => array(
		'so_ver' => 21019, //更新后so版本号(即客户端程序版本号)
    	//'url'=>'http://192.168.1.210:8088/update_so/libgame13.so.xm.zip',
    	//'md5'=>'58bdedace5de0c5af3b4926f8c32ed5b',
		'url'=>'http://1251021720.cdn.myqcloud.com/1251021720/update_so/libgame13.so.xm.zip',
    	'md5'=>'58bdedace5de0c5af3b4926f8c32ed5b',
	),
	'bd' => array(
		'so_ver' => 21019, //更新后so版本号(即客户端程序版本号)
    	//'url'=>'http://192.168.1.210:8088/update_so/libgame13_1.so.zip',
    	//'md5'=>'57fcc133c3f30ad8f695a372755dbf24',
		'url'=>'http://1251021720.cdn.myqcloud.com/1251021720/update_so/libgame13_1.so.zip',
    	'md5'=>'57fcc133c3f30ad8f695a372755dbf24',
	),
);

/* End of file constants.php */
/* Location: ./application/config/constants.php */
