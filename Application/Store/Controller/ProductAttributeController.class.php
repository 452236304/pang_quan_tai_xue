<?php
namespace Store\Controller;
use Think\Controller;
class ProductAttributeController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("product_attribute");
        $productid = I('get.productid',0);
        $map = array('productid'=>$productid);
        $data = $model->where($map)->order("ordernum asc")->select();
        $this->assign("data", $data);
        $this->assign("map", $this->getMap());
        $this->show();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $model = D("product_attribute");
        $data["info"] = $model->find($id);

        $param = $this->getMap();

        if($doinfo == "modify"){
            $d["productid"] = $param["productid"];
            if(empty($d["productid"])){
                $this->error("请选择关联的商品");
            }
            $d["status"] = I("post.status", 1);
            $d["code"] = I("post.code");
            $d["title"] = I("post.title");
            $d["thumb"] = I("post.thumb");
			if(empty($d["thumb"])){
				$this->error('请上传图片');
			}
			if(!is_http($d['thumb'])){
				if(!is_file('.'.$d['thumb'])){
					$this->error($d["thumb"].'图片路径无效');
				}
			}
            $d["price"] = I("post.price", 0);
            $d["stock"] = I("post.stock", 0);
            $d["ordernum"] = I("post.ordernum", 0);
            $d["remark"] = I("post.remark");

            if($id == 0){
                $d["createdate"] = date("Y-m-d H:i");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("ProductAttribute/listad", $this->getMap());
        }

        $this->assign($data);
        $this->assign("map", $param);
    	$this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("product_attribute");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("ProductAttribute/listad", $this->getMap());
    }

    public function sortad(){
        $id = I("post.id");
        $ordernum = I("post.ordernum");
        if(count($id)>0){
            $model = D("product_attribute");
            foreach ($id as $key=>$val){
                $model->where("id=".$val)->setField("ordernum", $ordernum[$key]);
            }
            $this->redirect("ProductAttribute/listad", $this->getMap());
            exit();
        }else{
            $this->assign("jumpUrl", U("ProductAttribute/listad", $this->getMap()));
            $this->error("没有进行任何操作");
            exit();
        }
    }


    public function getMap(){
        $productid = I("get.productid");
        $map = array("productid"=>$productid);
        return $map;
    }
}