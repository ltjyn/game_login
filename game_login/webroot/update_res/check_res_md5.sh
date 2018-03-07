
if [[ $1 == "" ]]; then
	echo "USAGE: $0 <md5.txt>";
	exit 
fi

md5file=$1
md5sum $md5file 

nf=2 #域数大于2则输出 
cat $md5file | awk "NF>$nf{num+=1}END{if (num<=0) print \"OK!\"; else print num \" ERR files!\"}NF>$nf" 
