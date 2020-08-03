<?php
namespace Admin\Controller;
use Think\Controller;
class RecommendActivityController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $param = $this->getMap();
		
        if($param["keyword"]){
            $where["title"] = array("like","%".$param["keyword"]."%");
            $where["subtitle"] = array("like","%".$param["keyword"]."%");
            $where["_logic"] = "or";
            $map["_complex"] = $where;
        }
        $count = D("recommend_activity")->where($map)->count();
        $model = D("recommend_activity");
        $data = $this->pager(array("mo"=>$model, "count"=>$count), "10", "id asc", $map);
        $this->assign($data);
        $this->assign("map", $param);
        $this->show();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $model = D("recommend_activity");

        if($doinfo == "modify"){
            $d["status"] = I("post.status", 1);
            $d["title"] = I("post.title");
            $d["subtitle"] = I("post.subtitle");
			$d["post_title"] = I("post.post_title");
			$d["post_subtitle"] = I("post.post_subtitle");
            $d["thumb"] = I("post.thumb");
			$d["post_image"] = I("post.post_image");
			$d["examine_image"] = I("post.examine_image");
			$d["product_id"] = I("post.product_id");
			$d["attribute_id"] = I("post.attribute_id");
			$d["store_id"] = implode(',',I("post.store_id"));
			$d["starttime"] = I("post.starttime");
			$d["endtime"] = I("post.endtime");
			
			if(empty($d["post_image"])){
				$this->error('请上传图片');
			}
			if(!is_http($d['post_image'])){
				if(!is_file('.'.$d['post_image'])){
					$this->error($d["post_image"].'图片路径无效');
				}
			}
			if(empty($d["examine_image"])){
				$this->error('请上传图片');
			}
			if(!is_http($d['examine_image'])){
				if(!is_file('.'.$d['examine_image'])){
					$this->error($d["examine_image"].'图片路径无效');
				}
			}
			
            $d["content"] = htmlspecialchars_decode(I("post.content"));
            $d["price"] = I("post.price", 0);
            $d["freight"] = I("post.freight", 0);
           
            if($id == 0){
                $d["createtime"] = date("Y-m-d H:i");
            }
			
            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("RecommendActivity/listad", $this->getMap());
        }
		
        $data["info"] = $model->find($id);
		$map = array('status'=>1);
        $data['product']=D('product')->where($map)->select();
		if($data['info']){
			$data['info']['store_id']=explode(',',$data['info']['store_id']);
			$map = array('productid'=>$data['info']['product_id']);
			$data['attribute']=D('product_attribute')->where($map)->select();
		}
		$map = array('status'=>1);
		$data['store']=D('store')->where($map)->select();
        $this->assign($data);

        $this->assign("map", $this->getMap());
    	$this->display();
    }
	public function applylist(){
	    $model = D("product_order");
	    $map = array('activity_id'=>I('get.id'));
	    $data = $model->where($map)->select();
		foreach($data as $k=>&$v){
			$map = array('id'=>$v['userid']);
			$user_info = D('user')->field('mobile')->where($map)->find();
			$v['user_mobile'] = $user_info['mobile'];
		}
	    $this->assign("data", $data);
	    $this->assign("map", $map);
	    $this->display();
	}
    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("recommend_activity");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("RecommendActivity/listad", $this->getMap());
    }
    public function getMap(){
        $keyword = I("get.keyword");
        $p = I("get.p");
        $map = array("p"=>$p, "keyword"=>$keyword);
        return $map;
    }
	public function get_attribute(){
		$product_id = I('get.product_id');
		$map = array('productid'=>$product_id);
		$list=D('product_attribute')->where($map)->select();
		foreach($list as $k=>$v){
			echo "<option value='{$v['id']}'>{$v['title']}</option>";
		}
		exit;
	}
}