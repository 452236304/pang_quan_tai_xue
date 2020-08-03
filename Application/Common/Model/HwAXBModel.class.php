<?php
namespace Common\Model;

require_once "Application/Common/HwPN/AXB.php";

require_once "Application/Payment/Weixin/Extend/log.php";

class HwAXBModel{

     public function __construct(){
        //初始化日志
        $logHandler= new \CLogFileHandler("logs/hwpn/".date('Y-m-d').'.log');
        $log = \Log::Init($logHandler, 15);
    }

    public function Bind($num_a, $num_b){
        if(empty($num_a) || empty($num_b)){
            \Log::INFO("Bind：缺少必要的参数");
            return array("result"=>"FAIL", "message"=>"缺少必要的参数");
        }
        if(!isMobile($num_a) || !isMobile($num_b)){
            \Log::INFO("Bind：手机号码格式不正确");
            return array("result"=>"FAIL", "message"=>"手机号码格式不正确");
        }

        $model = new \Axb();

        foreach($model->xs as $k=>$x){
            $model->relationNum = $x;

            $result = $this->ResultCheck($model->bind($num_a, $num_b));
            if(in_array($result["data"]["resultcode"], ["1012007", "1012008", "1012009", "1012010"])){
                continue;
            }

            if($result["data"]["resultcode"] == "0"){
                break;
            }
        }

        return $result;
    }

    public function UnBind($subscriptionId){
        if(empty($subscriptionId)){
            \Log::INFO("UnBind：缺少必要的参数");
            return array("result"=>"FAIL", "message"=>"缺少必要的参数");
        }

        $model = new \Axb();

        return $this->ResultCheck($model->unbind($subscriptionId));
    }

    public function UnBindAll($relationNum){
        if(empty($relationNum)){
            \Log::INFO("UnBindAll：缺少必要的参数");
            return array("result"=>"FAIL", "message"=>"缺少必要的参数");
        }

        $model = new \Axb();

        $model->relationNum = $relationNum;

        $data = $this->ResultCheck($model->unbind());

        //清空服务订单中的号码隐私绑定关系
        if($data["result"] == "OK"){
            $ordermodel = D("service_order");

            $entity = array("pn_mobile"=>"", "pn_bind_id"=>"", "pn_status"=>0);

            $map = array("pn_mobile"=>$relationNum);
            $ordermodel->where($map)->save($entity);
        }

        return $data;
    }

    public function Update($subscriptionId, $num_a, $num_b){
        if(empty($subscriptionId) || empty($num_a) || empty($num_b)){
            \Log::INFO("Update：缺少必要的参数");
            return array("result"=>"FAIL", "message"=>"缺少必要的参数");
        }
        if(!isMobile($num_a) || !isMobile($num_b)){
            \Log::INFO("Update：手机号码格式不正确");
            return array("result"=>"FAIL", "message"=>"手机号码格式不正确");
        }

        $model = new \Axb();

        return $this->ResultCheck($model->update($subscriptionId, $num_a, $num_b));
    }

    public function Query($subscriptionId){
        if(empty($subscriptionId)){
            return array("result"=>"FAIL", "message"=>"缺少必要的参数");
        }

        $model = new \Axb();

        return $this->ResultCheck($model->query($subscriptionId));
    }

    private function ResultCheck($result){
        if(empty($result)){
            \Log::INFO("ResultCheck：请求结果异常，请稍后重试");
            return array("result"=>"FAIL", "message"=>"请求结果异常，请稍后重试");
        }

        if(is_string($result)){
            $result = json_decode($result, true);
        }

        if($result["resultcode"] != "0"){
            \Log::INFO("ResultCheck：请求结果错误，请稍后重试");
            return array("result"=>"FAIL", "message"=>"请求结果错误，请稍后重试", "data"=>$result);
        }

        return array("result"=>"OK", "data"=>$result);
    }

}