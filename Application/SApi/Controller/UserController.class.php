<?php
namespace SApi\Controller;
use Think\Controller;
class UserController extends BaseLoggedController {
	
	//用户信息
	public function userinfo(){
		$user = $this->AuthUserInfo;
		$user["avatar"] = $this->DoUrlHandle($user["avatar"]);
		$user['is_profile'] = $user['profile']['status'];
		if($user['is_profile'] == 0){
			$user['nickname'] .= '(审核中)';
		}
		return $user;
	}

	//我的资质
	public function userprofile(){
		$user = $this->AuthUserInfo;
		
		$user["avatar"] = $this->DoUrlHandle($user["avatar"]);

		$papersmodel = D("user_papers");
		$map = array("userid"=>$user["id"]);
		$papers = $papersmodel->where($map)->order("type asc")->select();
        $user["papers"] = array();
		foreach($papers as $k=>$v){
			$v["images"] = $this->DoUrlListHandle($v["images"]);
            $v['begintime'] = date("Y-m-d",strtotime($v["begintime"]));
            $v['validtime'] = date("Y-m-d",strtotime($v["validtime"]));
			$user["papers"]['type_'.$v["type"]][] = $v;
		}
		$profile_info=D('user_profile')->where($map)->find();
		$user['idcard_image']=$this->DoUrlListHandle($profile_info['idcard_image']);
		$user['idcard_type']=$profile_info['idcard_type'];
		$user['test_image']=$this->DoUrlHandle($profile_info['test_image']);
		return $user;
	}

	//我的评价
	public function comment(){
		$user = $this->AuthUserInfo;

		$model = D("service_comment");

		$map = array("service_userid"=>$user["id"]);
		$page = I("get.page", 1);
        $row = I("get.row", 10);
		$begin = ($page-1)*$row;
		
		$order = "createdate desc";
        $count = $model->where($map)->count();
		$totalpage = ceil($count/$row);
		$list = $model->where($map)->order($order)->limit($begin, $row)->select();

		$this->SetPaginationHeader($totalpage, $count, $page, $row);

        $ordermodel = D("service_order");

		foreach ($list as $k=>$v) {
			$v["avatar"] = $this->DoUrlHandle($v["avatar"]);
			$v["service_avatar"] = $this->DoUrlHandle($v["service_avatar"]);
			$v["images"] = $this->DoUrlListHandle($v["images"]);

			//服务信息
            $order = $ordermodel->where('id='.$v['orderid'])->find();
            $v["service_title"] = $order['title'];
            $v["service_begintime"] = $order['begintime'];
            $v["service_endtime"] = $order['endtime'];

			$list[$k] = $v;
		}
		
		return $list;
	}

	//我的评价 - 详情
	public function commentdetail(){
		$user = $this->AuthUserInfo;

        $commentid = I("get.commentid", 0);
        if(empty($commentid)){
            E("请选择要查看的订单评价");
        }

        $model = D("service_comment");
        
        $detail = $model->find($commentid);
        if(empty($detail)){
            E("查看的订单评价不存在");
        }

        $detail["avatar"] = $this->DoUrlHandle($detail["avatar"]);
        $detail["service_avatar"] = $this->DoUrlHandle($detail["service_avatar"]);
        $detail["images"] = $this->DoUrlListHandle($detail["images"]);

        $ordermodel = D("service_order");

        //服务信息
        $order = $ordermodel->where('id='.$detail['orderid'])->find();
        $detail["service_title"] = $order['title'];
        $detail["service_begintime"] = $order['begintime'];
        $detail["service_endtime"] = $order['endtime'];
		
		return $detail;
	}

    //我的服务对象管理
    public function usercare(){
        $user = $this->AuthUserInfo;

        //0=全部，1=新单发布中
        $type = I("get.type", 0);

        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;

        $ordermodel = D("service_order");

        //获取服务过的用户id
        $map = array("service_userid"=>$user["id"]);
        $orders = $ordermodel->where($map)->select();
        foreach($orders as $k=>$v){
            $userids[] = $v["userid"];
        }
        if(count($userids) <= 0){
            return [];
        }

        $list = array();

        if ($type == 1) {//新单发布中
            //服务人员角色
            $roles = $user["role"];

            $ordermodel = D("service_order");

            if(in_array(2, $roles)){ //送餐员
                $map = array(
                    "so.userid"=>array('in',$userids), "so.admin_status"=>1, "so.type"=>1, "so.service_userid"=>0, "so.status"=>1, "so.execute_status"=>0,
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
                    "so.userid"=>array('in',$userids), "so.admin_status"=>1, "so.type"=>2, "service_userid"=>0, "so.status"=>1, "so.execute_status"=>0,
                    "so.pay_status"=>3, "so.projectid"=>array("in", $projectids), "so.province"=>$user["profile"]["province"],
                    "so.city"=>$user["profile"]["city"], "so.region"=>$user["profile"]["region"]
                );
            }

            $order = "so.createdate desc";
            $count = $ordermodel->alias("so")->join("left join sj_service_category as sc on so.categoryid=sc.id")
                ->join("left join sj_user as u on so.userid=u.id")->where($map)->count();
            $totalpage = ceil($count/$row);
            $list = $ordermodel->alias("so")->join("left join sj_service_category as sc on so.categoryid=sc.id")
                ->join("left join sj_user as u on so.userid=u.id")->field("so.*,sc.role as service_role,u.avatar as user_avatar")
                ->where($map)->order($order)->limit($begin, $row)->select();


            $projectmodel = D("service_project");//服务项目
            foreach($list as $k=>$v){

                $v["thumb"] = $this->DoUrlHandle($v["thumb"]);
                $v["service_avatar"] = $this->DoUrlHandle($v["service_avatar"]);
                if(empty($v["user_avatar"])){
                    $v["user_avatar"] = "/upload/default/default_avatar.png";
                }
                $v["user_avatar"] = $this->DoUrlHandle($v["user_avatar"]);
            
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

        }else{

            $map = array(
                "so.userid"=>array('in',$userids), "so.admin_status"=>1, "so.status"=>array('in',[1,4]),
                "so.pay_status"=>3, 'so.service_userid'=>$user["id"]
            );
            $order = "userid ASC,createdate desc";
            $count = $ordermodel->alias("so")->where($map)->count();
            $totalpage = ceil($count/$row);
            //$order = $ordermodel->alias("")->where($map)->order("userid ASC,createdate desc")->limit($begin, $row)->select();
            $order = $ordermodel->alias("so")->join("left join sj_user as u on so.userid=u.id")
			->field("so.*,u.avatar as user_avatar")->where($map)->order($order)->limit($begin, $row)->select();

            $usermodel = D("user");//用户端用户
            $userlist = array();
            foreach ($order as $key =>$val) {
                $val["thumb"] = $this->DoUrlHandle($val["thumb"]);
                $val["service_avatar"] = $this->DoUrlHandle($val["service_avatar"]);
                if(empty($val["user_avatar"])){
                    $val["user_avatar"] = "/upload/default/default_avatar.png";
                }
                $val["user_avatar"] = $this->DoUrlHandle($val["user_avatar"]);

                $begintime = strtotime($val["begintime"]);
                $val["begintime"] = date("Y/m/d H:i", $begintime);
                $endtime = strtotime($val["endtime"]);
                if(date("Y/m/d", $begintime) == date("Y/m/d", $endtime)){
                    $val["endtime"] = date("H:i", $endtime);
                } else{
                    $val["endtime"] = date("Y/m/d H:i", $endtime);
                }

                //订单综合状态
                $val["com_status"] = $this->GetServiceOrderStatus($val);

                //用户id分类
                $userlist[$val['userid']][] = $val;
            }
            foreach ($userlist as $key => $val) {
                foreach ($val as $k => $v) {
                    $list[] = $v;
                }
            }
        }

        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        return $list;
    }

	//我的消息
	public function message(){
		$user = $this->AuthUserInfo;

		$model = D("user_message");

		//消息类型（0=系统，1=订单）
		$type = I("get.type", 0);

		$map = array("hybrid"=>"service", "userid"=>$user["id"], "type"=>$type);
		$page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;
        
        $order = "createdate desc";
        $count = $model->where($map)->count();
        $totalpage = ceil($count/$row);
        $list = $model->where($map)->order($order)->limit($begin, $row)->select();

		$this->SetPaginationHeader($totalpage, $count, $page, $row);
        
        foreach($list as $k=>$v){
            $v["createdate"] = getMessageDateFormat($v["createdate"]);

            $list[$k] = $v;
        }
		
		return $list;
	}

	//我的未读消息
	public function unreadmessage(){
		$user = $this->AuthUserInfo;

		$model = D("user_message");

		//消息类型（0=系统，1=订单）
		$type = I("get.type", -1);

		$map = array("hybrid"=>"service", "status"=>0, "userid"=>$user["id"]);
		if(in_array($type, [0,1])){
			$map["type"] = $type;
		}

		$list = $model->where($map)->select();
        
        foreach($list as $k=>$v){
            $v["createdate"] = getMessageDateFormat($v["createdate"]);

            $list[$k] = $v;
        }

		$count = count($list);

		return array("type"=>$type, "count"=>$count, "list"=>$list);
	}
	
	//我的服务项目
	public function project(){
		$user = $this->AuthUserInfo;

		$roles = $user["role"];
		
		if(in_array(2, $roles)){ //送餐员 - 适老配餐商品
			$model = D("product");

			$order = "top desc, recommend desc, ordernum asc, sales desc";
			$map = array("status"=>1, "categoryid"=>1);
			$list = $model->where($map)->order($order)->select();

		} else{ //非送餐员 - 关联服务项目
			$model = D("user_project_relation");
		
			$map = array("upr.userid"=>$user["id"], "upr.type"=>2);
			$list = $model->alias("upr")->join("sj_service_project as sp on upr.projectid=sp.id")->field("sp.*")->where($map)->select();
		}

		foreach($list as $k=>$v){
			$v["thumb"] = $this->DoUrlHandle($v["thumb"]);

			$list[$k] = $v;
		}

		return $list;
	}
	//我的邀请好友
	public function my_friends(){
		$user = $this->AuthUserInfo;
		$profile=D('user_profile')->where('userid='.$user['id'])->find();
		$map=array();
		$map['up.be_referral_code']=$profile['referral_code'];
		$map['up.status']=1;
		$list=D('user_profile')->alias('up')->field('u.id,u.nickname,u.avatar,u.registertime')->join('left join sj_user u on u.id=up.userid')->where($map)->order('u.registertime desc')->select();
		$count=D('user_profile')->alias('up')->join('left join sj_user u on u.id=up.userid')->where($map)->count();
		foreach($list as $k=>$v){
			$v['avatar']=$this->DoUrlHandle($v['avatar']);
			$list[$k]=$v;
		}
		return array('count'=>$count,'list'=>$list);
	}
}