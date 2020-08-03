<?php
namespace Admin\Controller;
use Think\Controller;
class OrgOrderRefundController extends BaseController {

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $orderid = I("get.orderid", 0);
        $doinfo = I("get.doinfo");
        $model = D("org_order_refund");
        $data["info"] = $model->where('orderid='.$orderid)->find();
        $order = D("org_order")->where("id=".$orderid)->find();

        if($doinfo == "modify"){
            if($data["info"]["status"] == 4){
                alert_back('订单售后已完成，请勿重复提交');
            }
            
            $refund_money = I("post.refund_money");
            $d["refund_money"] = $refund_money;
            $d["status"] = I("post.status");
            $d["reason"] = I("post.reason");
            $d["feedback"] = I("post.feedback");
            $d["feedback_date"] = I("post.feedback_date");
            if (empty($d["feedback_date"])) {
                $d["feedback_date"] = date("Y-m-d H:i:s");
            }
            $ret = '保存成功';
            $d["adminid"] = $_SESSION['manID'];
            if($data["info"]['id'] > 0){
                if ($order['status'] == 5 && $d["status"] == 4) {
                    if ($refund_money <= 0) {
                        alert_back('退款金额不能小于0或等于0');
                    }
                    if ($refund_money > $order['amount']) {
                        alert_back('退款金额不能大于订单金额');
                    }
                    $info = array('ordertype'=>2, 'orderid'=>$orderid, 'refund_money'=>$refund_money);
                    $this->pass($info);
                    $ret = '退款成功';
                    D('CApi/Brokerage', 'Service')->orderRefund(3, $orderid);
                }

                $model->where("orderid=".$orderid)->save($d);
            }

            alert($ret,U('OrgOrderRefund/modifyad','orderid='.$orderid));

        }

        $this->assign($data);
        $this->assign('order',$order);
        $this->show();
    }
}