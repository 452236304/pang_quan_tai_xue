<?php
namespace Admin\Controller;
use Think\Controller;
class CouponController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("coupon");
        $map = array("type"=>I("get.type", 0));
        $data = $model->where($map)->order("id asc")->select();
        $this->assign("data", $data);
        $this->assign("map", $this->getMap());
        $this->show();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
        $id = I("get.id", 0);
    	$doinfo = I("get.doinfo");
        $model = D("coupon");
        $data["info"] = $model->find($id);

        if($doinfo == "modify"){
            $d["type"] = I("get.type");
			$d["level"] = I("post.level",0);
			$d["coupon_type"] = I("post.coupon_type",0);
			$d["product_id"] = I("post.product_id",0);
			$d["service_id"] = I("post.service_id",0);
			$d["org_id"] = I("post.org_id",0);
            $d["status"] = I("post.status", 1);
            $d["title"] = I("post.title");
            $d["subtitle"] = I("post.subtitle");
            $d["money"] = I("post.money", 0);
            $d["min_amount"] = I("post.min_amount", 0);
            $d["count"] = I("post.count", 0);
            $d["use_date"] = I("post.use_date", 0);
            // $d["use_start_date"] = I("post.use_start_date");
            // $d["use_end_date"] = I("post.use_end_date");
			
			if($d['coupon_type']==0){
				if($d['money']==0){
					alert_back("请填写金额");
				}
			}elseif($d['coupon_type']==1){
				if($d['product_id']==0){
					alert_back("请选择商品");
				}
			}elseif($d['coupon_type']==2){
				if($d['service_id']==0){
					alert_back("请选择服务");
				}
			}elseif($d['coupon_type']==3){
				if($d['org_id']==0){
					alert_back("请选择机构");
				}
			}
			
			
            if($id == 0){
                //券码
                while(true){
                    $code = random(10, "number");
                    $check = $model->where(array('code'=>$code))->find();
                    if(empty($check)){
                        $d["code"] = $code; break;
                    }
                }

                $d["createdate"] = date("Y-m-d H:i");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                $model->add($d);
            }
            
            $this->redirect("Coupon/listad", $this->getMap());
        }
		$map = array('status'=>1);
		$data['product']=D('product')->where($map)->select();
		$data['service']=D('service_project')->where($map)->select();
		$data['org']=D('org')->where($map)->select();
        $this->assign($data);
        $this->assign("map", $this->getMap());
    	$this->show();
    }

    /**
     * [sendcoupon]
     * @return [type] [description]
     */
    public function sendcoupon(){
        $couponid = I("get.couponid", 0);
    	$doinfo = I("get.doinfo");
        $model = D("coupon");
        $info = $model->find($couponid);
        $data["info"] = $info;
        if($info["status"] != 1){
            alert("优惠券状态异常，无法发放", U("Coupon/listad", $this->getMap()));
        }

        if($doinfo == "modify"){
            $userid = I("post.userid", 0);

            $user_coupon_model = D("user_coupon");
            if ($userid == 'qb') {
                //发放全部用户
                $user_model = D("user");
                $map = array("status"=>200);
                $userids = $user_model->where($map)->getField('id',true);
                if (empty($userids)) {
                    alert_back("无用户可发放");
                }
                if ($info["count"] < count($userids)) {
                    alert_back("优惠券数量少于用户数，发放失败");
                }
                $use_date = $info["use_date"];
                $time = time();
                $Suc = array();
                foreach ($userids as $key => $val) {
                    $ds = array();
                    $ds["couponid"] = $couponid;
                    $ds["type"] = $info["type"];
                    $ds["code"] = $info["code"];
                    $ds["title"] = $info["title"];

                    $ds["use_start_date"] = date("Y-m-d", $time);
                    $ds["use_end_date"] = date("Y-m-d", strtotime("+".$use_date." day", $time));
					$ds['coupon_type']=$info['coupon_type'];
					if($info['coupon_type']==0){
						$ds["money"] = $info["money"];
						$ds["min_amount"] = $info["min_amount"];
					}elseif($info['coupon_type']==1){
						$ds["product_id"] = $info["product_id"];
					}elseif($info['coupon_type']==2){
						$ds["service_id"] = $info["service_id"];
					}elseif($info['coupon_type']==3){
						$ds["org_id"] = $info["org_id"];
					}
                   
					
                    $ds["status"] = 0;
                    $ds["use_type"] = 0;
                    $ds["userid"] = $val;

                    $ds["createdate"] = date("Y-m-d H:i");
					$entity = array(
					    "hybrid"=>'client', "status"=>0, "sendid"=>0, "sender"=>"系统消息", "userid"=>$val,
					    "title"=>'优惠券', "content"=>'您好，您有一张新的'.$info["title"].',价值'.$info["money"].'元，有效期至'.$ds['use_end_date'], "param"=>null,
					    "createdate"=>date("Y-m-d H:i:s"), "type"=>0, "systemid"=>0
					);
					D("user_message")->add($entity);
                    $Suc[] = $user_coupon_model->add($ds);

                }
                $entity = array("count"=>($info["count"]-count($Suc)), "sales"=>($info["sales"]+count($Suc)));
                $model->where("id=".$couponid)->save($entity);
            }else{
                if($userid <= 0){
                    alert_back("请选择要发放优惠券的用户");
                }

                if($info["count"] <= 0){
                    alert_back("优惠券数量不足，发放失败");
                }


                $d["couponid"] = $couponid;
                $d["type"] = $info["type"];
                $d["code"] = $info["code"];
                $d["title"] = $info["title"];

                $use_date = $info["use_date"];
                $time = time();
                $d["use_start_date"] = date("Y-m-d H:i:s", $time);
                $d["use_end_date"] = date("Y-m-d H:i:s", strtotime("+".$use_date." day", $time));
				
				$d['coupon_type']=$info['coupon_type'];
				if($info['coupon_type']==0){
					$d["money"] = $info["money"];
					$d["min_amount"] = $info["min_amount"];
				}elseif($info['coupon_type']==1){
					$d["product_id"] = $info["product_id"];
				}elseif($info['coupon_type']==2){
					$d["service_id"] = $info["service_id"];
				}elseif($info['coupon_type']==3){
					$d["org_id"] = $info["org_id"];
				}
				
                $d["status"] = 0;
                $d["use_type"] = 0;
                $d["userid"] = $userid;

                $d["createdate"] = date("Y-m-d H:i");
				$entity = array(
				    "hybrid"=>'client', "status"=>0, "sendid"=>0, "sender"=>"系统消息", "userid"=>$userid,
				    "title"=>'优惠券', "content"=>'您好，您有一张新的'.$info["title"].',价值'.$info["money"].'元，有效期至'.$d['use_end_date'], "param"=>null,
				    "createdate"=>date("Y-m-d H:i:s"), "type"=>0, "systemid"=>0
				);
				D("user_message")->add($entity);
                $user_coupon_model->add($d);

                $entity = array("count"=>($info["count"]-1), "sales"=>($info["sales"]+1));
                $model->where("id=".$couponid)->save($entity);
            }

            alert("优惠券发放成功", U("Coupon/listad", $this->getMap()));
        }
		
		switch($data['info']['coupon_type']){
			case 1:
				$product=D('product')->field('title')->find($data['info']['product_id']);
				$data['info']['product_title']=$product['title'];
				break;
			case 2:
				$service=D('service_project')->field('title')->find($data['info']['service_id']);
				$data['info']['product_title']=$service['title'];
				break;
			case 3:
				$org=D('org')->field('title')->find($data['info']['org_id']);
				$data['info']['product_title']=$org['title'];
				break;
		}
		
        $user_model = D("user");
        $map = array("status"=>200);
        $users = $user_model->where($map)->select();
        $this->assign("users", $users);
		
        $this->assign($data);

        $map = $this->getMap();
        $map["couponid"] = $couponid;
        $this->assign("map", $map);

    	$this->show();
    }
	
	//根据用户组发放优惠券
	public function classsendcoupon(){
	    $couponid = I("get.couponid", 0);
		$doinfo = I("get.doinfo");
	    $model = D("coupon");
	    $info = $model->find($couponid);
	    $data["info"] = $info;
	    if($info["status"] != 1){
	        alert("优惠券状态异常，无法发放", U("Coupon/listad", $this->getMap()));
	    }
	
	    if($doinfo == "modify"){
	        $userid = I("post.userid", 0);
	
	        $user_coupon_model = D("user_coupon");
			//发放全部用户
			$user_model = D("user");
			$userids = explode(',',$userid);
			if (empty($userids)) {
				alert_back("无用户可发放");
			}
			if ($info["count"] < count($userids)) {
				alert_back("优惠券数量少于用户数，发放失败");
			}
			$use_date = $info["use_date"];
			$time = time();
			$Suc = array();
			foreach ($userids as $key => $val) {
				$ds = array();
				$ds["couponid"] = $couponid;
				$ds["type"] = $info["type"];
				$ds["code"] = $info["code"];
				$ds["title"] = $info["title"];

				$ds["use_start_date"] = date("Y-m-d", $time);
				$ds["use_end_date"] = date("Y-m-d", strtotime("+".$use_date." day", $time));
				$ds['coupon_type']=$info['coupon_type'];
				if($info['coupon_type']==0){
					$ds["money"] = $info["money"];
					$ds["min_amount"] = $info["min_amount"];
				}elseif($info['coupon_type']==1){
					$ds["product_id"] = $info["product_id"];
				}elseif($info['coupon_type']==2){
					$ds["service_id"] = $info["service_id"];
				}elseif($info['coupon_type']==3){
					$ds["org_id"] = $info["org_id"];
				}
			   
				
				$ds["status"] = 0;
				$ds["use_type"] = 0;
				$ds["userid"] = $val;
				$entity = array(
				    "hybrid"=>'client', "status"=>0, "sendid"=>0, "sender"=>"系统消息", "userid"=>$val,
				    "title"=>'优惠券', "content"=>'您好，您有一张新的'.$info["title"].',价值'.$info["money"].'元，有效期至'.$ds['use_end_date'], "param"=>null,
				    "createdate"=>date("Y-m-d H:i:s"), "type"=>0, "systemid"=>0
				);
				D("user_message")->add($entity);
				$ds["createdate"] = date("Y-m-d H:i");

				$Suc[] = $user_coupon_model->add($ds);

			}
			$entity = array("count"=>($info["count"]-count($Suc)), "sales"=>($info["sales"]+count($Suc)));
			$model->where("id=".$couponid)->save($entity);
		
	
	        alert("优惠券发放成功", U("Coupon/listad", $this->getMap()));
	    }
		
		switch($data['info']['coupon_type']){
			case 1:
				$product=D('product')->field('title')->find($data['info']['product_id']);
				$data['info']['product_title']=$product['title'];
				break;
			case 2:
				$service=D('service_project')->field('title')->find($data['info']['service_id']);
				$data['info']['product_title']=$service['title'];
				break;
			case 3:
				$org=D('org')->field('title')->find($data['info']['org_id']);
				$data['info']['product_title']=$org['title'];
				break;
		}
		
	    $user_model = D("user_class");
	    $user_class = $user_model->select();
	    $this->assign("user_class", $user_class);
		
	    $this->assign($data);
	
	    $map = $this->getMap();
	    $map["couponid"] = $couponid;
	    $this->assign("map", $map);
	
		$this->show();
	}
    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("coupon");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("Coupon/listad", $this->getMap());
    }

    public function getMap(){
        $type = I("get.type");
        $map = array("type"=>$type);
        return $map;
    }
}