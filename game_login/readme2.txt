添加新渠道要做的修改
1.  controllers/login.php login_205()函数 中调用的my_pre_login_chk()
  		helplers/platform_login_helper.php

2. 新增渠道 的宏定义 config/constants.php

3. controllers/createbyplatform.php 中 登陆校验 分渠道处理 

---------------------------
添加测试账号白名单要做的修改:

1. 查出 玩家的username: 
	a. 由角色id 得到 账号id, 在gmtools查或者 USER_x.t_global_user_info 表中 userid -> account_id
    b. 由账号id 得到 username, 在USER_INFO_x.t_user_info_y表中 userid -> user_name
2. 修改./config/test_users.php
3. 重启 ./restart_task.sh
