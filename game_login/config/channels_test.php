<?php
//该脚本用于对 channels.php 中的渠道配置进行测试
require ('channels.php');
$self_dir = (dirname(__FILE__));
echo "self_dir:$self_dir\n";

ChannelConfig::check_all_channels();

//TEST
//ChannelConfig::debug_print_all_channels();
ChannelConfig::debug_print_err_channels();

$tag = "";
$tst_ch = "aaaaa";
$ret = ChannelConfig::get_channel_tag($tst_ch, $tag);
echo "channel:$tst_ch tag:$tag chk_ret:$ret\n";

//ChannelConfig::debug_test_one_channel("86100001");
//ChannelConfig::debug_test_one_channel("86200001");
//ChannelConfig::debug_test_one_channel("861000011");
//ChannelConfig::debug_test_one_channel("86100018");

echo "-------beg---------\n";
$channel_checked = array('86100011','86100008','86200004','86100005','86100004','86200007','86200006','86100002','86100003', '86200009','86100012','86100009','86100001','86200003','86100015','86200010','86100013','86100006','86200002','86200008','86100017', '86200005','86100014','86100020','86100019','86100021');
foreach ($channel_checked as $ch_str) {
	ChannelConfig::debug_test_one_channel($ch_str);
}
echo "-------end---------\n";

