<?php
namespace Store\Controller;
use Think\Controller;
class ProductOrderAttachController extends BaseController {

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $orderid = I("get.orderid", 0);
        $model = D("order_attach");
        $map = array("orderid"=>$orderid);
        $data["info"] = $model->where($map)->find();
        $this->assign($data);
    	$this->show();
    }
}