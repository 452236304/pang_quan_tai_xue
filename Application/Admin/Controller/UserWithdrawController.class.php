<?php
namespace Admin\Controller;
use Think\Controller;
class UserWithdrawController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("user_coupon");
        $status = I("get.status", -1);
        $this->assign("status", $status);
        $map = array(
            "uc.type"=>2, "uc.status"=>$status, "uc.use_end_date"=>array("egt", date("Y-m-d H:i")),
            "uc.referral_orderid"=>array("gt", 0)
        );
        $data = $model->alias("uc")->join("left join sj_user as u on uc.userid=u.id")
                ->join("left join sj_class_order as o on uc.referral_orderid=o.id")
                ->join("left join sj_user as ru on uc.referral_userid=ru.id")
                ->field("uc.id as couponid,uc.title as uctitle,uc.money,uc.use_end_date,uc.status as ucstatus,uc.userid,u.nickname,o.id as orderid,o.title as otitle,o.classid,ru.nickname as runickname,ru.id as ruuserid,u.aliyun_account,u.aliyun_name")
                ->where($map)->select();
        $this->assign("data", $data);
        $this->show();
    }

    /**
     * [pass]
     * @return [type] [description]
     */
    public function pass(){
    	$model = D("user_coupon");
        $id = I("get.id");
        $entity = array("status"=>3);
        $model->where("id=".$id)->save($entity);
    	$this->redirect("UserWithdraw/listad", array("status"=>2));
    }
    
}