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