<?php
namespace Home\Controller;

use Think\Controller;

class BaseController extends Controller
{
    public function _before_index()
    {
        // 实例化模块文件
        $this->requiceFile();
    }

    //补全访问链接地址
    protected function DoUrlHandle($thumb){
        if(!empty($thumb) && (strpos(strtolower($thumb), 'http://') === false && strpos(strtolower($thumb), 'https://') === false)){
            $http_type = "http://";
            if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')){
                $http_type = "https://";
            }
            return $http_type.$_SERVER['HTTP_HOST'].$thumb;
        }else{
            return $thumb;
        }
    }

    // 加载文件
    private function requiceFile()
    {
        if(file_exists("./Public/".MODULE_NAME."/css/".CONTROLLER_NAME.".css"))
        {
            $this->assign('requiceCss' , "<link rel='stylesheet' type='text/css' href='/Public/".MODULE_NAME."/css/".CONTROLLER_NAME.".css'>");
        }
        if(file_exists("./Public/".MODULE_NAME."/js/".CONTROLLER_NAME.".js"))
        {
            $this->assign('requiceJs' , "<script src='/Public/".MODULE_NAME."/js/".CONTROLLER_NAME.".js'></script>");
        }
    }
}