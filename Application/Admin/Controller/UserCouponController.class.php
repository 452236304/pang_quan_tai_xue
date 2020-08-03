<?php
namespace Admin\Controller;
use Think\Controller;
class UserCouponController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("user_coupon");
        $map = array("userid"=>I("get.userid", 0));
        $data = $model->where($map)->order("use_end_date desc, use_start_date asc")->select();
        $this->assign("data", $data);
        $this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("user_coupon");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("UserCoupon/listad", $this->getMap());
    }
}