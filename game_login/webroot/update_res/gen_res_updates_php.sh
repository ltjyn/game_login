#查找形如vV1_V2.zip(Ex. v20928_20930.zip)的文件，并计算mdsum值
#生成的文件名形如 res_updates.r20930.php
#拷贝至 config/res_updates.php 替换原文件

if [[ $1 == "" ]]; then
    echo "USAGE: $0 <latest version num>";
    exit;
fi

if [[ -z `echo $1 | grep -E ^[0-9]\+$` ]]; then
    echo "version should be a number!";
	exit;
fi
lastest_ver=$1 #最新资源包版本号

url_prefix='http://192.168.1.210:8088/update_res'
outfile=res_updates.r$lastest_ver.php
cat <<EOT > $outfile
<?php
\$g_res_updates = array (
    'pkg_ver' => $lastest_ver, //更新后资源包版本号
EOT

lastest_ver=$1
files=`ls | grep -E ^v[0-9]\+_$lastest_ver.zip$`
for file in $files
do
    echo $file;
	ver=`expr $file : 'v\([0-9]\+\)_*'`
	echo $ver
	md5str=`md5sum $file | awk '{print $1}'`
	cat <<EOT >> $outfile 
    '$ver' => array (
        'url'=>'$url_prefix/v${ver}_${lastest_ver}.zip',
        'md5'=>'$md5str',
        ),
EOT
done

cat <<EOT >> $outfile 
    );
?>
EOT

