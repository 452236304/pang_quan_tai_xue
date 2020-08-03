<?php
namespace Admin\Controller;
use Think\Controller;
class ServiceOrderRefundController extends BaseController {

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $orderid = I("get.orderid", 0);
        $doinfo = I("get.doinfo");
        $model = D("service_order_refund");
        $data["info"] = $model->where('orderid='.$orderid)->find();
        $order = D("service_order")->where('id='.$orderid)->find();

        if($doinfo == "modify"){
            if($data["info"]["status"] == 4){
                alert_back('订单售后已完成，请勿重复提交');
            }
            
            $refund_money = I("post.refund_money", 0);
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
                    $info = array('ordertype'=>3, 'orderid'=>$orderid, 'refund_money'=>$refund_money);
                    $this->pass($info);
                    $ret = '退款成功';
                    D('CApi/Brokerage', 'Service')->orderRefund(1, $orderid);
                    //推送消息
                    $this->message($order);
                }

                $model->where("orderid=".$orderid)->save($d);
            }

            alert($ret,U('ServiceOrderRefund/modifyad','orderid='.$orderid));

        }

        $this->assign($data);
        $this->assign('order',$order);
        $this->show();
    }

    protected function message($order){
        if (empty($order)) {
            return false;
        }
        $messagemodel = D("user_message");
        //用户订单消息
        if ($order["service_userid"] > 0) {
            $profile = D("user_profile")->where('userid='.$order["service_userid"])->find();
        }
        $content = '<p>';
        $content .= "【订单内容】：".$order['title']."<br/>";
        $content .= "【服务人员】：".$profile["realname"]."<br/>";
        $content .= "【联系方式】：".substr_replace($profile["mobile"],'****',3,4)."<br/>";
        $content .= "【订单号】：".$order["sn"]."<br/>";
        if($order["other_remark"]){
            $content .= "【用户备注】：".$order["other_remark"]."<br/>";
        }
        if($order["platform_money"] > 0){
            $content .= "【平台补贴】：".$order["platform_money"]."元<br/>";
        }
        $content .= "您的订单成功退款。祝您身体健康，生活愉快！";
        $content .= '</p>';
        $message_entity = array(
            "userid"=>$order["userid"], "title"=>$order["title"], "content"=>$content,
            "hybrid"=>"client", "type"=>1, "status"=>0, "createdate"=>date("Y-m-d H:i:s")
        );
        $messagemodel->add($message_entity);
		
		//短信提醒
		$user_info=D('user')->field('mobile')->where('id='.$order['userid'])->find();
		D('Common/RequestSms')->SendRefund($user_info);
		
		
        if ($order["service_userid"] > 0) {
            //通知服务人员
            $content = '<p>';
            $content .= "【订单内容】：".$order['title']."<br/>";
            $content .= "【服务地址】：".$order["province"].$order["city"].$order["region"].$order["address"]."<br/>";
            $content .= "【服务时间】：".$order["begintime"].' / '.$order['endtime']."<br/>";
            if($order["other_remark"]){
                $content .= "【用户备注】：".$order["other_remark"]."<br/>";
            }
            if($order["platform_money"] > 0){
                $content .= "【平台补贴】：".$order["platform_money"]."元<br/>";
            }
            $content .= "以上订单正在退款中，请暂停服务，谢谢。";
            $content .= '</p>';
            $message_entity = array(
                "userid"=>$order["service_userid"], "title"=>$order['title'], "content"=>$content,
                "hybrid"=>"service", "type"=>1, "status"=>0, "createdate"=>date("Y-m-d H:i:s")
            );
            $messagemodel->add($message_entity);
        }

        return true;
    }
}