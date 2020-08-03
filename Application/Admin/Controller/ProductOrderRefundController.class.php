<?php
namespace Admin\Controller;
use Think\Controller;
class ProductOrderRefundController extends BaseController {

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $orderid = I("get.orderid", 0);
        $doinfo = I("get.doinfo");
        $model = D("product_order_refund");
        $data["info"] = $model->where('orderid='.$orderid)->find();

        if($doinfo == "modify"){
            if($data["info"]["status"] == 4){
                alert_back('订单售后已完成，请勿重复提交');
            }

            $d["type"] = I("post.type");
            $d["status"] = I("post.status");
            $d["refund_money"] = I("post.refund_money", 0);
            $d["reason"] = I("post.reason");
            $d["shipping_name"] = I("post.shipping_name");
            $d["shipping_number"] = I("post.shipping_number");
            $d["shipping_date"] = I("post.shipping_date");
            $d["feedback"] = I("post.feedback");
            $d["feedback_date"] = I("post.feedback_date");
            if (empty($d["feedback_date"])) {
                $d["feedback_date"] = date("Y-m-d H:i");
            }
            $ret = '保存成功';
            $d["adminid"] = $_SESSION['manID'];
            if($data["info"]['id'] > 0){
                $order = D("product_order")->where("id=".$orderid)->find();
                if ($d["type"] == 2 && $order['status'] == 5 && $d["status"] == 4) {
                    $ret = '退款成功';
					D('CApi/Brokerage', 'Service')->orderRefund(2, $orderid);
                } else if ($d["type"] == 1 && $data["info"]['status'] == 1 && $d["status"] == 4) {
                    $ret = '退货成功';
                    D('CApi/Brokerage', 'Service')->orderRefund(2, $orderid);
                }

                if ($d["refund_money"] > 0) {
                    $info = array('ordertype'=>1, 'orderid'=>$orderid, 'refund_money'=>$d["refund_money"]);
                    $this->pass($info);
					//短信提醒
					$user_info=D('user')->field('mobile')->where('id='.$order['userid'])->find();
					D('Common/RequestSms')->SendRefund($user_info);
                } else{
                    $r_order = array("status"=>6);
                    D("product_order")->where("id=".$order["id"])->save($r_order);
                }

                $model->where("orderid=".$orderid)->save($d);
            }

            alert($ret,U('ProductOrderRefund/modifyad','orderid='.$orderid));

        }

        $this->assign($data);
        $this->show();
    }
}