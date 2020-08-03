<?php
namespace SApi\Controller;
use Think\Controller\RestController;
class UploadFileController extends BaseController {
	
	//文件上传
	public function upload(){
		
		//接收图片
		$file = $this->ImageUpload('file', 'user', array("pptx","ppt","pdf","jpg","jpeg","png","xls","xlsx","doc","zip","rar","7z","txt"));
		if(!$file){
			E('文件上传失败，请重新尝试');
		}

		$file_link = $this->DoUrlHandle('/upload/user/'.$file);

		return array("file_link"=>$file_link);
	}
}