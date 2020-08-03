<?php
namespace Store\Controller;
use Think\Controller;
class ProductOrderFreightController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("product_order_freight");
		if($this->checkpower('company/product')){
			$map = array('company_id'=>$_SESSION['storeID']);
		}else{
			$map = array();
		}
        $data = $model->where($map)->select();
        $this->assign("data", $data);
        $this->show();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $model = D("product_order_freight");
        $data["info"] = $model->find($id);

        if($doinfo == "modify"){
            $d["money"] = I("post.money");
            $d["full_amount"] = I("post.full_amount");
            $d["remark"] = I("post.remark");
            $d["createdate"] = date("Y-m-d H:i:s");
			if($this->checkpower('company/product')){
				$d['company_id'] = $_SESSION['storeID'];
			}else{
				$d['company_id'] = 0;
			}
            if($id > 0){
                $model->where("id=".$id)->save($d);
            } else{
                $model->add($d);
            }
            
    	    $this->redirect("ProductOrderFreight/listad");
        }

        $this->assign($data);
    	$this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("product_order_freight");
        $id = I("get.id");
    	$model->delete($id);
        
        //删除此运费模版下的所有商品关联
        $productmodel = D("product");
        $map = array("freightid"=>$id);
        $entity = array("freightid"=>0);
        $productmodel->where($map)->save($entity);

    	$this->redirect("ProductOrderFreight/listad");
    }
}