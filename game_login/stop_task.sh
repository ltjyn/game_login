#!/bin/bash                     

work_dir=$PWD
for i in `ps -ef|grep ${work_dir}|grep -v grep |awk '{print $2}'`
do
  kill -9 $i
done
echo "stop tasks"
