<?php
namespace CApi\Controller;
use Think\Controller;
//发票管理
class InvoiceController extends BaseLoggedController
{
    /**
     * 开发票申请表单
     */
    public function submit()
    {
        $user = $this->AuthUserInfo;

        $data['invoice_type']= I('post.type', 0);
        $data['type']= I('post.type', 0);
        $data['head']= I('post.head');
        $data['number']= I('post.number');
        $data['bank_name']= I('post.bank_name');
        $data['bank_account']= I('post.bank_account');
        $data['company_phone']= I('post.company_phone');
        $data['company_addr']= I('post.company_addr');
        $data['user_id']= $user['id'];
        $data['user_name']= I('post.user_name');
        $data['user_phone']= I('post.user_phone');
        $data['user_addr']= I('post.user_addr');
        $data['user_mail']= I('post.user_mail');
        $data['add_time']= time();
        $order= I('post.order', '', 'strip_tags');

        if(empty($data['head']) || empty($data['number']) || empty($data['bank_name']) || empty($data['bank_account']) || empty($data['company_phone']) || empty($data['company_addr']) || empty($data['user_name']) || empty($data['user_phone']) || empty($data['user_addr']) || empty($data['user_mail']))
        {
            E("必填项请不要留空");
        }
        if(empty($order))
        {
            E("请选择订单开发票");
        }

        $invoicemodel = D('invoice');
        $service_ordermodel = D("service_order");
        $product_ordermodel = D("product_order");
        $order = json_decode($order, true);

        $amount = 0;
        $service_id = [];
        $product_id = [];
        foreach ($order as $v)
        {
            // 查看订单是否他本人,或是否已开发票
            $res = [];
            if($v['type'] == 1)
            {
                $res = $service_ordermodel->field('userid,sn,amount,invoice_id')->where("id={$v['id']}")->find();
            }
            else
            {
                $res = $product_ordermodel->field('userid,sn,amount,invoice_id')->where("id={$v['id']}")->find();
            }

            if(empty($res))
            {
                E("订单不存在");
                break;
            }
            if($res['userid'] != $user['id'])
            {
                E("订单号：{$res['sn']} 不是你的订单");
                break;
            }
            if($res['invoice_id'] > 0)
            {
                E("订单号：{$res['sn']} 已开发票");
                break;
            }


            $amount = bcadd($amount, $res['amount'], 2);
            if($v['type'] == 1)
            {
                array_push($service_id, $v['id']);
            }
            else
            {
                array_push($product_id, $v['id']);
            }
        }

        $data['amount'] = $amount;
        $insertId = $invoicemodel->add($data);
        if($insertId > 0)
        {
            $service_id = implode(',', $service_id);
            $service_ordermodel->where("id IN('{$service_id}')")->setField('invoice_id', $insertId);

            $product_id = implode(',', $product_id);
            $product_ordermodel->where("id IN('{$product_id}')")->setField('invoice_id', $insertId);
        }
        else
        {
            E('申请失败');
        }

        return E('申请成功');
    }

    /**
     * 获取申请发票列表
     */
    public function invoice()
    {
        $status = I('status', 0);
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;

        $user = $this->AuthUserInfo;

        $invoicemodel = D('invoice');
        $map = [];
        $map['user_id'] = $user['id'];
        $map['status'] = $status;
        $count = $invoicemodel->where($map)->count();
        $totalpage = ceil($count/$row);
        $invoice = $invoicemodel->where($map)->limit($begin, $row)->order('add_time DESC')->select();

        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        foreach ($invoice as $k => &$v)
        {
            $v['add_time'] = date('Y-m-d', $v['add_time']);
            $v['status_time'] = !empty($v['status_time']) ? date('Y-m-d', $v['status_time']) : '';
        }

        return $invoice;
    }

    // 获取商品已完成订单列表和服务已完成订单列表
    public function order()
    {
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page - 1) * $row;

        $list = array_merge($this->productorder(), $this->serviceorder());
        $createdate = array_column($list, 'createdate');
        array_multisort($list, SORT_DESC ,$createdate);

        $count = count($list);
        $totalpage = ceil($count/$row);
        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        $data = [];
        for($i = $begin; $i < $begin + $row; $i++)
        {
            if(empty($list[$i]))
            {
                continue;
            }
            array_push($data, $list[$i]);
        }

        return $data;
    }

    //商品已完成订单列表
    public function productorder(){
        $user = $this->AuthUserInfo;

        $ordermodel = D("product_order");

        //剔除已删除的订单
        $map = array("userid"=>$user["id"], "status"=>array("neq", -1));
//        $map["status"] = 4;
//        $map["pay_status"] = 3;
        $map["examine"] = 1;

        $list = $ordermodel->where($map)->where('invoice_id=0')->select();

        $orderproductmodel = D("product_order_product");
        $orderattachmodel = D("order_attach");

        foreach($list as $k=>$v){
            if ($v['type'] == 0) {
                $map = array("userid"=>$user["id"], "orderid"=>$v["id"]);
                $products = $orderproductmodel->alias("pop")->join("left join sj_product as p on pop.productid=p.id")
                    ->join("left join sj_attribute as a1 on p.attribute_cpid=a1.id")->join("left join sj_attribute as a2 on p.attribute_czid=a2.id")
                    ->field("pop.*,a1.name as cpname,a2.name as czname")->where($map)->select();
                foreach($products as $ik=>$iv){
                    $map = array('id'=>$iv['attributeid']);
                    $attribute=D('product_attribute')->where($map)->find();
                    $iv["thumb"] = $this->DoUrlHandle($attribute["thumb"]);
                    $iv["amount"] = floatval($iv["price"]) * floatval($iv["quantity"]);
                    $products[$ik] = $iv;
                }
                $v["products"] = $products;
            }else{
                $map = array("orderid"=>$v["id"]);
                $attach = $orderattachmodel->where($map)->find();
                if($attach){
                    $attach["thumb"] = $this->DoUrlHandle($attach["thumb"]);
                    $attach["amount"] = getNumberFormat($v['amount']);
                    $attach["price"] = getNumberFormat($v['total_amount']);
                    $attach['productid'] = $attach['objectid'];
                    $attach['quantity'] = '1';

                    $v["products"][] = $attach;
                }
            }

            $v["total_amount"] = getNumberFormat($v["total_amount"]);
            $v["amount"] = getNumberFormat($v["amount"]);

            //订单综合状态
            $v["com_status"] = $this->GetProductOrderStatus($v);

            $list[$k] = $v;
        }

        return $list;
    }

    //服务已完成订单列表
    public function serviceorder(){
        $user = $this->AuthUserInfo;

        $ordermodel = D("service_order");

        //剔除已删除的订单
        $map = array("o.userid"=>$user["id"], "o.status"=>array("neq", -1));
        $map["o.status"] = 4;
        $map["o.pay_status"] = 3;
        $map["o.execute_status"] = 4;

        $list = $ordermodel->alias("o")->join("left join sj_user_care as c on o.careid=c.id")
            ->field("o.*,c.level as care_level")->where($map)->where('invoice_id=0')->select();

        $recordmodel = D("service_order_record");
        $caremodel = D("user_care");
        foreach($list as $k=>$v){

            //订单综合状态
            $v["com_status"] = $this->GetServiceOrderStatus($v);

            $v["thumb"] = $this->DoUrlHandle($v["thumb"]);
            $v["service_avatar"] = $this->DoUrlHandle($v["service_avatar"]);
            $v["doctor_image"] = $this->DoUrlListHandle($v["doctor_image"]);
            $v["total_amount"] = getNumberFormat($v["total_amount"]);
            $v["amount"] = getNumberFormat($v["amount"]);
            $v["again_price"] = getNumberFormat($v["again_price"]);

            $begintime = strtotime($v["begintime"]);
            $v["begintime"] = date("Y/m/d H:i", $begintime);
            $endtime = strtotime($v["endtime"]);
            if(date("Y/m/d", $begintime) == date("Y/m/d", $endtime)){
                $v["endtime"] = date("H:i", $endtime);
            } else{
                $v["endtime"] = date("Y/m/d H:i", $endtime);
            }

            //照护人详情
            $map = array("userid"=>$v["userid"], "id"=>$v["careid"]);
            $usercare = $caremodel->where($map)->find();
            if ($usercare) {
                $usercare["age"] = getAgeMonth($usercare["birth"]);
                if(empty($usercare["height"])){
                    $usercare["height"] = "";
                }
                if(empty($usercare["weight"])){
                    $usercare["weight"] = "";
                }
                $usercare["avatar"] = $this->DoUrlHandle($usercare["avatar"]);
            }
            $v["user_care"] = $usercare;

            if($v["assess"] == 1 && $v["service_role"] == 3){
                //照护人评估记录
                $recordmodel = D("service_order_assess_record");
                $map = array("orderid"=>$v["id"], "careid"=>$v["careid"]);
                $record = $recordmodel->where($map)->find();
                if($record){
                    $v["assess_care_level"] = $record["assess_care_level"]; //专业评估等级
                    $v["care_level"] = $record["care_level"]; //实际照护等级
                }else{
                    $v["assess_care_level"] = 0; //专业评估等级
                    $v["care_level"] = 0; //实际照护等级
                }
            }

            //是否评论
            $v["is_comment"] = 0;
            if($v["commentid"] > 0){
                $v["is_comment"] = 1;
            }

            if($v["type"] == 1){
                //服务交互记录 - 配餐餐次
                $map = array("orderid"=>$v["id"], "userid"=>$v["service_userid"], "execute_status"=>3);
                $record = $recordmodel->where($map)->select();

                $v["record"] = $record;
            }

            $list[$k] = $v;
        }

        return $list;
    }
}