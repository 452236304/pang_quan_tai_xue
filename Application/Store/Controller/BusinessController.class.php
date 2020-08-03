<?php
namespace Store\Controller;
use Think\Controller;
class BusinessController extends BaseController {
    public function modifyad(){
        $id = $_SESSION['businessID'];
    	$doinfo = I("get.doinfo");
        $model = D("business");

        if($doinfo == "modify"){
            $d["title"] = I("post.title");
			$d["province"] = I("post.province");
			$d["city"] = I("post.city");
			$d["region"] = I("post.region");
			$d["address"] = I("post.address");
            $d["thumb"] = I("post.thumb");
			$d["head"] = I("post.head");
            if(empty($d["thumb"])){
            	$this->error('请上传图片');
            }
            if(!is_http($d['thumb'])){
            	if(!is_file('.'.$d['thumb'])){
            		$this->error($d["thumb"].'图片路径无效');
            	}
            }
			if(empty($d["head"])){
				$this->error('请上传图片');
			}
			if(!is_http($d['head'])){
				if(!is_file('.'.$d['head'])){
					$this->error($d["head"].'图片路径无效');
				}
			}

            $d["content"] = htmlspecialchars_decode(I("post.content"));
			
            if($id > 0){
                $model->where("id=".$id)->save($d);
            }
        }
		$map = array('id'=>$_SESSION['businessID']);
		$info = D('business')->where($map)->find();
		$this->assign('info',$info);
    	$this->display();
    }
}