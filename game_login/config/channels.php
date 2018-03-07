<?php
/*
 * 在这个文件里定义渠道号及渠道名缩写
 * 渠道名缩写，用于合成username: "tag"+"平台账号名"
 * tag必须设置，且要么为""(为兼容老渠道), 要么为3个字符且不重复
 */

//腾讯的渠道
define('TENCENT_CHANNEL_ID1', '73213123');  //应用宝
define('TENCENT_CHANNEL_ID2', '10000454');  //黄钻
define('TENCENT_CHANNEL_ID3', '2037');      //QQ浏览器
define('TENCENT_CHANNEL_ID4', '888889');    //腾讯游戏频道
define('TENCENT_CHANNEL_ID5', '200000026'); //腾讯游戏频道（门户）
define('TENCENT_CHANNEL_ID6', '200000027'); //腾讯游戏频道（微信）

//腾讯代发的外发平台
define('XIAOMI_CHANNEL', '18320001');//安卓小米渠道(腾讯)
define('BAIDU_CHANNEL', '18420001');//安卓百度渠道(腾讯)
define('LENOVO_CHANNEL', '200000174');//安卓联想渠道(腾讯)
//define('QQ_UC_CHANNEL', '2057');//安卓UC渠道(腾讯,从未发布)

//韩国
define('KAKAO_CHANNEL', '18520001');//安卓KAKAO渠道 韩国

//木游自有账号登陆渠道
define('AMUYOU_CHANNEL', '86300001');//安卓木游自有账号登陆渠道
define('IMUYOU_CHANNEL', '86300002');//iOS 木游自有账号登陆渠道

//android渠道 自营联运
define('MUYOU_CHANNEL', '86100000');//安卓木游自有账号登陆渠道
define('MZW_CHANNEL', '86100001');  //安卓拇指玩渠道
define('Q360_CHANNEL', '86100002'); //安卓奇虎360渠道, 宏不能以数字开头 
define('WDJ_CHANNEL', '86100003');  //豌豆荚渠道 
define('OPPO_CHANNEL', '86100004'); //OPPO渠道
define('JINLI_CHANNEL', '86100005');//金立渠道
define('ANZHI_CHANNEL', '86100006');//安智渠道
define('HUAWEI_CHANNEL', '86100007');//华为渠道
define('UC_CHANNEL', '86100008');//UC渠道(联运,非腾讯)
define('XM_CHANNEL', '86100009');//小米渠道(联运,非腾讯)
define('BD_CHANNEL', '86100010');//百度渠道(联运,非腾讯)
define('LX_CHANNEL', '86100011');//联想渠道(联运,非腾讯)
define('NDUO_CHANNEL', '86100012');//N多市场渠道
define('DANGLE_CHANNEL', '86100013');//当乐渠道,(另有ios渠道)
define('VIVO_CHANNEL', '86100014');//vivo渠道
define('SHUYOU_CHANNEL', '86100015');//数游渠道
define('SUNING_CHANNEL', '86100016');//苏宁渠道
define('YYH_CHANNEL', '86100017');//应用汇渠道
define('AI4399_CHANNEL', '86100018');//4399渠道
define('JIFENG_CHANNEL', '86100019');//机锋渠道
define('PPW_CHANNEL', '86100020');//琵琶网渠道
define('ZHUOYI_CHANNEL', '86100021');//卓易渠道

//ios渠道 自营联运
define('APPLE_CHANNEL', '86200001');    //苹果appstore官方渠道
define('BD91_CHANNEL', '86200002'); //91平台渠道，不能以数字开头
define('TBT_CHANNEL', '86200003');  //同步推渠道
define('KY_CHANNEL', '86200004');   //快用
define('PP_CHANNEL', '86200005');   //pp
define('ITOOLS_CHANNEL', '86200006');   //ITOOLS
define('I4_CHANNEL', '86200007');   // IOS爱思渠道
define('AIBEI_CHANNEL', '86200008');    // IOS爱贝渠道
define('XY_CHANNEL', '86200009');   // XY爱贝渠道
define('IDANGLE_CHANNEL', '86200010');//ios当乐渠道(另有android渠道)

const CHANNEL_UNCHECKED = 0;//0 未审核或者非法渠道
const CHANNEL_OPEN_WAIT = 1;//1 已审核渠道但还未到开放时间
const CHANNEL_OPENED = 2;//2 已审核且已经对外开放
//渠道名缩写，用于合成username: "tag"+"平台账号名"
class ChannelConfig {
	//@return: 0 ok, 1 渠道号未配置, 2 渠道已配置，但是有误
	public static function chk_valid_channel($channel_str) {
		if (self::$channels_checked != 1) {
			self::check_all_channels();
		}
		if (isset(self::$channels["$channel_str"])) {
			$channel = self::$channels["$channel_str"];
			if (isset($channel['invalid']) && $channel['invalid'] > 0) {
				return 2;//配置有误
			}
			return 0;//ok
		}
		return 1;//未配置
	}
	public static function get_channel_tag($channel_str, &$tag) {
		$ret = self::chk_valid_channel($channel_str);
		if ($ret === 0) {
			$tag = self::$channels["$channel_str"]["tag"];
		}
		return $ret;
	}

	//return: 0 未审核或者非法渠道, 1 已审核渠道但还未到开放时间, 2 已经对外开放
	public static function get_channel_status($channel_str) {
		//return 2;//内网测试均返回2，外网需注掉
		$ret = self::chk_valid_channel($channel_str);
		if ($ret === 0) {
			$cur_channel = self::$channels["$channel_str"];
			if (isset($cur_channel['status']) && $cur_channel['status'] != CHANNEL_UNCHECKED) {
				if ($cur_channel['status'] == CHANNEL_OPEN_WAIT 
						&& isset($cur_channel['open_time']) && time() >= strtotime($cur_channel['open_time'])) {
					return CHANNEL_OPENED;
				}
				return $cur_channel['status'];
			}
		}
		return CHANNEL_UNCHECKED;
	}

	//检查渠道标签是否应该为空
	private function is_null_tag_channel ($k, $v) {
		//原应用宝版本, apple官方渠道 tag必须为空
		//TODO:韩国kakao是否用兼容老版本?
		//TODO:itunes版本处理，需兼容老代码
		if ($k == APPLE_CHANNEL || $k == KAKAO_CHANNEL
			|| (isset($v['tencent']) && $v['tencent'] > 0)) {
			return true;
		}
	}

	public static function debug_print_all_channels() {
		echo "all_channels:".var_export(self::$channels, true)."\n";		
	}
	public static function debug_print_err_channels() {
		$err_channels = array();
		foreach (self::$channels as $k => $v) {
			if (isset($v['invalid']) && $v['invalid'] > 0) {
				$err_channels["$k"] = $v;
			}
		}
		echo "err_channels:".var_export($err_channels, true)."\n";
		unset($err_channels);
	}
	public static function debug_test_one_channel($tst_ch)
	{
		$tag = "";
		$ret = self::get_channel_tag($tst_ch, $tag);
		$status = self::get_channel_status($tst_ch);
		echo "channel:$tst_ch tag:$tag chk_ret:$ret status:$status\n";
	}

	//tag必须设置，且要么为"", 要么为3个字符且不重复
	public static function check_all_channels() {
		if (self::$channels_checked) {
			return ;
		}
		foreach (self::$channels as $k => $v) {
			//格式是否合法
			if (!isset($v['tag']) 
				|| ($v['tag'] !== "" && self::is_null_tag_channel($k, $v))
				|| (strlen($v['tag']) != 3 && !self::is_null_tag_channel($k, $v))
				) {
				self::$channels["$k"]['invalid'] = 1;
				continue;
			}
			//判定是否重复
			foreach (self::$channels as $k2 => $v2) {
				if (self::is_null_tag_channel($k2, $v2) || !isset($v2['tag']) || $v2['tag'] == "") {
					continue;
				}
				if ($k != $k2 && $v['tag'] == $v2['tag']) {
					self::$channels["$k"]['invalid'] = 1;
					break;
				}
			}
		}
		self::$channels_checked = 1;
		return ;
	}

	private static $channels_checked = 0;
	private static $channels = array (
		//格式: "渠道名" => array('tag' => '渠道缩写标签，限定为3个符', 'tencent' => '是否是原应用宝版本', 'status' => '0 未审核; 1 已经审核过 开放时间取决于open字段; 2 已经审核过且直接开放', 'opentime' => 'YYYY-mm-dd HH:ii:ss 开放时间，当status为1是有意义'),
		//'tag': 渠道缩写标签，限定为3个符，用于组合生成唯一username
		//'tencent': 是否是原应用宝版本: 1 是，且需要验证QQ或微信登陆; 2 是，但不需验证QQ或微信登陆

		//腾讯渠道, 为兼容老账号，tag为空-----------------------------------------------
		TENCENT_CHANNEL_ID1 => array('tag' =>'', 'tencent' => 1, 'status'=>2), //应用宝
		TENCENT_CHANNEL_ID2 => array('tag' =>'', 'tencent' => 1, 'status'=>2), //黄钻
		TENCENT_CHANNEL_ID3 => array('tag' =>'', 'tencent' => 1, 'status'=>2), //QQ浏览器
		TENCENT_CHANNEL_ID4 => array('tag' =>'', 'tencent' => 1, 'status'=>2), //腾讯游戏频道
		TENCENT_CHANNEL_ID5 => array('tag' =>'', 'tencent' => 1, 'status'=>2), //腾讯游戏频道(门户)
		TENCENT_CHANNEL_ID6 => array('tag' =>'', 'tencent' => 1, 'status'=>2), //腾讯游戏频道(微信)
		//腾讯代发的外发平台, 为兼容老账号，tag为空
		XIAOMI_CHANNEL => array('tag' =>'', 'tencent' => 2, 'status'=>2), //安卓小米渠道(腾讯)
		BAIDU_CHANNEL => array('tag' =>'', 'tencent' => 2, 'status'=>2), //安卓百度渠道(腾讯)
		LENOVO_CHANNEL => array('tag' =>'', 'tencent' => 2, 'status'=>2), //安卓联想渠道(腾讯)

		//韩国, 目前只有一个平台, tag 可为空也可不为空 --------------------------------
		KAKAO_CHANNEL => array('tag' => '', 'status'=>0, 'open_time'=>'2020-01-01 00:00:00'),//安卓KAKAO渠道 韩国

		//木游自有账号登陆渠道
		AMUYOU_CHANNEL => array('tag' => 'MYa', 'status'=>0, 'open_time'=>'2020-01-01 00:00:00'),	//安卓木游自有账号登陆渠道
		IMUYOU_CHANNEL => array('tag' => 'MYi', 'status'=>0, 'open_time'=>'2020-01-01 00:00:00'),	//iOS 木游自有账号登陆渠道

		//android渠道 自营联运 --------------------------------------------------------
		MUYOU_CHANNEL => array('tag' => 'MY_', 'status'=>2),	//安卓木游自有账号登陆渠道(仅腾讯测试用,已废弃,但前端坚持保留)
		MZW_CHANNEL => array('tag' => 'MZW', 'status'=>2), 		//安卓拇指玩渠道
		Q360_CHANNEL => array('tag' => 'QH_', 'status'=>2),		//安卓奇虎360渠道, 宏不能以数字开头 
		WDJ_CHANNEL => array('tag' => 'WDJ', 'status'=>2),		//豌豆荚渠道 
		OPPO_CHANNEL => array('tag' => 'OPP', 'status'=>2),		//OPPO渠道
		JINLI_CHANNEL => array('tag' => 'JL_', 'status'=>2),	//金立渠道
		ANZHI_CHANNEL => array('tag' => 'AZ_', 'status'=>2),	//安智渠道
		HUAWEI_CHANNEL => array('tag' => 'HW_', 'status'=>0, 'open_time'=>'2020-01-01 00:00:00'),	//华为渠道
		UC_CHANNEL => array('tag' => 'UC_', 'status'=>2),		//UC渠道(联运,非腾讯)
		XM_CHANNEL => array('tag' => 'XM_', 'status'=>2),		//小米渠道(联运,非腾讯)
		BD_CHANNEL => array('tag' => 'BD_', 'status'=>0, 'open_time'=>'2020-01-01 00:00:00'),		//百度渠道(联运,非腾讯)
		LX_CHANNEL => array('tag' => 'LX_', 'status'=>2),		//联想渠道(联运,非腾讯)
		NDUO_CHANNEL => array('tag' => 'NDU', 'status'=>2),		//N多市场
		DANGLE_CHANNEL => array('tag' => 'DL_', 'status'=>2),	//当乐渠道(另有ios渠道)
		VIVO_CHANNEL => array('tag' => 'VIV', 'status'=>2),		//vivo渠道
		SHUYOU_CHANNEL => array('tag' => 'SY_', 'status'=>2),	//数游渠道
		SUNING_CHANNEL => array('tag' => 'SN_', 'status'=>0, 'open_time'=>'2020-01-01 00:00:00'),	//苏宁渠道
		YYH_CHANNEL => array('tag' => 'YYH', 'status'=>2),		//应用汇渠道
		AI4399_CHANNEL => array('tag' => '439', 'status'=>0, 'open_time'=>'2020-01-01 00:00:00'),	//4399渠道
		JIFENG_CHANNEL => array('tag' => 'JF_', 'status'=>2),	//机锋渠道
		PPW_CHANNEL => array('tag' => 'PPW', 'status'=>2),		//琵琶网渠道
		ZHUOYI_CHANNEL => array('tag' => 'ZHY', 'status'=>2),	//卓易渠道

		//ios渠道 自营联运 -------------------------------------------------------------
		APPLE_CHANNEL => array('tag' => '', 'status'=>2),		//iOS 苹果appstore官方渠道,需兼容老版本,故tag为空
		BD91_CHANNEL => array('tag' => 'BDi', 'status'=>2),		//iOS 越狱91平台渠道，不能以数字开头
		TBT_CHANNEL => array('tag' => 'TBT', 'status'=>2),		//iOS 越狱同步推渠道
		KY_CHANNEL => array('tag' => 'KY_', 'status'=>2),		//iOS 越狱快用
		PP_CHANNEL => array('tag' => 'PP_', 'status'=>2),		//iOS 越狱PP
		ITOOLS_CHANNEL => array('tag' => 'ITL', 'status'=>2),	//iOS 越狱ITOOLS
		I4_CHANNEL => array('tag' => 'I4_', 'status'=>2),		//iOS 越狱爱思渠道
		AIBEI_CHANNEL => array('tag' => 'AiB', 'status'=>2),	//iOS 越狱爱贝渠道
		XY_CHANNEL => array('tag' => 'XY_', 'status'=>2),		//iOS 越狱XY渠道
		IDANGLE_CHANNEL => array('tag' => 'DLi', 'status'=>2),	//iOS 越狱当乐渠道(另有android渠道)
	);

};

ChannelConfig::check_all_channels();

//TEST: 以下函数有echo语句，生产环境慎用 !!!!!!!!!!
//TEST: 请另建新的php脚本做测试, 如 channels_test.php !!!!!!!!!!
//ChannelConfig::debug_print_all_channels();
//ChannelConfig::debug_print_err_channels();
//ChannelConfig::debug_test_one_channel("86100001");
