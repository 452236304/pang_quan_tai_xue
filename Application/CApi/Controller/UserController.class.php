<?php
namespace CApi\Controller;
use Think\Controller;
class UserController extends BaseLoggedController {
	//用户信息
	public function userinfo(){
		$user = $this->AuthUserInfo;
		if(D('PointLog','Service')->check_tag($user['id'],'sign_in')){
			$user['is_sign']=0;
		}else{
			$user['is_sign']=1;
		}
		$user["avatar"] = $this->DoUrlHandle($user["avatar"]);
		
		//优惠卷
		$model = D("user_coupon");
		$time = date("Y-m-d");
		$map = array("userid"=>$user["id"], "status"=>0, "use_end_date"=>array("egt", $time));
		$coupon = $model->where($map)->count();
		$user['coupon']=$coupon;
		
		//照护人列表
		$model = D("user_care");
		$order = 'top DESC';
		$map = array("userid"=>$user["id"]);
		$list = $model->where($map)->order($order)->select();
		foreach($list as $k=>$v){
			$v['avatar']=$this->DoUrlHandle($v['avatar']);
			$v["age"] = getAgeMonth($v["birth"]);
			$health=json_decode($v['health'],true);
			$v['action']=$health['action'];
			$v['eat']=$health['eat'];
			$v['clothes']=$health['clothes'];
			$v['wash']=$health['wash'];
			$v['shit']=$health['shit'];
			$v['urine']=$health['urine'];
			$v['urine_piping']=$health['urine_piping'];
			$v['stomach_piping']=$health['stomach_piping'];
			$v['fistula_tube']=$health['fistula_tube'];
			$v['pressure']=$health['pressure'];
			$v['tracheotomy']=$health['tracheotomy'];
			$v['realize']=$health['realize'];
			$v['dementia']=$health['dementia'];
			$v['deathbed']=$health['deathbed'];

			$v["assess_care"] = $this->user_care_assess($v["id"]);

			$list[$k]=$v;
		}
		$user['care']=$list;
		$user['level_name']='普通会员';

		// 分销体系
        if( $user['is_team'] ){
            $user['team_level_name'] = '企业合伙人';
        }else{
            $user['team_level_name'] = '';
        }

        // 格言警句
		$today=date('Y-m-d');
		$map = array('startdate'=>array('elt',$today),'enddate'=>array('egt',$today));
		$data = D('Aphorism')->where($map)->order('sort asc')->find();
		if(!$data){
			$data = D('Aphorism')->order('sort asc')->find();
		}
        
        //$number = date('z');
        //$key = $number % count($data);
		if($data['author']){
			$user['aphorism'] = $data['content'] . '——' . $data['author'];
		}else{
			$user['aphorism'] = $data['content'];
		}
        
		
		//购物车数量
		$shopping_cart_model = D("shopping_cart");
		
		$map = array("userid"=>$user["id"]);
		$shopping_cart_count = $shopping_cart_model->alias("sc")->join("left join sj_product as p on sc.productid=p.id")->where($map)->count();
		
		$user["shopping_cart_count"] = $shopping_cart_count;
		
		return $user;
	}

	//我的照护人
	public function usercare(){
		$user = $this->AuthUserInfo;
		
		$model = D("user_care");
        $order = 'top DESC';
		$map = array("userid"=>$user["id"]);
		$id=I('get.id');
		if($id){
			$map['id']=$id;
		}
		$list = $model->where($map)->order($order)->select();
		foreach($list as $k=>$v){
			//计算年龄
			$v["age"] = getAgeMonth($v["birth"]);
			$v['avatar']=$this->DoUrlHandle($v['avatar']);
			$health=json_decode($v['health'],true);
			$v['action']=$health['action'];
			$v['eat']=$health['eat'];
			$v['clothes']=$health['clothes'];
			$v['wash']=$health['wash'];
			$v['shit']=$health['shit'];
			$v['urine']=$health['urine'];
			$v['urine_piping']=$health['urine_piping'];
			$v['stomach_piping']=$health['stomach_piping'];
			$v['fistula_tube']=$health['fistula_tube'];
			$v['pressure']=$health['pressure'];
			$v['tracheotomy']=$health['tracheotomy'];
			$v['realize']=$health['realize'];
			$v['dementia']=$health['dementia'];
			$v['deathbed']=$health['deathbed'];

			$v["assess_care"] = $this->user_care_assess($v["id"]);
			if($v['assess_care']['orderid']==0){
				$v['assess_care']['assess_care_level'] = intval($v['assess_care']['assess_care_level']);
				unset($v['assess_care']['orderid']);
			}
			$list[$k]=$v;
		}
		return $list;
	}

	//照护人专业评估
	private function user_care_assess($careid){
		$data = array(
			"orderid"=>0, "assess_care_level"=>0
		);

		$recordmodel = D("service_order_assess_record");

		$map = array("careid"=>$careid, "assess_care_level"=>array("gt", 0));
		$record = $recordmodel->where($map)->order("updatetime desc")->find();
		if(empty($record)){
			return $data;
		}

		$data["orderid"] = $record["orderid"];
		$data["assess_care_level"] = $record["assess_care_level"];

		return $data;
	}

	//我的优惠券
	public function coupon(){
		$user = $this->AuthUserInfo;

        $model = D("user_coupon");

		$time = date("Y-m-d");
		//未使用优惠券
		$map = array("userid"=>$user["id"], "status"=>0, "use_end_date"=>array("egt", $time));
		$valid_coupon = $model->where($map)->order('createdate desc')->select();
        foreach ($valid_coupon as $key => $val) {
            $valid_coupon[$key]['money'] = intval($val['money']);
            $valid_coupon[$key]['min_amount'] = intval($val['min_amount']);

            $valid_coupon[$key]['use_start_date'] = date("Y-m-d", strtotime($val["use_start_date"]));
            $valid_coupon[$key]['use_end_date'] = date("Y-m-d", strtotime($val["use_end_date"]));
			if(strtotime($val["use_end_date"]) < time()-259200){
				$valid_coupon[$key]['will_expire']=1;
			}else{
				$valid_coupon[$key]['will_expire']=0;
			}
            if($val["used_date"]){
                $valid_coupon[$key]['used_date'] = date("Y-m-d", strtotime($val["used_date"]));
            }

        }

        //已使用优惠券
        $map = array("userid"=>$user["id"], "status"=>array("neq", 0));
        $used_coupon = $model->where($map)->order('createdate desc')->select();
        foreach($used_coupon as $key => $val){
            $used_coupon[$key]['money'] = intval($val['money']);
            $used_coupon[$key]['min_amount'] = intval($val['min_amount']);

            $used_coupon[$key]['use_start_date'] = date("Y-m-d", strtotime($val["use_start_date"]));
            $used_coupon[$key]['use_end_date'] = date("Y-m-d", strtotime($val["use_end_date"]));
            if($val["used_date"]){
                $used_coupon[$key]['used_date'] = date("Y-m-d", strtotime($val["used_date"]));
            }
        }

		//已失效优惠券
		$map = array("userid"=>$user["id"], "status"=>0,"use_end_date"=>array("lt", $time));
		$invalid_coupon = $model->where($map)->order('createdate desc')->select();
        foreach ($invalid_coupon as $key => $val) {
            $invalid_coupon[$key]['money'] = intval($val['money']);
            $invalid_coupon[$key]['min_amount'] = intval($val['min_amount']);
            $invalid_coupon[$key]['use_start_date'] = date("Y-m-d", strtotime($val["use_start_date"]));
            $invalid_coupon[$key]['use_end_date'] = date("Y-m-d", strtotime($val["use_end_date"]));
            if($val["used_date"]){
                $invalid_coupon[$key]['used_date'] = date("Y-m-d", strtotime($val["used_date"]));
            }
        }
		
		//待领取优惠券
		$map = array("type"=>4, "status"=>1, "count"=>array("gt", 0));
		$unclaimed_coupon=D('coupon')->where($map)->field('id,title,money')->select();
		foreach($unclaimed_coupon as $k=>$v){
			$map=array("couponid"=>$v['id'],'userid'=>$user['id']);
			//该用户领取该优惠券的数量
			$v['accept']=D('user_coupon')->where($map)->count();
			if($v['accept'] > 0){
				unset($unclaimed_coupon[$k]);
			}
		}
		
		$data = array("valid_coupon"=>$valid_coupon, "used_coupon"=>$used_coupon, "invalid_coupon"=>$invalid_coupon,'unclaimed_coupon'=>$unclaimed_coupon);
		
		return $data;
	}
	
	//领取优惠券
	public function getCoupon(){
		$this->UserAuthCheck();
		$user = $this->AuthUserInfo;
		$id=I('post.id');
		$this->GrantUserCoupon($user,4,$id);
		return ;
	}
	
	//发放优惠券
	public function GrantUserCoupon($user,$type,$id){
	    if(empty($user)){
	        return;
	    }
	
	    $map = array("type"=>$type, "status"=>1, "count"=>array("gt", 0));
	    $register_coupon = D("coupon")->where($map)->select();
	    if(count($register_coupon) <= 0){
	        return;
	    }
	
	    //发放优惠券
	    $time = time();
	    foreach($register_coupon as $k=>$v){
	        $use_date = $v["use_date"];
	        $entity = array(
	            "couponid"=>$v["id"], "type"=>$v["type"], "code"=>$v["code"], "title"=>$v["title"],
	            "use_start_date"=>date("Y-m-d H:i:s", $time), "use_end_date"=>date("Y-m-d H:i:s", strtotime("+".$use_date." day", $time)),
	            "coupon_type"=>$v["coupon_type"], "status"=>0, "use_type"=>0,
	            "userid"=>$user["id"], "createdate"=>date("Y-m-d H:i:s", $time)
	        );
	    	switch($v['coupon_type']){
	    		case 0:
	    			$entity['money']=$v['money'];
	    			$entity['min_amount']=$v['min_amount'];
	    			break;
	    		case 1:
	    			$entity['product_id']=$v['product_id'];
	    			break;
	    		case 2:
	    			$entity['service_id']=$v['service_id'];
	    			break;
	    		case 3:
	    			$entity['org_id']=$v['org_id'];
	    			break;
	    	}
	        D("user_coupon")->add($entity);
	        //更新优惠券余量
	        $r_entity = array("count"=>($v["count"]-1), "sales"=>($v["sales"]+1));
	        D("coupon")->where("id=".$v["id"])->save($r_entity);
	    }
	
	}
	
	//我的邀请好友
	public function my_friends(){
		$user = $this->AuthUserInfo;
		$map['be_referral_code']=$user['referral_code'];
		$map['status']=200;
		$list=D('user')->field('id,nickname,avatar,registertime')->where($map)->order('registertime desc')->select();
		$count=D('user')->where($map)->count();
		foreach($list as $k=>$v){
			$v['avatar']=$this->DoUrlHandle($v['avatar']);
			$list[$k]=$v;
		}
		return array('count'=>$count,'list'=>$list);
	}
	
	//我的收货地址
	public function address(){
		$user = $this->AuthUserInfo;

		$model = D("user_address");

		$map = array("type"=>0, "userid"=>$user["id"]);
		$list = $model->where($map)->select();

		return $list;
	}

	//我的服务地址
	public function addressservice(){
		$user = $this->AuthUserInfo;

		$model = D("user_address");

		$map = array("type"=>1, "userid"=>$user["id"]);
		$list = $model->where($map)->select();

		return $list;
	}

	//我的收藏
	public function collection(){
		$user = $this->AuthUserInfo;

		$model = D("user_record");

		//收藏类型(1=资讯，2=商品，3=服务人员 4=服务项目 5=机构 6=店铺)
        $source = I("get.source", 0);
        if(!in_array($source, [1,2,3,4,5,6])){
            E("请选择要查看的收藏类型");
        }

		$map = array("userid"=>$user["id"], "type"=>1, "source"=>$source);
		$page = I("get.page", 1);
        $row = I("get.row", 10);
		$begin = ($page-1)*$row;
		
		$order = "r.createdate desc";
        $count = $model->where($map)->count();
		$totalpage = ceil($count/$row);

        $map = array("r.userid"=>$user["id"], "r.type"=>1, "r.source"=>$source);
		switch ($source) {
			case 1: //资讯
				$map=array('r.userid'=>$user['id'],'r.type'=>2);
				$model = D("user_record")->alias("r")->join("left join sj_mould_article as i on r.articleid=i.id")->field("i.*");
				break;
			case 2: //商品
				$map['pa.status']=1;
				$model = D("user_record")->alias("r")->join("left join sj_product as p on r.objectid=p.id")->field("p.*")->join('LEFT JOIN sj_product_attribute pa on p.id=pa.productid')->group('p.id');
				break;
			case 3: //服务人员
				break;
			case 4: //服务项目
				$map['pa.status']=1;
				$model = D("user_record")->alias("r")->join("left join sj_service_project as p on r.objectid=p.id")->field("p.*")->join('LEFT JOIN sj_service_project_level_price pa on p.id=pa.projectid')->group('p.id');
				break;
			case 5: //机构
				$map['p.status']=1;
				$model = D("user_record")->alias("r")->join("left join sj_pension as p on r.objectid=p.id")->field("p.*")->group('p.id');
				break;
			case 6: //店铺
				$map['p.status']=1;
				$model = D("user_record")->alias("r")->join("left join sj_business as p on r.objectid=p.id")->field("p.*")->group('p.id');
				break;
		}
        if ($source == 3) {
            $map['ur.role'] = array('neq',1);
            $list = D("user_record")->alias("r")->join("left join sj_user as u on r.objectid=u.id")->join("left join sj_user_role as ur on ur.userid=u.id")->join("left join sj_user_profile as up on r.objectid=up.userid")->field("up.*,u.avatar,ur.role")->where($map)->order($order)->limit($begin, $row)->group('u.id')->select();
        }else{
            $list = $model->where($map)->order($order)->limit($begin, $row)->select();
        }

		$this->SetPaginationHeader($totalpage, $count, $page, $row);
		
		foreach($list as $k=>$v){
			switch ($source) {
				case 1: //资讯
					$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
					$v["source_logo"] = $this->DoUrlHandle($v["source_logo"]);
					break;
				case 2: //商品
					$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
					break;
				case 3: //服务人员
					$v["avatar"] = $this->DoUrlHandle($v["avatar"]);
					switch($v['gender']){
						case 1:
							$v['gender']='男';
							break;
						case 2:
							$v['gender']='女';
							break;
						case 0:
							$v['gender']='保密';
							break;
					}
					$v["age"] = getAgeMonth($v["birth"]);
					break;
				case 4: //服务项目
					$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
					break;
				case 5: //服务项目
					$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
					break;
				case 6: //店铺
					$v["thumb"] = $this->DoUrlHandle($v["thumb"]);
					$v['subtitle'] = $v['province'].$v['city'].$v['region'].$v['address'];
					break;
			}
			$list[$k] = $v;
		}
		
		return $list;
	}

	//我的消息
	public function message(){
		$user = $this->AuthUserInfo;

		$model = D("user_message");

		//消息类型（0=系统，1=订单）
		$type = I("get.type", 0);

		$map = array("hybrid"=>"client", "userid"=>$user["id"], "type"=>$type);
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

		$map = array("hybrid"=>"client", "status"=>0, "userid"=>$user["id"]);
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

    //设置消息已读
    public function messagestatus(){
        $user = $this->AuthUserInfo;

        $model = D("user_message");

        $id = I("post.id", 0);

        $map = array("hybrid"=>"client", "userid"=>$user["id"], "id"=>$id);

        $model->where($map)->save(array("status"=>1));

        return ;
    }

    //我的订单评价列表
    public function ordercomment(){
        $user = $this->AuthUserInfo;

        //类型：0=待评价，1=已评价
        $type = I("get.type", 0);

        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;

        $count = 0;
        $data = array();

        /*机构订单评价列表*/
        $orgModel = D("org_order");

        $map = array("userid"=>$user["id"]);
        if($type == 0){
            $map["commentid"] = 0;
        } else{
            $map["commentid"] = array("gt", 0);
        }
        //短住类型才有评论
        $map["type"] = 3;
        $map["status"] = 4;
        $map["pay_status"] = 3;
        $order = "createdate desc";
        $count += $orgModel->where($map)->count();
        $list = $orgModel->where($map)->order($order)->limit($begin, $row)->select();



        foreach($list as $k=>$v){
            $list[$k]["thumb"] = $this->DoUrlHandle($v["thumb"]);
            $list[$k]["order_list_type"] = 1;
        }

        $data[] = $list;

        /*商品订单评价列表*/
        $productModel = D("product_order_product");

        $map = array("pop.userid"=>$user["id"]);
        if($type == 0){
            $map["pop.commentid"] = array("elt", 0);
        } else{
            $map["pop.commentid"] = array("gt", 0);
        }
        $map["po.status"] = 4;
        $map["po.pay_status"] = 3;
        $order = "createdate desc";
        $count += $productModel->alias("pop")->join("left join sj_product_order as po on pop.orderid=po.id")->where($map)->count();
        $list = $productModel->alias("pop")->join("left join sj_product_order as po on pop.orderid=po.id")
            ->field("po.sn,po.createdate,pop.*")->where($map)->order($order)->limit($begin, $row)->select();
        foreach($list as $k=>$v){
            $list[$k]["thumb"] = $this->DoUrlHandle($v["thumb"]);
            $list[$k]["amount"] = floatval($v["price"]) * floatval($v["quantity"]);
            $list[$k]["order_list_type"] = 2;
        }

        $data[] = $list;

        /*服务订单评价列表*/
        $serviceModel = D("service_order");

        $map = array("userid"=>$user["id"]);
        if($type == 0){
            $map["commentid"] = 0;
        } else{
            $map["commentid"] = array("gt", 0);
        }
        $map["status"] = 4;
        $map["pay_status"] = 3;
        $order = "createdate desc";
        $count += $serviceModel->where($map)->count();
        $list = $serviceModel->where($map)->order($order)->limit($begin, $row)->select();

        foreach($list as $k=>$v){
            $list[$k]["thumb"] = $this->DoUrlHandle($v["thumb"]);
            $list[$k]["service_avatar"] = $this->DoUrlHandle($v["service_avatar"]);
            $list[$k]["order_list_type"] = 3;
        }

        $data[] = $list;

        $totalpage = ceil($count/$row);

        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        $ret = array();
        foreach ($data as $key => $val) {
            foreach ($val as $k => $v) {
                $ret[] = $v;
            }
        }

        return $ret;
    }
	//保存兴趣标签
	public function saveinterest(){
		$user = $this->AuthUserInfo;
		$data=I('post.');
		$interest=explode(',',$data['interest']);
		$interest_list=D('interest')->field('title')->where(array('id'=>array('in',$interest)))->select();
		$interestArr=array();
		foreach($interest_list as $k=>$v){
			$interestArr[]=$v['title'];
		}
		$interest=implode(',',$interestArr);
		D('user')->where(array('id'=>$user['id']))->save(array('interest'=>$interest));
		$arr['updatetime']=date('Y-m-d H:i:s');
		$arr['success']=1;
		return $arr;
	}
	//获取小程序好友分享 小程序码
	public function xcx_invitation(){
		$user = $this->AuthUserInfo; //$user['referral_code'];
		$ext=['page'=>'pages/unLogin/unLogin','scene'=>$user['referral_code']];
		//$ext=['scene'=>123];
		$code=D('XcxHandle')->GetXcxCode($ext);
		$code = $this->DoUrlHandle($code);
		return array('link'=>$code);
	}
}