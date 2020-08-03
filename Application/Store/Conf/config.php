<?php
return array(
	//'配置项'=>'配置值'
	'TMPL_PARSE_STRING' => array(
        '__IMG__'    => __ROOT__ . '/Public/Admin/img',
        '__CSS__'    => __ROOT__ . '/Public/Admin/css',
        '__JS__'     => __ROOT__ . '/Public/Admin/js',
        '__PLUGINS__'     => __ROOT__ . '/Public/Admin/plugins',
        '__TMPL__'   => __ROOT__ . '/Application/Admin/View',
        '__UPLOADS__'   => __ROOT__ . '/upload',
        '__WEBJS__'     => __ROOT__ . '/Public/Home/plugin/webuploader',
    ),
    'URL_CASE_INSENSITIVE' => true, //忽略url地址大小写
);