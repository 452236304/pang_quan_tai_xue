<?php
namespace Admin\Controller;
use Think\Controller;
class ImgController extends BaseController {
	function recycle_list(){
		$type=I('get.type');
		if(empty($type)){
			$type='images';
		}
		$path = './upload/userfiles/recycle/'.$type.'/';
		$handle = opendir($path);
		$list=array();
		while (false !== ($file = readdir($handle))) { 
			if (!is_dir('./'.$file)) {
				$list[]='/upload/userfiles/recycle/'.$type.'/'.$file;
			}
		
		}
		$this->assign('list',$list);
		$this->display();
	}
	function restore(){
		$url=I('post.url');
		$type=I('post.type');
		if(empty($url)){
			$this->error('请选择还原的图片');
		}
		$newpath='./upload/userfiles/'.$type.'/'.basename($url);
		return rename('.'.$url,$newpath);
		
	}
	function del(){
		$url=I('post.url');
		if(empty($url)){
			$this->error('请选择删除的图片');
		}
		return unlink('.'.$url);
	}
}