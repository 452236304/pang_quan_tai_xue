<?php
namespace Admin\Controller;
use Think\Controller;
class ServiceOrderController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $order = "createdate desc";
        $param = $this->getMap();
        $map = array("status"=>array("neq", -1));
        if(in_array($param["type"], [1,2])){
            $map["type"] = $param["type"];
        }
        if($param["admin_status"] == 1){
            $map["admin_status"] = 1;
        }
		if($param["admin_status"] == 2){
		    $map["admin_status"] = 2;
		}
        if ($param['status'] == 'sh') {
            //售后
            $map = array("status"=>array("in", [5,6]) ,"type"=>$param["type"]);
        } else if ($param['status'] == 'sy') {
            //爽约
            $map = array("execute_status"=>7);
        } else if ($param['status'] == 'dq') {
            //待抢
            $map = array("service_userid"=>0, "admin_status"=>1, 'pay_status'=>3, 'status'=>1);
        }
        
        if($param["keyword"]){
            $where["sn"] = array("like","%".$param["keyword"]."%");
            $where["nickname"] = array("like","%".$param["keyword"]."%");
            $where["title"] = array("like","%".$param["keyword"]."%");
            $where["contact"] = array("like","%".$param["keyword"]."%");
            $where["mobile"] = array("like","%".$param["keyword"]."%");
            $where["keyword"] = array("like","%".$param["keyword"]."%");
            $where["_logic"] = "OR";
            $map["_complex"] = $where;
        }

        $where = array();
        
        $outtime = date("Y-m-d H:i:s", strtotime("-30 minute", time()));

        $where["outtime"] = $outtime;
		if($param["orderstatus"] && $param["orderstatus"] != -1){
            
            switch ($param["orderstatus"]) {
                case 1: //已超时
                    $map['status'] = 1;
                    $map['pay_status'] = 0;
                    $map['createdate'] = array('lt', $outtime);
                    break;
                case 2: //待付款
                    $map['status'] = 1;
                    $map['pay_status'] = 0;
                    break;
                case 3: //已取消
                    $map['status'] = 2;
                    break;
                case 4: //已完成
                    $map['status'] = 4;
                    break;
                case 5: //申请退款
                    $map['status'] = 5;
                    break;
                case 6: //已退款
                    $map['status'] = 6;
                    break;
                case 7: //待审核
                    $map['status'] = 1;
                    $map['pay_status'] = 3;
                    $map['admin_status'] = 0;
                    break;
                case 8: //待接单
                    $map['status'] = 1;
                    $map['pay_status'] = 3;
                    $map['admin_status'] = 1;
                    $map['service_userid'] = 0;
                    break;
                case 9: //待确认开始
                    $map['status'] = 1;
                    $map['pay_status'] = 3;
                    $map['admin_status'] = 1;
                    $map['service_userid'] = array('gt', 0);
                    $map['execute_status'] = 1;
                    break;
                case 10: //待服务中
                    $map['status'] = 1;
                    $map['pay_status'] = 3;
                    $map['admin_status'] = 1;
                    $map['service_userid'] = array('gt', 0);
                    $map['execute_status'] = 2;
                    break;
                case 11: //待确认完成
                    $map['status'] = 1;
                    $map['pay_status'] = 3;
                    $map['admin_status'] = 1;
                    $map['service_userid'] = array('gt', 0);
                    $map['execute_status'] = 3;
                    break;
                case 12: //线下评估中
                    $map['status'] = 1;
                    $map['pay_status'] = 3;
                    $map['admin_status'] = 1;
                    $map['service_userid'] = array('gt', 0);
                    $map['assess'] = 1;
                    $map['assess_status'] = 1;
                    break;
                case 13: //待缴付尾款
                    $map['status'] = 1;
                    $map['pay_status'] = 3;
                    $map['admin_status'] = 1;
                    $map['assess'] = 1;
                    $map['assess_status'] = 2;
                    $map['again_status'] = 1;
                    break;
            }
            $where['orderstatus'] = $param["orderstatus"];
		}
		if($param["pay_status"] && $param["pay_status"] != -1){
			$where['pay_status'] = $map['pay_status'] = $param["pay_status"];
        }
        
        D("service_order")->where(array('notice'=>0))->save(array('notice'=>1));
        $data = $this->pager("service_order", "10", $order, $map, $param);

        $this->assign($data);
		$this->assign('where', $where);
        $this->assign("map", $this->getMap());
        $this->show();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
        $model = D("service_order");
        $doinfo = I("get.doinfo");
        $data = $model->where('id='.$id)->find();
        if (empty($data)) {
            alert_back('订单不存在');
        }

        $param = $this->getMap();
        if($doinfo == "modify"){
            if ($param['status'] != 'sh') {//售后订单没有审核按钮
                $d["admin_status"] = I("post.admin_status",0);

                if($id > 0){
                    $model->where("id=".$id)->save($d);
                    //订单审核通过后自动通过照护人
                    if ($d["admin_status"] == 1 && $data['careid'] > 0) {
                        D("user_care")->where('id='.$data['careid'])->save(array('status'=>1));
                    }
                    //审核通过后发送消息推送
                    if ($d["admin_status"] == 1 && $data['admin_status'] == 0) {
                        //直接预约服务人员审核通过后发送信息
                        if($data['service_userid'] > 0 && $data['type'] == 2){
                            $this->receivcemessage($data);
                        } else{
                            $this->unreceivcemessage($data);
                        }
                    }
                }
            }
            $this->redirect("ServiceOrder/listad", $param);
        }
        if ($data) {
            $care = D("user_care")->find($data['careid']);
			switch($care['level']){
				case 1:
					$care['level']='自理';
					break;
				case 2:
					$care['level']='轻度失能';
					break;
				case 3:
					$care['level']='中度失能';
					break;
                case 4:
                    $care['level']='重度失能';
                    break;
                case 5:
                    $care['level']='特重护理';
                    break;
				default:
					$care['level']='未知护理';
			}
            $comment = D("service_comment")->where('orderid='.$data['id'])->find();

            //服务交互记录
            $recordmodel = D("service_order_record");
            $map = array("orderid"=>$data["id"]);
            $record = $recordmodel->where($map)->order("id asc")->select();

            //服务地址坐标
            $service_coordinate = array(
                "longitude"=>$data["longitude"], "latitude"=>$data["latitude"],
                "time"=>$data["createdate"], "stamp"=>strtotime($data["createdate"])
            );

            foreach($record as $k=>$v){
                //开始服务坐标
                if($v["execute_status"] == 1){
                    $begin_coordinate = array(
                        "longitude"=>$v["longitude"], "latitude"=>$v["latitude"],
                        "time"=>date("Y-m-d H:i", strtotime($v["updatetime"])), "stamp"=>strtotime($v["updatetime"])
                    );
                }
    
                //结束服务坐标
                if($v["execute_status"] == 3){
                    $end_coordinate = array(
                        "longitude"=>$v["longitude"], "latitude"=>$v["latitude"],
                        "time"=>date("Y-m-d H:i", strtotime($v["updatetime"])), "stamp"=>strtotime($v["updatetime"])
                    );
                }

                switch ($v["execute_status"]) {
                    case 1: //服务人员开始
                        $service_user_begin_time = $v["updatetime"];
                        break;
                    case 2: //用户确认开始
                        $user_begin_time = $v["updatetime"];
                        break;
                    case 3: //服务人员完成
                        $service_user_completed_time = $v["updatetime"];
                        break;
                    case 4: //用户确认完成
                        $user_completed_time = $v["updatetime"];
                        break;
                }
            }

            //服务人员坐标
            $coordinatemodel = D("Common/Coordinate");
            $all_coordinate = $coordinatemodel->readcoordinate($data);

            //1.服务人员开始 ~ 用户确认开始 - 坐标
            $service_begin_coordinate = array();
            if($service_user_begin_time){
                $service_user_begin_stamp = strtotime($service_user_begin_time);
            }
            if($user_begin_time){
                $user_begin_stamp = strtotime($user_begin_time);
            }

            //2.用户确认开始 ~ 服务人员完成服务 - 坐标
            $service_execute_coordinate = array();
            if($service_user_completed_time){
                $service_user_completed_stamp = strtotime($service_user_completed_time);
            }

            //3.服务完成后一小时坐标
            $service_completed_coordinate = array();
            if($service_user_completed_stamp){
                $service_completed_one_stamp = strtotime("+1 hour", $service_user_completed_stamp);
            }
            foreach($all_coordinate as $k=>$v){

                //匹配开始服务坐标
                if($begin_coordinate && (empty($begin_coordinate["longitude"]) || empty($begin_coordinate["latitude"])) 
                    && $k == date("Y-m-d", $begin_coordinate["stamp"])){
                    foreach($v as $ik=>$iv){
                        if($begin_coordinate["stamp"] < $iv["stamp"]){
                            $begin_coordinate["longitude"] = $iv["longitude"];
                            $begin_coordinate["latitude"] = $iv["latitude"];
                            break;
                        }
                    }
                }

                //匹配结束服务坐标
                if($end_coordinate && (empty($end_coordinate["longitude"]) || empty($end_coordinate["latitude"])) 
                    && $k == date("Y-m-d", $end_coordinate["stamp"])){
                    foreach($v as $ik=>$iv){
                        if($end_coordinate["stamp"] < $iv["stamp"]){
                            $end_coordinate["longitude"] = $iv["longitude"];
                            $end_coordinate["latitude"] = $iv["latitude"];
                            break;
                        }
                    }
                }

                //1.服务人员开始 ~ 用户确认开始 - 坐标
                if($service_user_begin_stamp && $user_begin_stamp){
                    if($k >= date("Y-m-d", $service_user_begin_stamp) && $k <= date("Y-m-d", $user_begin_stamp)){
                        foreach($v as $ik=>$iv){
                            if($service_user_begin_stamp <= $iv["stamp"] && $iv["stamp"] <= $user_begin_stamp){
                                $service_begin_coordinate[] = $iv;
                            }
                        }
                    }
                } else if($service_user_begin_stamp){
                    if($k >= date("Y-m-d", $service_user_begin_stamp)){
                        foreach($v as $ik=>$iv){
                            if($service_user_begin_stamp <= $iv["stamp"]){
                                $service_begin_coordinate[] = $iv;
                            }
                        }
                    }
                }

                //2.用户确认开始 ~ 服务人员完成服务 - 坐标
                if($user_begin_stamp && $service_user_completed_stamp){
                    if($k >= date("Y-m-d", $user_begin_stamp) && $k <= date("Y-m-d", $service_user_completed_stamp)){
                        foreach($v as $ik=>$iv){
                            if($user_begin_stamp <= $iv["stamp"] && $iv["stamp"] <= $service_user_completed_stamp){
                                $service_execute_coordinate[] = $iv;
                            }
                        }
                    }
                } else if($user_begin_stamp){
                    if($k >= date("Y-m-d", $user_begin_stamp)){
                        foreach($v as $ik=>$iv){
                            if($user_begin_stamp <= $iv["stamp"]){
                                $service_execute_coordinate[] = $iv;
                            }
                        }
                    }
                }

                //3.服务完成后一小时坐标
                if($service_user_completed_stamp && $service_completed_one_stamp){
                    if($k >= date("Y-m-d", $service_user_completed_stamp) && $k <= date("Y-m-d", $service_completed_one_stamp)){
                        foreach($v as $ik=>$iv){
                            if($service_user_completed_stamp <= $iv["stamp"] && $iv["stamp"] <= $service_completed_one_stamp){
                                $service_completed_coordinate[] = $iv;
                            }
                        }
                    }
                }
            }

            $coordinate = array(
                "service_coordinate"=>$service_coordinate, "begin_coordinate"=>$begin_coordinate, "end_coordinate"=>$end_coordinate,
                "all_coordinate"=>$all_coordinate, "service_begin_coordinate"=>$service_begin_coordinate,
                "service_execute_coordinate"=>$service_execute_coordinate, "service_completed_coordinate"=>$service_completed_coordinate
            );
        }

        $param["outtime"] = date("Y-m-d H:i:s", strtotime("-30 minute", time()));

        $this->assign("care", $care);
        $this->assign("comment", $comment);
        $this->assign("record", $record);
        $this->assign("coordinate", $coordinate);
        $this->assign("info", $data);
        $this->assign("map", $param);
        $this->show();

    }

    /**
     * 指派服务人员
     * [modifyad]
     * @return [type] [description]
     */
    public function appoint(){
        $id = I("get.id", 0);
        $model = D("service_order");
        $doinfo = I("get.doinfo");
        $data = $model->where('id='.$id)->find();
        if (empty($data)) {
            alert_close('订单不存在');
        }
        if($doinfo == "modify"){

            $usermodel = D("user_profile");

            $service_userid = I("post.service_userid", 0);

            $map = array("userid"=>$service_userid);
            $service_user = $usermodel->field("id,realname")->where($map)->find();
			$map = array("id"=>$service_userid);
			$service_user_info = D('user')->field("id,avatar")->where($map)->find();
            if(empty($service_user)){
                $this->error("指定的服务人员不存在");
            }
            $d["service_userid"] = $service_userid;
            $d["service_realname"] = $service_user["realname"];
			$d["service_avatar"] = $service_user_info["avatar"];

            $begintime = I("post.begintime");
            $endtime = I("post.endtime");
            if (empty($begintime) || !checkDateTime($begintime)) {
                alert_back('请选择开始时间');
            }
            if (empty($endtime) || !checkDateTime($begintime)) {
                alert_back('请选择结束时间');
            }
            if (strtotime($begintime) > strtotime($endtime)) {
                alert_back('开始时间不能大于结束时间');
            }

            $d["begintime"] = $begintime;
            $d["endtime"] = $endtime;

            if($id > 0){
                $d["admin_status"] = 1; //审核已通过

                if ($data['execute_status'] == 7) {
                    $d["execute_status"] = 0;
                    $d["execute_time"] = null;
                }
                $model->where("id=".$id)->save($d);
                alert_back('保存成功');
            }

            $this->redirect("ServiceOrder/appoint", 'id='.$id);
        }

        $relationmodel = D("user_project_relation");
        $map = array("type"=>2, "projectid"=>$data["projectid"]);
        $checkproject = $relationmodel->where($map)->select();

        $userids = [];
        foreach($checkproject as $k=>$v){
            if(!in_array($v["userid"], $userids)){
                $userids[] = $v["userid"];
            }
        }

        $serviceuser = [];

        if(count($userids) > 0){
            $usermodel = D("user");

            //爽约时间
            $planetime = date("Y-m-d H:i:s", strtotime("-3 month", time()));
            
            $map = array(
                "u.status"=>200, "up.status"=>1, "u.id"=>array("in", $userids),
                "up.province"=>$data["province"], "up.city"=>$data["city"], "up.region"=>$data["region"],
                "plane_time"=>array(array("exp", "is null"), array("elt", $planetime), "or")
            );

            $serviceuser = $usermodel->alias("u")->join("left join sj_user_profile as up on up.userid=u.id")
                ->field("u.id,u.realname,u.mobile,up.mobile as upmobile,up.realname as uprealname,up.province,up.city,up.region,up.plane_time")
                ->where($map)->order("u.registertime desc")->select();
        }
		
        //方便验证
        $data['begintime'] = date("Y-m-d H:i",strtotime($data["begintime"]));
        $data['endtime'] = date("Y-m-d H:i",strtotime($data["endtime"]));

        $this->assign("info", $data);
        $this->assign("serviceuser", $serviceuser);
        $this->assign("map", $this->getMap());
        $this->show();

    }

    /**
     * 解除爽约
     * [modifyad]
     * @return [type] [description]
     */
    public function removead(){
        $id = I("get.id", 0);
        $model = D("service_order");
        if($id > 0){
            $o_entity = array("execute_status"=>0, "execute_time"=>"");
            $model->where("id=".$id)->save($o_entity);
        }
        $this->redirect("ServiceOrder/listad", $this->getMap());
    }
    
     /**
     * 服务表单
     * [modifyad]
     * @return [type] [description]
     */
     public function orderform(){
        $id = I("get.id");
        $model = D("service_order");
        $doinfo = I("get.doinfo");
        $order = $model->find($id);
        if(empty($order)){
            alert_close("订单不存在");
        }

        if($doinfo == "modify"){
            $formid = I("post.formid");

            if(count($formid) > 0){
                $formids = join(",", $formid);
            } else{
                $formids = "";
            }

            $entity = array(
                "formids"=>$formids
            );
            $map = array("id"=>$id);
            $model->where($map)->save($entity);

            $this->redirect("ServiceOrder/orderform", 'id='.$id);
        }

        $formids = $order["formids"];
        if(empty($formids)){
            $formids = [];
        } else{
            $formids = explode(",", $formids);
        }

        $formmodel = D("service_form");
        $list = $formmodel->select();

        $form = array();
        $selected = [];
        foreach($list as $k=>$v){
            $form[$v["category"]][] = $v;

            if(in_array($v["id"], $formids)){
                $selected[] = $v;
            }
        }
        $data = array(
            "form"=>$form, "order"=>$order, "selected"=>$selected
        );

        $this->assign($data);
        $this->show();
     }

     /**
     * 服务表单日志
     * [modifyad]
     * @return [type] [description]
     */
     public function orderformrecord(){
        $orderid = I("get.orderid", 0);
        
        $ordermodel = D("service_order");
		
		$order = $ordermodel->find($orderid);
		if(empty($order)){
			$this->error("服务订单不存在");
		}
        if($order["assess"] != 1 && $order["time_type"] != 4 && $order["doctor"] != 1){
			$this->error("当前服务订单必须为线下评估/日间照护/遵照医嘱");
		}

		$list = [];

		if(empty($order["formids"])){
			$this->error("当前服务订单没有设置服务表单，无法查看服务表单日志");
		}
		$formids = explode(",", $order["formids"]);
		
		$begin = strtotime(date("Y-m-d", strtotime($order["begintime"])));
		$end = strtotime(date("Y-m-d", strtotime($order["endtime"])));

		//服务订单表单项目集合
		$formmodel = D("service_form");

		$map = array("id"=>array("in", $formids));
		$orderform = $formmodel->where($map)->select();
		foreach($orderform as $k=>$v){
			$v["recordid"] = 0;
			$v["completed"] = 0;
			$v["remark"] = "";

			$orderform[$k] = $v;
		}

		//服务订单表单项目记录集合
		$recordmodel = D("service_order_form_record");

		//起始日期
		$date = $begin;
		//相差天数
		$day = floor(($end - $begin)/3600/24);
		for($i=0;$i<=$day;$i++){
			$record_date = date("Y-m-d", strtotime("+".$i." day", $date));

			$item = array(
				"id"=>($i+1), "orderid"=>$orderid, "record"=>0, "date"=>$record_date, "list"=>[]
			);

			$map = array("orderid"=>$orderid, "formid"=>array("in", $formids), "record_date"=>$record_date);
			$forms = $recordmodel->alias("r")->join("left join sj_service_form as f on r.formid=f.id")
				->field("f.id,f.category,f.title,f.source,r.id as recordid,r.completed,r.remark")
				->where($map)->select();
			if(count($forms) <= 0){
				$forms = $orderform;
			} else{
				$item["record"] = 1;
			}

			$item["list"] = $forms;

			$list[] = $item;
        }
        
        $this->assign("list", $list);
        $this->show();
     }

     /**
      * 查看服务总结
      * [modifyad]
      * @return [type] [description]
      */
      public function carerecord(){
         $id = I("get.orderid", 0);
         $model = D("service_order");
         $order = $model->where('id='.$id)->find();
         if (empty($order)) {
             alert_close('订单不存在');
         }

         $recordmodel = D("service_order_care_record");
         $map = array("orderid"=>$order["id"]);
         $info = $recordmodel->where($map)->find();
         if($info){
            if($info["images"]){
                $images = $info["images"];
                $images = str_replace("\r\n", "|", $images);
                $images = str_replace(",", "|", $images);
                $info["images"] = explode("|", $images);
            }
            if($info["video"]){
                $video = $info["video"];
                $video = str_replace("\r\n", "|", $video);
                $video = str_replace(",", "|", $video);
                $info["video"] = explode("|", $video);

                $info["video_count"] = count($info["video"]);
            } else{
                $info["video_count"] = 0;
            }
         }
 
         $this->assign("order", $order);
         $this->assign("info", $info);
         $this->assign("map", $this->getMap());
         $this->show();
      }

    /**
     * 回复评论
     * [modifyad]
     * @return [type] [description]
     */
    public function comment(){
        $id = I("get.id", 0);
        $model = D("service_comment");
        $doinfo = I("get.doinfo");
        $data = $model->where('id=' . $id)->find();
        if (empty($data)) {
            alert_close('评论不存在');
        }
        if($doinfo == "modify"){
            $d["platform_reply"] = I("post.platform_reply");

            if (empty($d["platform_time"])) {
                $d["platform_time"] = date("Y-m-d H:i");
            }
            $d["adminid"] = $_SESSION['manID'];
            if($id > 0){
                $model->where("id=".$id)->save($d);
                alert_back('保存成功');
            }
        }
        $this->assign("info", $data);
        $this->show();
    }

    public function getMap(){
        $type = I("get.type");
        $p = I("get.p");
        $admin_status = I("get.admin_status");
        $status = I("get.status");
        $pay_status = I("post.pay_status");
        if(empty($pay_status)){
            $pay_status = I("get.pay_status");
        }
        $orderstatus = I("post.orderstatus");
        if(empty($orderstatus)){
            $orderstatus = I("get.orderstatus");
        }
        $keyword = I("post.keyword");
        if(empty($keyword)){
            $keyword = mb_convert_encoding($_GET["keyword"], "UTF-8", "gb2312");
        }
        $map = array("p"=>$p, "type"=>$type, 'admin_status'=>$admin_status, 'status'=>$status, "orderstatus"=>$orderstatus, 'keyword'=>$keyword);
        return $map;
    }

    //已接单的消息模版
    protected function receivcemessage($order){
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
        $content .= "【联系方式】：".substr_replace($profile["mobile"])."<br/>";
        $content .= "【订单号】：".$order["sn"]."<br/>";
        if($order["other_remark"]){
            $content .= "【用户备注】：".$order["other_remark"]."<br/>";
        }
        if($order["platform_money"] > 0){
            $content .= "【平台补贴】：".$order["platform_money"]."元<br/>";
        }
        $content .= "订单已审核成功，服务人员：".$profile["realname"]."，联系电话：".$profile["mobile"]."，将准时上门服务，请耐心等候。。祝您身体健康，生活愉快！";
        $content .= '</p>';
        $message_entity = array(
            "userid"=>$order["userid"], "title"=>$order["title"], "content"=>$content,
            "hybrid"=>"client", "type"=>1, "status"=>0, "createdate"=>date("Y-m-d H:i:s")
        );
        $messagemodel->add($message_entity);

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
            $content .= "订单已审核成功，请您准时上门服务，如出现不能按时服务情况请提前至少3小时联系保椿客服，电话：020-38894803。";
            $content .= '</p>';
            $message_entity = array(
                "userid"=>$order["service_userid"], "title"=>$order['title'], "content"=>$content,
                "hybrid"=>"service", "type"=>1, "status"=>0, "createdate"=>date("Y-m-d H:i:s")
            );
            $messagemodel->add($message_entity);
        }

        return true;
    }

    //待接单的消息模版
    protected function unreceivcemessage($order){

        //用户订单消息
        $messagemodel = D("user_message");
        
        //标题
        $title = "一点椿-服务项目《".$order["title"]."》";
        
        $content = '<p>';
        $content .= "【订单号】：".$order["sn"]."<br/>";
        $content .= "【订单内容】：".$title."<br/>";
        $content .= "您的订单已经审核通过，请耐心等待服务人员接单。祝您身体健康，生活愉快！";
        $content .= '</p>';
        $message_entity = array(
            "userid"=>$order["userid"], "title"=>$title, "content"=>$content,
            "hybrid"=>"client", "type"=>1, "status"=>0, "createdate"=>date("Y-m-d H:i:s")
        );
        $messagemodel->add($message_entity);
		
		
		//短信提醒可接订单
		$usermodel = D('user');
		//爽约时间
		$planetime = date("Y-m-d H:i:s", strtotime("-3 month", time()));
		
		$map = array(
		    "u.status"=>200, "up.status"=>1,
		    "up.province"=>$order["province"], "up.city"=>$order["city"], "up.region"=>$order["region"],
		    "plane_time"=>array(array("exp", "is null"), array("eq", ""), array("elt", $planetime), "or")
		);
		$serviceuser = $usermodel->alias("u")->join("left join sj_user_profile as up on up.userid=u.id")
		    ->field("up.mobile")
		    ->where($map)->order("u.registertime desc")->select();
		$RequestSms=D('Common/RequestSms');
		foreach($serviceuser as $k=>$v){
			$RequestSms->SendRemindOrder($v);
		}
    }

	//修改订单价格
	public function change_price(){
	    $id = I("get.id", 0);
	    $doinfo = I("get.doinfo");
	    $model = D("service_order");
	    
	    if($doinfo == "modify"){
	        $d["total_amount"] = I("post.total_amount", 0);
	        $d["amount"] = I("post.amount", 0);
	    	
	        if($id > 0){
	            $model->where("id=".$id)->save($d);
	        }
	        
	        $this->redirect("ServiceOrder/listad", $this->getMap());
	    }
	    
	    $data["info"] = $model->find($id);
	    $this->assign($data);
	    $this->assign("map", $this->getMap());
	    $this->display();
	}
}