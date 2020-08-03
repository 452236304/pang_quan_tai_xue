<?php
namespace SApi\Controller;
use Think\Controller;
class OrderPlatformController extends BaseLoggedController {
	
	//抢单中
	public function orderbattle(){
		$user = $this->AuthUserInfo;

		//验证服务人员的爽约状况
		$planetime = $user["profile"]["plane_time"];
		if(checkDateTime($planetime, "Y-m-d H:i:s")){
			$time = date("Y-m-d H:i:s", strtotime("-3 month", time()));
			if($planetime > $time){
				E("你存在爽约记录，3个月内不能接单");
			}
		}

		//服务人员角色
        $roles = $user["role"];

		$ordermodel = D("service_order");

        //当前时间
        $time = date("Y-m-d H:i");

		$page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;

		if(in_array(2, $roles)){ //送餐员
			$map = array(
				"so.admin_status"=>1, "so.type"=>1, "so.service_userid"=>0, "so.status"=>1, "so.execute_status"=>0,
				"so.pay_status"=>3, "so.province"=>$user["profile"]["province"],
                "so.city"=>$user["profile"]["city"], "so.region"=>$user["profile"]["region"],
                "so.begintime"=>array("gt", $time)
            );
        
            $order = "so.createdate desc";
            $count = $ordermodel->alias("so")->join("left join sj_user as u on so.userid=u.id")
                ->where($map)->count();
            $totalpage = ceil($count/$row);
            $list = $ordermodel->alias("so")->join("left join sj_user as u on so.userid=u.id")
                ->join("left join sj_user_care as c on so.careid=c.id")
                ->field("so.*,u.avatar as user_avatar,c.level as care_level")
                ->where($map)->order($order)->limit($begin, $row)->select();
            
		} else if(in_array(3, $roles)){ //家护师

			//服务人员关联服务项目
			$relationmodel = D("user_project_relation");
			$map = array("type"=>2, "userid"=>$user["id"]);
			$project = $relationmodel->where($map)->select();
			foreach($project as $k=>$v){
				$projectids[] = $v["projectid"];
			}
			if(count($projectids) <= 0){
				return [];
            }
        
            //服务人员星级
            $service_level = $user["profile"]["service_level"]; 
            //服务人员专业等级
            $major_level = $user["profile"]["major_level"];
	
			$map = array(
				"so.admin_status"=>1, "so.type"=>2, "so.service_userid"=>0, "so.status"=>1, "so.execute_status"=>0,
				"so.pay_status"=>3, "so.projectid"=>array("in", $projectids), "so.province"=>$user["profile"]["province"],
                "so.city"=>$user["profile"]["city"], "so.region"=>$user["profile"]["region"],
                "so.service_level"=>array("elt", $service_level), "p.major_level"=>array("elt", $major_level),
                "so.begintime"=>array("gt", $time), "so.service_role"=>3 //"so.service_role"=>array("in", $roles)
			);
        
            $order = "so.createdate desc";
            $count = $ordermodel->alias("so")->join("left join sj_user as u on so.userid=u.id")
                ->join("left join sj_service_project as p on so.projectid=p.id")
                ->where($map)->count();
            $totalpage = ceil($count/$row);
            $list = $ordermodel->alias("so")->join("left join sj_user as u on so.userid=u.id")
                ->join("left join sj_user_care as c on so.careid=c.id")
                ->join("left join sj_service_project as p on so.projectid=p.id")
                ->field("so.*,u.avatar as user_avatar,c.level as care_level,p.major_level as service_major_level")
                ->where($map)->order($order)->limit($begin, $row)->select();

		} else if(in_array(4, $roles)){ //康复师

			//服务人员关联服务项目
			$relationmodel = D("user_project_relation");
			$map = array("type"=>2, "userid"=>$user["id"]);
			$project = $relationmodel->where($map)->select();
			foreach($project as $k=>$v){
				$projectids[] = $v["projectid"];
			}
			if(count($projectids) <= 0){
				return [];
			}
        
            //服务人员星级
            $service_level = $user["profile"]["service_level"]; 
            //服务人员专业等级
            $major_level = $user["profile"]["major_level"];
	
			$map = array(
				"so.admin_status"=>1, "so.type"=>2, "so.service_userid"=>0, "so.status"=>1, "so.execute_status"=>0,
				"so.pay_status"=>3, "so.projectid"=>array("in", $projectids), "so.province"=>$user["profile"]["province"],
                "so.city"=>$user["profile"]["city"], "so.region"=>$user["profile"]["region"],
                "so.service_level"=>array("elt", $service_level), "p.major_level"=>array("elt", $major_level),
                "so.begintime"=>array("gt", $time), "so.service_role"=>4 //"so.service_role"=>array("in", $roles)
			);
        
            $order = "so.createdate desc";
            $count = $ordermodel->alias("so")->join("left join sj_user as u on so.userid=u.id")
                ->join("left join sj_service_project as p on so.projectid=p.id")
                ->where($map)->count();
            $totalpage = ceil($count/$row);
            $list = $ordermodel->alias("so")->join("left join sj_user as u on so.userid=u.id")
                ->join("left join sj_user_care as c on so.careid=c.id")
                ->join("left join sj_service_project as p on so.projectid=p.id")
                ->field("so.*,u.avatar as user_avatar,c.level as care_level,p.major_level as service_major_level")
                ->where($map)->order($order)->limit($begin, $row)->select();

		} else if(in_array(5, $roles)){ //医生

			//服务人员关联服务项目
			$relationmodel = D("user_project_relation");
			$map = array("type"=>2, "userid"=>$user["id"]);
			$project = $relationmodel->where($map)->select();
			foreach($project as $k=>$v){
				$projectids[] = $v["projectid"];
			}
			if(count($projectids) <= 0){
				return [];
			}
        
            //服务人员星级
            $service_level = $user["profile"]["service_level"]; 
            //服务人员专业等级
            $major_level = $user["profile"]["major_level"];
	
			$map = array(
				"so.admin_status"=>1, "so.type"=>2, "so.service_userid"=>0, "so.status"=>1, "so.execute_status"=>0,
				"so.pay_status"=>3, "so.projectid"=>array("in", $projectids), "so.province"=>$user["profile"]["province"],
                "so.city"=>$user["profile"]["city"], "so.region"=>$user["profile"]["region"],
                "so.service_level"=>array("elt", $service_level), "p.major_level"=>array("elt", $major_level),
                "so.begintime"=>array("gt", $time), "so.service_role"=>5 //"so.service_role"=>array("in", $roles)
			);
        
            $order = "so.createdate desc";
            $count = $ordermodel->alias("so")->join("left join sj_user as u on so.userid=u.id")
                ->join("left join sj_service_project as p on so.projectid=p.id")
                ->where($map)->count();
            $totalpage = ceil($count/$row);
            $list = $ordermodel->alias("so")->join("left join sj_user as u on so.userid=u.id")
                ->join("left join sj_user_care as c on so.careid=c.id")
                ->join("left join sj_service_project as p on so.projectid=p.id")
                ->field("so.*,u.avatar as user_avatar,c.level as care_level,p.major_level as service_major_level")
                ->where($map)->order($order)->limit($begin, $row)->select();

		} else if(in_array(6, $roles)){ //护士

			//服务人员关联服务项目
			$relationmodel = D("user_project_relation");
			$map = array("type"=>2, "userid"=>$user["id"]);
			$project = $relationmodel->where($map)->select();
			foreach($project as $k=>$v){
				$projectids[] = $v["projectid"];
			}
			if(count($projectids) <= 0){
				return [];
			}
        
            //服务人员星级
            $service_level = $user["profile"]["service_level"]; 
            //服务人员专业等级
            $major_level = $user["profile"]["major_level"];
	
			$map = array(
				"so.admin_status"=>1, "so.type"=>2, "so.service_userid"=>0, "so.status"=>1, "so.execute_status"=>0,
				"so.pay_status"=>3, "so.projectid"=>array("in", $projectids), "so.province"=>$user["profile"]["province"],
                "so.city"=>$user["profile"]["city"], "so.region"=>$user["profile"]["region"],
                "so.service_level"=>array("elt", $service_level), "p.major_level"=>array("elt", $major_level),
                "so.begintime"=>array("gt", $time), "so.service_role"=>6 //"so.service_role"=>array("in", $roles)
			);
        
            $order = "so.createdate desc";
            $count = $ordermodel->alias("so")->join("left join sj_user as u on so.userid=u.id")
                ->join("left join sj_service_project as p on so.projectid=p.id")
                ->where($map)->count();
            $totalpage = ceil($count/$row);
            $list = $ordermodel->alias("so")->join("left join sj_user as u on so.userid=u.id")
                ->join("left join sj_user_care as c on so.careid=c.id")
                ->join("left join sj_service_project as p on so.projectid=p.id")
                ->field("so.*,u.avatar as user_avatar,c.level as care_level,p.major_level as service_major_level")
                ->where($map)->order($order)->limit($begin, $row)->select();

		} else{
            return [];
        }

		$this->SetPaginationHeader($totalpage, $count, $page, $row);

		foreach($list as $k=>$v){
			
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
            $v["service_avatar"] = $this->DoUrlHandle($v["service_avatar"]);
            if(empty($v["user_avatar"])){
                $v["user_avatar"] = "/upload/default/default_avatar.png";
            }
			$v["user_avatar"] = $this->DoUrlHandle($v["user_avatar"]);
            $v["coupon_money"] = getNumberFormat($v["coupon_money"]);
			$v["total_amount"] = getNumberFormat($v["total_amount"]);
			$v["amount"] = getNumberFormat($v["amount"]);
            $v["again_price"] = getNumberFormat($v["again_price"]);

			//平台补贴（优惠券金额）
            $v["platform_money"] = getNumberFormat($v["platform_money"]);

			$begintime = strtotime($v["begintime"]);
            $v["begintime"] = date("Y/m/d H:i", $begintime);
            $endtime = strtotime($v["endtime"]);
            if(date("Y/m/d", $begintime) == date("Y/m/d", $endtime)){
                $v["endtime"] = date("H:i", $endtime);
            } else{
                $v["endtime"] = date("Y/m/d H:i", $endtime);
            }

            //专业等级
            if(empty($v["service_major_level"])){
                $v["service_major_level"] = 0;
            }

            //订单综合状态 给空
            $v["com_status"] =  array("com_status"=>'', "com_status_str"=>'');

			$list[$k] = $v;
		}

		return $list;
	}

    //历史抢单
    public function orderhistory(){
        $user = $this->AuthUserInfo;

        $ordermodel = D("service_order");

        //服务人员角色
        $roles = $user["role"];

        if(in_array(2, $roles)){ //送餐员
            $map = array(
                "so.admin_status"=>1, "so.type"=>1, "so.service_userid"=>array("gt", 0),
                "so.pay_status"=>3, "so.province"=>$user["profile"]["province"],
                "so.city"=>$user["profile"]["city"], "so.region"=>$user["profile"]["region"]
            );
        } else{ //非送餐员

            //服务人员关联服务项目
            $relationmodel = D("user_project_relation");
            $map = array("type"=>2, "userid"=>$user["id"]);
            $project = $relationmodel->where($map)->select();
            foreach($project as $k=>$v){
                $projectids[] = $v["projectid"];
            }
            if(count($projectids) <= 0){
                return [];
            }

            $map = array(
                "so.admin_status"=>1, "so.type"=>2, "so.pay_status"=>3, "so.projectid"=>array("in", $projectids),
                "so.service_userid"=>array("gt", 0), "so.province"=>$user["profile"]["province"],
                "so.city"=>$user["profile"]["city"], "so.region"=>$user["profile"]["region"]
            );
        }

        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;

        $order = "so.createdate desc";
        $count = $ordermodel->alias("so")->join("left join sj_service_category as sc on so.categoryid=sc.id")
            ->join("left join sj_user as u on so.userid=u.id")->where($map)->count();
        $totalpage = ceil($count/$row);
        $list = $ordermodel->alias("so")->join("left join sj_service_category as sc on so.categoryid=sc.id")
            ->join("left join sj_user as u on so.userid=u.id")->join("left join sj_user_care as c on so.careid=c.id")
            ->field("so.*,sc.role as service_role,u.avatar as user_avatar,c.level as care_level")->where($map)->order($order)->limit($begin, $row)->select();

        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        $projectmodel = D("service_project");//服务项目
        foreach($list as $k=>$v){

            $v["thumb"] = $this->DoUrlHandle($v["thumb"]);
            $v["service_avatar"] = $this->DoUrlHandle($v["service_avatar"]);
            if(empty($v["user_avatar"])){
                $v["user_avatar"] = "/upload/default/default_avatar.png";
            }
            $v["user_avatar"] = $this->DoUrlHandle($v["user_avatar"]);
            $v["coupon_money"] = getNumberFormat($v["coupon_money"]);
			$v["total_amount"] = getNumberFormat($v["total_amount"]);
			$v["amount"] = getNumberFormat($v["amount"]);
            $v["again_price"] = getNumberFormat($v["again_price"]);

			//平台补贴（优惠券金额）
            $v["platform_money"] = getNumberFormat($v["platform_money"]);

			$begintime = strtotime($v["begintime"]);
            $v["begintime"] = date("Y/m/d H:i", $begintime);
            $endtime = strtotime($v["endtime"]);
            if(date("Y/m/d", $begintime) == date("Y/m/d", $endtime)){
                $v["endtime"] = date("H:i", $endtime);
            } else{
                $v["endtime"] = date("Y/m/d H:i", $endtime);
            }

            //专业等级
            $v["service_major_level"] = 0;
            if($v["type"] == 2){ //服务订单
                $project = $projectmodel->find($v["projectid"]);
                $v["service_major_level"] = $project["major_level"];
            }
            //订单综合状态 给空
            $v["com_status"] =  array("com_status"=>'', "com_status_str"=>'');

            $list[$k] = $v;
        }

        return $list;
    }

	//成功抢单
	public function ordersucceed(){
		$user = $this->AuthUserInfo;

		$ordermodel = D("service_order");

		$map = array("so.service_userid"=>$user["id"], "so.admin_status"=>1, "so.pay_status"=>3);

		$page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;

        $order = "so.createdate desc";
        $count = $ordermodel->alias("so")->join("left join sj_service_category as sc on so.categoryid=sc.id")
            ->join("left join sj_user as u on so.userid=u.id")->where($map)->count();
        $totalpage = ceil($count/$row);
        $list = $ordermodel->alias("so")->join("left join sj_service_category as sc on so.categoryid=sc.id")
            ->join("left join sj_user as u on so.userid=u.id")->join("left join sj_user_care as c on so.careid=c.id")
            ->field("so.*,sc.role as service_role,u.avatar as user_avatar,c.level as care_level")->where($map)->order($order)->limit($begin, $row)->select();

        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        $projectmodel = D("service_project");//服务项目
        foreach($list as $k=>$v){

            $v["thumb"] = $this->DoUrlHandle($v["thumb"]);
            $v["service_avatar"] = $this->DoUrlHandle($v["service_avatar"]);
            if(empty($v["user_avatar"])){
                $v["user_avatar"] = "/upload/default/default_avatar.png";
            }
            $v["user_avatar"] = $this->DoUrlHandle($v["user_avatar"]);
            $v["coupon_money"] = getNumberFormat($v["coupon_money"]);
			$v["total_amount"] = getNumberFormat($v["total_amount"]);
			$v["amount"] = getNumberFormat($v["amount"]);
            $v["again_price"] = getNumberFormat($v["again_price"]);

			//平台补贴（优惠券金额）
            $v["platform_money"] = getNumberFormat($v["platform_money"]);

			$begintime = strtotime($v["begintime"]);
            $v["begintime"] = date("Y/m/d H:i", $begintime);
            $endtime = strtotime($v["endtime"]);
            if(date("Y/m/d", $begintime) == date("Y/m/d", $endtime)){
                $v["endtime"] = date("H:i", $endtime);
            } else{
                $v["endtime"] = date("Y/m/d H:i", $endtime);
            }

            //专业等级
            $v["service_major_level"] = 0;
            if($v["type"] == 2){ //服务订单
                $project = $projectmodel->find($v["projectid"]);
                $v["service_major_level"] = $project["major_level"];
            }
            //订单综合状态 给空
            $v["com_status"] =  array("com_status"=>'', "com_status_str"=>'');

            $list[$k] = $v;
        }

		return $list;
	}

	//退单
	public function orderreturned(){
		$user = $this->AuthUserInfo;

		$ordermodel = D("service_order_record");

		$map = array("sor.userid"=>$user["id"], "sor.execute_status"=>5);

		$page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;

        $order = "so.createdate desc";
        $count = $ordermodel->alias("sor")->join("left join sj_service_order as so on sor.orderid=so.id")->where($map)->count();
        $totalpage = ceil($count/$row);
        $list = $ordermodel->alias("sor")->join("left join sj_service_order as so on sor.orderid=so.id")
            ->join("left join sj_service_category as sc on so.categoryid=sc.id")
            ->join("left join sj_user as u on so.userid=u.id")->join("left join sj_user_care as c on so.careid=c.id")
            ->field("so.*,sc.role as service_role,u.avatar as user_avatar,so.remark as reason,c.level as care_level")
            ->where($map)->order($order)->limit($begin, $row)->select();

		$this->SetPaginationHeader($totalpage, $count, $page, $row);

        $projectmodel = D("service_project");//服务项目
        foreach($list as $k=>$v){

            $v["thumb"] = $this->DoUrlHandle($v["thumb"]);
            $v["service_avatar"] = $this->DoUrlHandle($v["service_avatar"]);
            if(empty($v["user_avatar"])){
                $v["user_avatar"] = "/upload/default/default_avatar.png";
            }
            $v["user_avatar"] = $this->DoUrlHandle($v["user_avatar"]);
            $v["coupon_money"] = getNumberFormat($v["coupon_money"]);
			$v["total_amount"] = getNumberFormat($v["total_amount"]);
			$v["amount"] = getNumberFormat($v["amount"]);
            $v["again_price"] = getNumberFormat($v["again_price"]);

			//平台补贴（优惠券金额）
            $v["platform_money"] = getNumberFormat($v["platform_money"]);

			$begintime = strtotime($v["begintime"]);
            $v["begintime"] = date("Y/m/d H:i", $begintime);
            $endtime = strtotime($v["endtime"]);
            if(date("Y/m/d", $begintime) == date("Y/m/d", $endtime)){
                $v["endtime"] = date("H:i", $endtime);
            } else{
                $v["endtime"] = date("Y/m/d H:i", $endtime);
            }

            //专业等级
            $v["service_major_level"] = 0;
            if($v["type"] == 2){ //服务订单
                $project = $projectmodel->find($v["projectid"]);
                $v["service_major_level"] = $project["major_level"];
            }
            //订单综合状态 给空
            $v["com_status"] =  array("com_status"=>'', "com_status_str"=>'');

            $list[$k] = $v;
        }

        return $list;
	}
}