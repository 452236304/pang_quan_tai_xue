<?php
namespace CApi\Controller;
use Think\Controller;
class OrderOrgController extends BaseLoggedController {
	
	//机构订单列表
	public function orgorder(){
		$user = $this->AuthUserInfo;

		$ordermodel = D("org_order");

		//orderstatus：0=全部,1=待付款,2=已取消,3=已完成,4=已付款,5=售后,6=待评价,7=已评价
		$orderstatus = I("get.orderstatus", 0);

		//剔除已删除的订单
		$map = array("userid"=>$user["id"], "status"=>array("neq", -1));
		switch ($orderstatus) {
			case 1:
				$map["status"] = 1;
				$map["pay_status"] = 0;
				break;
			case 2:
				$map["status"] = 2;
				$map["pay_status"] = 0;
				break;
			case 3:
				$map["status"] = 4;
				$map["pay_status"] = 3;
				break;
			case 4:
				$map["status"] = array("in", [1,4]);
				$map["pay_status"] = 3;
				break;
			case 5:
				$map["status"] = array("in", [5,6]);
				$map["pay_status"] = 3;
				break;
			case 6:
				$map["commentid"] = 0;
                $map["status"] = 4;
                $map["pay_status"] = 3;
				$map["type"] = 3;
				break;
			case 7:
				$map["commentid"] = array("neq", 0);
                $map["status"] = 4;
                $map["pay_status"] = 3;
                $map["type"] = 3;
				break;
		}

		$page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;
        
        $order = "createdate desc";
        $count = $ordermodel->where($map)->count();
        $totalpage = ceil($count/$row);
        $list = $ordermodel->where($map)->order($order)->limit($begin, $row)->select();

		$this->SetPaginationHeader($totalpage, $count, $page, $row);

		foreach($list as $k=>$v){
			
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
			$v["total_amount"] = getNumberFormat($v["total_amount"]);
			$v["amount"] = getNumberFormat($v["amount"]);
			
			//是否评论
			$v["is_comment"] = 0;
			if($v["commentid"] > 0){
				$v["is_comment"] = 1;
			}

			//订单综合状态
			$v["com_status"] = $this->GetOrgOrderStatus($v);

			$list[$k] = $v;
		}

		return $list;
	}

    //机构订单详情
    public function orgorderdetail(){
        $user = $this->AuthUserInfo;

        $ordermodel = D("org_order");

        $orderid = I("get.orderid", 0);
        if(empty($orderid)){
            E("请选择要查看的订单");
        }
        $map = array("userid"=>$user["id"], "id"=>$orderid);
        $order = $ordermodel->where($map)->find();
        if(empty($order)){
            E("订单不存在");
        }

        $order["thumb"] = $this->DoUrlHandle($order["thumb"]);
		$order["total_amount"] = getNumberFormat($order["total_amount"]);
		$order["amount"] = getNumberFormat($order["amount"]);

		//是否评论
		$order["is_comment"] = 0;
		if($order["commentid"] > 0){
			$order["is_comment"] = 1;
		}

        //订单综合状态
		$order["com_status"] = $this->GetOrgOrderStatus($order);

		//订单售后信息
		if(in_array($order["status"], [5,6])){
			$refundmodel = D("org_order_refund");
			$map = array("userid"=>$user["id"], "orderid"=>$orderid);
			$order["refund_record"] = $refundmodel->where($map)->find();
			if($order["refund_record"]){
				$order["refund_record"]["images"] = $this->DoUrlListHandle($order["refund_record"]["images"]);
			}
		}
		//活动
		$type=$order['type'];
		if(in_array($type, [1,2])){
			$model = D("org_activity");
			
			$map = array("status"=>1, "type"=>$order['type']);
			$detail = $model->where($map)->find();
			
			$detail['price'] = intval($detail['price']);
			$detail["thumb"] = $this->DoUrlHandle($detail["thumb"]);
			$detail["content"] = $this->UEditorUrlReplace($detail["content"]);
			
			$orgs = $this->orgactivityrelation($detail["id"]);
			
			$order['activity'] = array(
				"detail"=>$detail, "list"=>$orgs
			);
		}
		$info=D('user_care')->field('name,mobile')->where('id='.$order['careid'])->find();
		$order['care_name']=$info['name'];
		$order['care_mobile']=$info['mobile'];
        return $order;
    }
	//根据活动id获取关联的机构列表
	private function orgactivityrelation($activityid){
		$activityrelationmodel = D("org_activity_relation");

		$map = array("ar.status"=>1, "ar.activityid"=>$activityid);
		$list = $activityrelationmodel->alias("ar")->join("left join sj_org as o on ar.orgid=o.id")
			->field("o.*,ar.dis_price")->where($map)->select();

		return $list;
	}
	//机构订单评价列表
	public function orgcomment(){
		$user = $this->AuthUserInfo;

		$model = D("org_order");

		//类型：0=待评价，1=已评价
		$type = I("get.type", 0);

        //短住类型才有评论
		$map = array("userid"=>$user["id"], "type"=>3, "status"=>4, "pay_status"=>3);
		if($type == 0){
			$map["commentid"] = 0;
		} else{
			$map["commentid"] = array("gt", 0);
		}
		$page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;
        
        $order = "createdate desc";
        $count = $model->where($map)->count();
        $totalpage = ceil($count/$row);
		$list = $model->where($map)->order($order)->limit($begin, $row)->select();

		$this->SetPaginationHeader($totalpage, $count, $page, $row);

		foreach($list as $k=>$v){
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);

			$list[$k] = $v;
		}
		
		return $list;
	}

	//机构订单评价详情
	public function orgcommentdetail(){
		$user = $this->AuthUserInfo;

		$model = D("org_comment");

		$commentid = I("post.commentid", 0);
		if(empty($commentid)){
			E("请选择要查看的机构订单评价");
		}

		$map = array("userid"=>$user["id"], "id"=>$commentid);
		$detail = $model->where($map)->find();
		if(empty($detail)){
			E("当前订单还未评价，无法查看");
		}

		$detail["thumb"] = $this->DoUrlHandle($detail["thumb"]);
		$detail["images"] = $this->DoUrlListHandle($detail["images"]);

		return $detail;
	}

}