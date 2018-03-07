#/bin/bash

work_dir=$PWD
nohup php ${work_dir}/task.php > logs/task`date '+%Y%m%d_%H%M%S'`.log &

sleep 0.5 #wait the following files created
chown -R nobody.nobody logs
chmod -R 777 logs
echo "start tasks"
