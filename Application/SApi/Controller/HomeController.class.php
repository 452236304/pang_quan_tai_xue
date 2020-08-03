<?php
namespace SApi\Controller;
use Think\Controller;
class HomeController extends BaseController {

	//首页
	public function index(){
		
		$bannermodel = D("banner");
		//顶部广告图
		$map = array("status"=>1, "type"=>6);
		$banner = $bannermodel->where($map)->order("ordernum asc")->select();
		foreach ($banner as $k=>$v) {
			$v["image"] = $this->DoUrlHandle($v["image"]);
            
            if($v["param"]){
                $v["param"] = json_decode($v["param"], true);
            } else{
                $v["param"] = array("param_type"=>"-1", "param_id"=>"");
            }

			$banner[$k] = $v;
		}

		//底部广告图
		$map = array("status"=>1, "type"=>7);
		$footerbanner = $bannermodel->where($map)->order("ordernum asc")->select();
		foreach ($footerbanner as $k=>$v) {
			$v["image"] = $this->DoUrlHandle($v["image"]);
            
            if($v["param"]){
                $v["param"] = json_decode($v["param"], true);
            } else{
                $v["param"] = array("param_type"=>"-1", "param_id"=>"");
            }

			$footerbanner[$k] = $v;
		}
        $list = array();

        //订单动态
        $ordermodel = D("service_order");

        //当前时间
        $time = date("Y-m-d H:i:s");
        $map = array(
            "so.status"=>1, "so.pay_status"=>3, 'so.service_userid'=>0, 'so.admin_status'=>1,
            "so.service_role"=>array("in", [2,3,4,5,6]), "so.begintime"=>array("gt", $time)
        );
        $list = $ordermodel->alias("so")->join("left join sj_service_category as sc on so.categoryid=sc.id")
            ->join("left join sj_user as u on so.userid=u.id")->join("left join sj_user_care as c on so.careid=c.id")
            ->field("so.*,sc.role as service_role,u.avatar as user_avatar,c.level as care_level")
            ->where($map)->order("createdate desc")->limit(0, 4)->select();

        $checkproject = array();
        if($this->UserAuthCheckLogin()) {
            //查找登录的服务人员所匹配的项目
            $user = $this->AuthUserInfo;

            if(!in_array(2, $user["role"])){ //非送餐员
                $relationmodel = D("user_project_relation");
                $map = array("type"=>2, "userid"=>$user["id"]);
                $checkproject = $relationmodel->where($map)->getField('projectid',true);
            }
        }

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
            
            //订单综合状态
            $v["com_status"] = $this->GetServiceOrderStatus($v);
            
            $begintime = strtotime($v["begintime"]);
            $endtime = strtotime($v["endtime"]);
            if(date("Y/m/d", $begintime) == date("Y/m/d", $endtime)){
				$v["begintime"] = date("Y/m/d H:i", $begintime);
                $v["endtime"] = date("H:i", $endtime);
            } else{
				$v["begintime"] = date("Y/m/d", $begintime);
                $v["endtime"] = date("Y/m/d", $endtime);
            }

			//平台补贴（优惠券金额）
			$v["platform_money"] = getNumberFormat($v["platform_money"]);

            //专业等级
			$v["service_major_level"] = 0;
			if($v["type"] == 2){ //服务订单
				$project = $projectmodel->find($v["projectid"]);
				$v["service_major_level"] = $project["major_level"];
            }
            
            //判断是否能抢单
            $v['isbattle'] = '0';
            $time = time();
            if($time < $begintime){ //可预约服务时间内
                if($user && $user["profile"]["province"] == $v["province"] && $user["profile"]["city"] == $v["city"]
                    && $user["profile"]["region"] == $v["region"]){
                    if($v["type"] == 1 && in_array(2, $user["role"])){ //送餐员
                        $v['isbattle'] = '1';
                    } else if($v["type"] == 2 && in_array($v["service_role"], $user["role"])){ //服务人员
						//服务星级
						$service_level = $user["profile"]["service_level"];
						//专业等级
						$major_level = $user["profile"]["major_level"];
						if($v["service_level"] <= $service_level && $v["service_major_level"] <= $major_level){
							if(in_array($v['projectid'],$checkproject)){
								$v['isbattle'] = '1';
							}
						}
                    }
                }
            }

            $list[$k] = $v;
        }

        //客服电话
        $aboutmodel = D("about");
        $map = array("status"=>1, "id"=>4);
        $about = $aboutmodel->where($map)->find();
        if($about){
            $service = array("title"=>$about["title"], "tel"=>$about["content"]);
        }

		$data = array(
            "banner"=>$banner, "footerbanner"=>$footerbanner, "order"=>$list, "service"=>$service
		);

		return $data;
	}

	//订单动态
	public function lists(){
        $ordermodel = D("service_order");

        //当前时间
        $time = date("Y-m-d H:i:s");
        $map = array(
            "so.status"=>1, "so.pay_status"=>3, 'so.service_userid'=>0, 'so.admin_status'=>1,
            "so.service_role"=>array("in", [2,3,4,5,6]), "so.begintime"=>array("gt", $time)
        );

        //服务项目
        $categoryid = I("get.categoryid", 0);
        if($categoryid == -1){ //送餐服务
            $map["so.type"] = 1;
        } else if($categoryid){ //服务栏目id
            $map["so.categoryid"] = $categoryid;
        }

        //服务时间 0=全部,1=当天,2=最近三天,3=最近一周,4=最近1个月
        $sdate = I("get.sdate", 0);
        switch($sdate){
            case 1:
                $time = date("Y-m-d", time());
                $map["so.begintime"] = array("egt", $time);
                break;
            case 2:
                $time = date("Y-m-d", strtotime("-3 day", time()));
                $map["so.begintime"] = array("egt", $time);
                break;
            case 3:
                $time = date("Y-m-d", strtotime("-7 day", time()));
                $map["so.begintime"] = array("egt", $time);
                break;
            case 4:
                $time = date("Y-m-d", strtotime("-1 month", time()));
                $map["so.begintime"] = array("egt", $time);
                break;
        }

        //发布时间 0=全部,1=当天,2=最近三天,3=最近一周,4=最近1个月
        $cdate = I("get.cdate", 0);
        switch($cdate){
            case 1:
                $time = date("Y-m-d", time());
                $map["so.createdate"] = array("egt", $time);
                break;
            case 2:
                $time = date("Y-m-d", strtotime("-3 day", time()));
                $map["so.createdate"] = array("egt", $time);
                break;
            case 3:
                $time = date("Y-m-d", strtotime("-7 day", time()));
                $map["so.createdate"] = array("egt", $time);
                break;
            case 4:
                $time = date("Y-m-d", strtotime("-1 month", time()));
                $map["so.createdate"] = array("egt", $time);
                break;
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
            ->field("so.*,sc.role as service_role,u.avatar as user_avatar,c.level as care_level")
            ->where($map)->order($order)->limit($begin, $row)->select();

        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        $checkproject = array();
        if($this->UserAuthCheckLogin()) {
            //查找登录的服务人员所匹配的项目
            $user = $this->AuthUserInfo;

            //if(!in_array(2, $user["role"])){ //非送餐员
                $relationmodel = D("user_project_relation");
                $map = array("type"=>2, "userid"=>$user["id"]);
                $checkproject = $relationmodel->where($map)->getField('projectid',true);
            //}
        }

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

            //订单综合状态
            $v["com_status"] = $this->GetServiceOrderStatus($v);
            
            $begintime = strtotime($v["begintime"]);
            $endtime = strtotime($v["endtime"]);
            if(date("Y/m/d", $begintime) == date("Y/m/d", $endtime)){
				$v["begintime"] = date("Y/m/d H:i", $begintime);
                $v["endtime"] = date("H:i", $endtime);
            } else{
				$v["begintime"] = date("Y/m/d", $begintime);
                $v["endtime"] = date("Y/m/d", $endtime);
            }

			//平台补贴（优惠券金额）
			$v["platform_money"] = getNumberFormat($v["platform_money"]);

			//专业等级
			$v["service_major_level"] = 0;
			if($v["type"] == 2){ //服务订单
				$project = $projectmodel->find($v["projectid"]);
				$v["service_major_level"] = $project["major_level"];
			}
            
            //判断是否能抢单
            $v['isbattle'] = '0';
            $time = time();
            if($time < $begintime){ //可预约服务时间内
                if($user && $user["profile"]["province"] == $v["province"] && $user["profile"]["city"] == $v["city"]
                    && $user["profile"]["region"] == $v["region"]){
                    if($v["type"] == 1 && in_array(2, $user["role"])){ //送餐员
                        $v['isbattle'] = '1';
                    } else if($v["type"] == 2 && in_array($v["service_role"], $user["role"])){ //服务人员
                        //服务星级
                        $service_level = $user["profile"]["service_level"];
                        //专业等级
                        $major_level = $user["profile"]["major_level"];
                        if($v["service_level"] <= $service_level && $v["service_major_level"] <= $major_level){
                            if(in_array($v['projectid'],$checkproject)){
                                $v['isbattle'] = '1';
                            }
                        }
                    }
                }
            }

            $list[$k] = $v;
        }

		return $list;
	}

    //订单详情
    public function detail(){
        $orderid = I("get.orderid", 0);
        if(empty($orderid)){
            E("请选择要查看的订单");
        }

        //订单详情
        $ordermodel = D("service_order");
        $map = array("id"=>$orderid);
        $order = $ordermodel->where($map)->find();
        if(empty($order)){
            E("订单不存在");
        }

        $order["thumb"] = $this->DoUrlHandle($order["thumb"]);
        $order["service_avatar"] = $this->DoUrlHandle($order["service_avatar"]);
        $order["coupon_money"] = getNumberFormat($order["coupon_money"]);
        $order["total_amount"] = getNumberFormat($order["total_amount"]);
        $order["amount"] = getNumberFormat($order["amount"]);
        $order["again_price"] = getNumberFormat($order["again_price"]);
		//平台补贴（优惠券金额）
        $order["platform_money"] = getNumberFormat($order["platform_money"]);

        //订单综合状态
        $order["com_status"] = $this->GetServiceOrderStatus($order);
        
        $begintime = strtotime($order["begintime"]);
		$order["begintime"] = date("Y/m/d H:i", $begintime);
		$endtime = strtotime($order["endtime"]);
		if(date("Y/m/d", $begintime) == date("Y/m/d", $endtime)){
			$order["endtime"] = date("H:i", $endtime);
		} else{
			$order["endtime"] = date("Y/m/d H:i", $endtime);
		}

		//是否评论
		$order["is_comment"] = 0;
		if($order["commentid"] > 0){
			$order["is_comment"] = 1;
		}
        //角色类型
        $scmodel = D("service_category");//服务项目栏目
        $sc= $scmodel->where('id='.$order['categoryid'])->find();
        $order['service_role'] = $sc['role'];

        if($order["type"] == 2){
            //专业等级
            $spmodel = D("service_project");//服务项目
            $sp = $spmodel->where('id='.$order['projectid'])->find();
            $order['service_major_level'] = $sp['major_level'];
        }
        
        $order['isbattle'] = '0';
        if($order["service_userid"] == 0 && $this->UserAuthCheckLogin()) {
            $user = $this->AuthUserInfo;

            $time = time();
            if($time < $begintime){ //可预约服务时间内
                if($user["profile"]["province"] == $order["province"] && $user["profile"]["city"] == $order["city"]
                    && $user["profile"]["region"] == $order["region"]){
                    if($order["type"] == 1 && in_array(2, $user["role"])){ //送餐员
                        $order['isbattle'] = '1';
                    } else if($order["type"] == 2 && in_array($order["service_role"], $user["role"])){ //服务人员
                        //服务星级
                        $service_level = $user["profile"]["service_level"];
                        //专业等级
                        $major_level = $user["profile"]["major_level"];
                        if($order["service_level"] <= $service_level && $order["service_major_level"] <= $major_level){
                            //服务人员关联的服务项目
                            $relationmodel = D("user_project_relation");
                            $map = array("type"=>2, "userid"=>$user["id"]);
                            $checkproject = $relationmodel->where($map)->getField('projectid',true);

                            if(in_array($order['projectid'], $checkproject)){
                                $order['isbattle'] = '1';
                            }
                        }
                    }
                }
            }
        }

        //照护人详情
        $caremodel = D("user_care");
        $map = array("userid"=>$order["userid"], "id"=>$order["careid"]);
        $usercare = $caremodel->where($map)->find();
        if ($usercare) {
            $usercare['age'] = getAgeMonth($usercare['birth']);
        	if($usercare['height']==0){
        		$usercare['height']='';
        	}
        	if($usercare['weight']==0){
        		$usercare['weight']='';
        	}
        }

        //服务人员详情
        if($order["service_userid"] > 0){
            $usermodel = D("user_profile");
			$map = array("u.status"=>200, "up.status"=>1, "u.id"=>$order["service_userid"]);
			$serviceuser = $usermodel->alias("up")->join("left join sj_user as u on u.id=up.userid")
				->field("u.id,u.avatar,up.realname,up.gender,up.birth,up.mobile,up.major_level,up.service_level,up.work_year,up.education,up.major,up.language,up.comment_percent")->where($map)->find();
			if ($serviceuser){
				$serviceuser['age'] = getAge($serviceuser['birth']);
				$serviceuser['avatar'] = $this->DoUrlHandle($serviceuser["avatar"]);
			}
        }

        $data = array(
            "order"=>$order, "usercare"=>$usercare, "serviceuser"=>$serviceuser
        );

        return $data;
    }

    //送餐服务订单详情
    public function mealdetail(){
        $orderid = I("get.orderid", 0);
        if(empty($orderid)){
            E("请选择要查看的订单");
        }

        //订单详情
        $ordermodel = D("service_order");
        $order = $ordermodel->find($orderid);
        if(empty($order)){
            E("订单不存在");
        }
        $order["thumb"] = $this->DoUrlHandle($order["thumb"]);
        $order["service_avatar"] = $this->DoUrlHandle($order["service_avatar"]);
        $order["coupon_money"] = getNumberFormat($order["coupon_money"]);
        $order["total_amount"] = getNumberFormat($order["total_amount"]);
        $order["amount"] = getNumberFormat($order["amount"]);
		//平台补贴（优惠券金额）
        $order["platform_money"] = getNumberFormat($order["platform_money"]);
        
        //订单综合状态
        $order["com_status"] = $this->GetServiceOrderStatus($order);
        
        $begintime = strtotime($order["begintime"]);
		$order["begintime"] = date("Y/m/d H:i", $begintime);
		$endtime = strtotime($order["endtime"]);
		if(date("Y/m/d", $begintime) == date("Y/m/d", $endtime)){
			$order["endtime"] = date("H:i", $endtime);
		} else{
			$order["endtime"] = date("Y/m/d H:i", $endtime);
		}

		//是否评论
		$order["is_comment"] = 0;
		if($order["commentid"] > 0){
			$order["is_comment"] = 1;
		}
        
        $order['isbattle'] = '0';
        if($this->UserAuthCheckLogin()) {
            $user = $this->AuthUserInfo;

            $time = time();
            if($time < $begintime){ //可预约服务时间内
                if($user["profile"]["province"] == $order["province"] && $user["profile"]["city"] == $order["city"]
                    && $user["profile"]["region"] == $order["region"]){
                    if($order["type"] == 1 && in_array(2, $user["role"])){ //送餐员
                        $order['isbattle'] = '1';
                    }
                }
            }
        }

        //服务人员详情
        if($order["service_userid"] > 0){
            $usermodel = D("user_profile");
            $map = array("u.status"=>200, "up.status"=>1, "u.id"=>$order["service_userid"]);
            $serviceuser = $usermodel->alias("up")->join("left join sj_user as u on u.id=up.userid")->join("left join sj_user_role as ur on u.id=ur.userid")
                ->field("u.id,u.avatar,up.realname,up.gender,up.birth,up.mobile,up.major_level,up.service_level,up.work_year,up.education,up.major,up.language,up.comment_percent,ur.role as service_role")->where($map)->find();
            if ($serviceuser){
                $serviceuser['age'] = getAge($serviceuser['birth']);
                $serviceuser['avatar'] = $this->DoUrlHandle($serviceuser["avatar"]);
            }
            //服务交互记录
            $recordmodel = D("service_order_record");
            $map = array("orderid"=>$orderid, "userid"=>$order["service_userid"],
                "execute_status"=>3);
            $record = $recordmodel->where($map)->select();
        }

        $data = array(
            "order"=>$order, "serviceuser"=>$serviceuser, "record"=>$record
        );

        return $data;
    }
}