<?php
namespace Admin\Controller;
use Think\Controller;
class XcxController extends BaseController {
    /*
     * sysconfig	系统配置项
     */
	public function config(){
		$doinfo = I("get.doinfo");
		if($doinfo=="config"){
			$data = I('post.');
			F('xcx_config',$data);
			echo "<script>alert('修改成功!');location.href='".U('Xcx/config')."';</script>";
			exit();
		}
		$info = F('xcx_config');
		$this->assign('info',$info);
		$this->show();
	}



}