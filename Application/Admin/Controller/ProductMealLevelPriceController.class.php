<?php
namespace Admin\Controller;
use Think\Controller;
class ProductMealLevelPriceController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("product_meal_level_price");
        $productid = I("get.productid", 0);
        $map = array("pmpl.productid"=>$productid);
        $data = $model->alias("pmpl")->join("left join sj_product p on pmpl.productid=p.id")
            ->field("pmpl.*")->where($map)->order("meal_level asc")->select();
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
        $model = D("product_meal_level_price");

        if($doinfo == "modify"){
            $productid = I("get.productid");
            if(empty($productid)){
                alert_back("请选择要关联的商品");
            }
            $d["productid"] = $productid;
            $d["status"] = I("post.status", 1);
            $meal_level = I("post.meal_level", 1);
            $map = array("productid"=>$productid, "meal_level"=>$meal_level);
            $checkprice = $model->where($map)->find();
            if($checkprice && empty($id)){
                alert_back("当前商品已经添加过".getMealLevel($meal_level)."餐次，请勿重复添加");
            }
            $d["meal_level"] = $meal_level;
            $d["price"] = I("post.price", 0);
            $d["remark"] = I("post.remark");

            if($id == 0){
                $d["updatetime"] = date("Y-m-d H:i");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("ProductMealLevelPrice/listad", $this->getMap());
        }

        $data["info"] = $model->find($id);
        $this->assign($data);
        $this->assign("map", $this->getMap());
    	$this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("product_meal_level_price");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("ProductMealLevelPrice/listad", $this->getMap());
    }

    public function getMap(){
        $productid = I("get.productid");
        $p = I("get.p");
        $map = array("productid"=>$productid,"p"=>$p);
        return $map;
    }
}