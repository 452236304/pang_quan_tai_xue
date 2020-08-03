<?php
namespace Admin\Controller;
use Think\Controller;
class PointShopController extends BaseController {
    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $param = $this->getMap();
        if($param["keyword"]){
            $map["title"] = array("like","%".$param["keyword"]."%");
        }
		$map['del_time']=array('eq','0');
        $data = $this->pager('point_shop', "10", "ordernum asc,createtime desc", $map);
        $this->assign($data);
        $this->assign("map", $param);
        $this->display();
    }
    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $model = D("point_shop");

        if($doinfo == "modify"){
            $d["type"] = I("post.type", 0);
            $d["status"] = I("post.status", 1);
			$d["object_id"] = I("post.object_id");
			$d["attribute_id"] = I("post.attribute_id",0);
			
			if($type==0){
				//商品
				$product = D('product')->field('title')->where(array('id'=>$d['object_id']))->find();
				
				if($d['attribute_id'] == 0){
					$this->error('请选择规格');
				}
				
				$attribute = D('product_attribute')->field('title,subtitle,thumb')->where(array('id'=>$d['attribute_id'],'productid'=>$d['object_id']))->find();
				
				if(empty($attribute)){
					$this->error('规格不存在');
				}
				
				$title = $product['title'] . $attribute['title'];
				
				$subtitle = $attribute['subtitle'];
				
				$thumb = $attribute['thumb'];
				
			}elseif($type==1){
				//服务
				$service = D('service_project')->field('title,thumb')->where(array('id'=>$d['object_id']))->find();
				$thumb = $service['thumb'];
			}
			
            $d["title"] = $title;
			$d["subtitle"] = $subtitle;
            $d["thumb"] = $thumb;
            
            $d["price"] = I("post.price", 0);
			$d["point"] = I("post.point", 1);
			if($d['point']<0){
				$this->error('积分商城商品不能低于1积分。');
			}
			
			
            if($id == 0){
                $d["createtime"] = date("Y-m-d H:i:s");
            }
			
            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("PointShop/listad", $this->getMap());
        }

        $this->assign("map", $this->getMap());
    	$this->display();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("point_shop");
    	$id = I("get.id");
		$map = array('id'=>$id);
		$entity = array('del_time'=>time());
    	$model->where($map)->save($entity);
    	$this->redirect("PointShop/listad", $this->getMap());
    }

    public function getMap(){
        $keyword = I("get.keyword");
        $p = I("get.p");
        $map = array("p"=>$p,"keyword"=>$keyword);
        return $map;
    }
}