<?php
return array(
	//'配置项'=>'配置值'
    'url_model'          => '1', //URL模式
    'MODULE_ALLOW_LIST'  => array('Admin','Payment','CApi','SApi','Home','Store'),
    'DB_TYPE'   => 'mysqli', // 数据库类型
//    'DB_HOST'   => '120.79.93.100', // 服务器地址
//    'DB_NAME'   => 'baochun-dev4', // 数据库名
//    'DB_USER'   => 'dev4', // 用户名
//    'DB_PWD'    => '123456',  // 密码
    'DB_HOST'   => '127.0.0.1', // 服务器地址
    'DB_NAME'   => 'pang_quan_tai_xue', // 数据库名
    'DB_USER'   => 'root', // 用户名
    'DB_PWD'    => '123456',  // 密码
    'DB_PORT'   => '3306', // 端口
    'DB_PREFIX' => 'sj_', // 数据库表前缀
    'URL_CASE_INSENSITIVE' => true, //忽略url地址大小写
	'TMPL_CACHE_ON' => false,//禁止模板编译缓存
	'HTML_CACHE_ON' => false,//禁止静态缓存
    'pwd_key' => 'BAOCHUN++', //密码加密集成字符

);