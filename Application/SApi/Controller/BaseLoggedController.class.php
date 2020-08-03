<?php
namespace SApi\Controller;
use Think\Controller;
class BaseLoggedController extends BaseController {
	
	/* 构造函数 begin */
	function __construct(){
		$this->CheckUserLogin = true;

		parent::__construct();
	}
	/* 构造函数 end */

	// 人脸识别
	protected function BdFace($checkface){
		$user = $this->AuthUserInfo;

		$userface = $user["profile"]["face"];
		if(empty($userface)){
			E("服务人员暂未进行人脸识别认证，操作失败", 100);
		}

		if(empty($checkface)){
			E("请上传服务人员的人脸识别照片");
		}

		$facemodel = D("Common/BdFace");
		$face = $facemodel->FaceMatch($checkface, $userface);
		if($face["result"] == "FAIL"){
			E($face["message"]);
		} else if($face["score"] < 80){ //符合度
			E("人脸识别用户与当前账号人脸识别认证不匹配，操作失败");
		}
	}

}