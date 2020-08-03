<?php
namespace CApi\Controller;
use Think\Controller;
class LoginController extends BaseController {
	//手机号验证
	public function check_mobile(){
		$data=I('post.');
		$mobile = trim($data["mobile"]);
		$action = trim($data["action"]);
		if(empty($mobile)){
			E("手机号码不能为空");
		}
		if(!isMobile($mobile)){
			E("手机号码格式不正确");
		}
		$model = D("user");
		$map = array("account"=>$mobile, "mobile"=>$mobile, "_logic"=>"or");
		$checkuser = $model->where($map)->find();
		if($action=='register'){
			if($checkuser){
				E('该手机号已注册');
			}
		}else{
			if(!$checkuser){
				E('该手机号未注册',44);
			}
		}
		$arr['success']=1;
		return $arr;
    }
    
	//验证码验证
	public function check_code(){
		$data=I('post.');
		$mobile = trim($data["mobile"]);
		$action = trim($data["action"]);
		$code = trim($data["code"]);
		//检查验证码
		$this->CheckSmsCode($mobile,$action, $code);
		$arr['success']=1;
		return $arr;
    }
    
	//用户登录
	public function login(){
		$data = I("post.");

        $account = trim($data["mobile"]);
        $logintype =  $data["logintype"]; //登录方式 0=密码登录，1=验证码登录
        $code = trim($data["code"]);
		$password = trim($data["password"]);
		
        if(empty($account)){
			E("手机号码不能为空");
        }
        if(!isMobile($account)){
			E("手机号码格式不正确");
        }
        if($logintype == 1){
            if(empty($code)){
                E("验证码不能为空");
            }

            //检查验证码
            $this->CheckSmsCode($account, "login", $code);
        }else{
            if(empty($password)){
                E("密码不能为空");
            }

            $password = md5(strtolower($password) . C("pwd_key"));
        }
        
        $user = $this->UserCheckLogin($account, $password, true, true, $logintype);
		$map = array('id'=>$user['id'],'password'=>md5(strtolower('ydchun') . C("pwd_key")));
		$default_pass=D('user')->where($map)->find();
		if($default_pass){
			$user['default_pass']=1;
		}else{
			$user['default_pass']=0;
		}
		
        return $user;
	}

	//用户注册
    public function register(){
        $data = I("post.");

        $account = $data["mobile"];
        $code = $data["code"];
        $password = $data["password"];
        $be_referral_code = $data["be_referral_code"];
		
		$openid = $data["openid"];
		$hybrid = $data["hybrid"];

        if(empty($account)){
            E("手机号码不能为空");
        }
        if(!isMobile($account)){
            E("手机号码格式不正确");
        }
		
		if(!$hybrid || !$openid){
			//检查验证码
			$this->CheckSmsCode($account, "register", $code);
		}
		
        if(empty($password)){
            E("密码不能为空");
        }
        $password = md5(strtolower($password) . C("pwd_key"));

        $model = D("user");

        $map = array("account"=>$account, "mobile"=>$account, "_logic"=>"or");
        $checkuser = $model->where($map)->find();
        if($checkuser){
            E("手机号码已经被注册");
        }

        //检查推荐人手机号码或邀请码
        $team_path = '';
        $is_team = $team_parent = 0;
        if($be_referral_code){
            $map = array("referral_code"=>$be_referral_code, "mobile"=>$referral_code, "_logic"=>"OR");
            $be_referral_user = $model->where($map)->find();
            if($be_referral_user){
                $be_referral_code = $be_referral_user["referral_code"];
                // wjk  分销用户路径
                if( $be_referral_user['team_path'] ){
                    $team_path = $be_referral_user['team_path'] . $be_referral_user['id'] . '-';
                }else{
                    $team_path = '-' . $be_referral_user['id'] . '-';
                }
                $is_team = 1;
                $team_parent = $be_referral_user['id'];
                D('user')->save(['id' => $be_referral_user['id'], 'is_team' => 1]);
                D('user')->where(['id' => $be_referral_user['id']])->setInc('team_children_num');
				//邀请好友后发放优惠券
				$this->GrantUserCoupon($be_referral_user,1);//邀请好友的用户的优惠券
				//发放积分奖励
				$check = D('PointLog','Service')->check_tag($be_referral_user['id'],'first_invite');
				if($check){
					D('Point','Service')->append($be_referral_user['id'],'first_invite');
				}else{
					D('Point','Service')->append($be_referral_user['id'],'invite');
				}
            } else{
                E("填写的邀请码不存在");
            }
        }

        $referral_code = random(10, "all");
        //邀请码
        while(true){
            $check = $model->where(array('referral_code'=>$referral_code))->find();
            if(empty($check)){
                $referral_code = $referral_code; break;
            }
            $referral_code = random(10, "all");
        }

        $user = array(
            "account"=>$account, "password"=>$password, "status"=>200,
            "nickname"=>func_substr_replace($account, 3, 4), "mobile"=>$account,
            "avatar"=>"/upload/default/default_avatar.png",
            "registertime"=>date("Y-m-d H:i"), "updatetime"=>date("Y-m-d H:i"),
            "logintime"=>date("Y-m-d H:i"), "login_count"=>1, "user_money" =>0,
            "referral_code"=>$referral_code, "be_referral_code"=>$be_referral_code,
            'team_path' => $team_path, 'is_team' => $is_team, 'team_parent' => $team_parent
        );

        $userid = $model->add($user);
		if($be_referral_code){
			$user['id']=$userid;
			$this->GrantUserCoupon($user,1);//注册用户的优惠券
		}
        //绑定用户角色
        $this->BindUserRole($userid);

		$user = $model->find($userid);

        //用户注册后发放优惠券
        $this->GrantUserCoupon($user,2);

        //设置用户加密信息返回
        $this->SetUserHeader($userid, $password);
		
		//绑定用户第三方信息
		if($hybrid && $openid){
			switch ($hybrid){
			    case "app":
			        $type = 1;
			        break;
			    case "xcx":
			        $type = 2;
			        break;
			    case "qq":
			        $type = 3;
			        break;
			}
			
			//删除已存在的授权码
			$map = array("code"=>$openid, "type"=>$type);
			D("user_bind")->where($map)->delete();
			
			$entity = array(
			    "status"=>1, "type"=>$type, "code"=>$openid,
			    "remark"=>$remark, "createdate"=>date("Y-m-d H:i:s"),
				"userid"=>$userid
			);
			
			//保存绑定信息
			$map = array("userid"=>$userid, "type"=>$type);
			$check = D("user_bind")->where($map)->find();
			if($check){
				D("user_bind")->where($map)->save($entity);
			} else{
				D("user_bind")->add($entity);
			}
		}
		
		//完成注册并绑定手机号积分任务
		D('Point','Service')->append($user['id'],'regiest');
		
		return $this->UserHandle($user);
	}
	
	//发放优惠券
    private function GrantUserCoupon($user,$type){
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
	
	//忘记密码
    public function forget(){
        $data = I("post.");

        $account = $data["mobile"];
        $code = $data["code"];
        $password = $data["password"];
        $confirm_password = $data["confirm_password"];

        if(empty($account)){
            E("手机号码不能为空");
        }
        if(!isMobile($account)){
            E("手机号码格式不正确");
        }
        //检查验证码
		$this->CheckSmsCode($account, "forget", $code);
		
        if(empty($password)){
            E("密码不能为空");
        }
        if($password != $confirm_password){
            E("密码与确认密码不一致");
        }
        $password = md5(strtolower($password) . C("pwd_key"));

        $model = D("user");

        $map = array("account"=>$account, "mobile"=>$account, "_logic"=>"or");
        $user = $model->where($map)->find();
        if(empty($user)){
            E("手机号码还未注册");
        }

        $entity = array(
            "id"=>$user["id"], "password"=>$password,
            "updatetime"=>date("Y-m-d H:i:s")
        );
        $model->where("id=".$user["id"])->save($entity);

        //设置用户加密信息返回
        $this->SetUserHeader($user["id"], $password);

        return;
    }

    //第三方登录
    public function oauthlogin(){
        $data = I("post.");
        //第三方授权码openid
        $openid = $data["openid"];
        if(empty($openid)){
            E("授权登录码不能为空");
        }
        $hybrid = $data["hybrid"];
        if(empty($hybrid)){
            E("请选择授权方");
        }

        switch ($hybrid){
            case "app":
                $type = 1;
                break;
            case "xcx":
                $type = 2;
                break;
            case "qq":
                $type = 3;
                break;
			case "ios":
			    $type = 5;
			    break;
            default:
                E('目前不支持此登录方式');
        }

        $map = array("ub.code"=>$openid,'ub.type'=>$type);
        $user = D("user_bind")->alias("ub")->join("left join sj_user as u on ub.userid=u.id")
                ->field("u.*")->where($map)->find();
        if (empty($user["account"])) {
            E('未绑定账号', 1);
        }

        $user=$this->UserCheckLogin($user["account"], $user["password"], true, true);
		$map = array('id'=>$user['id'],'password'=>md5(strtolower('ydchun') . C("pwd_key")));
		$default_pass=D('user')->where($map)->find();
		if($default_pass){
			$user['default_pass']=1;
		}else{
			$user['default_pass']=0;
		}
		return $user;
    }

    //第三方授权绑定
    public function oauthbind(){
        $data = I("post.");

        $openid = $data["openid"];
        if(empty($openid)){
            E("授权登录码不能为空");
        }
        $mobile = $data["mobile"];
        if(empty($mobile)){
            E("绑定的手机号码不能为空");
        }
        $code = $data["code"];
        if(empty($code)){
            E("验证码不能为空");
        }
        //检查验证码
        $this->CheckSmsCode($mobile, "loginbing", $code);

        $model = D("user");

        $hybrid = $data["hybrid"];
        if(empty($hybrid)){
            E("请选择授权方");
        }

        switch ($hybrid){
            case "app":
                $remark = "微信APP绑定";
                $type = 1;
                break;
            case "xcx":
                $remark = "微信小程序绑定";
                $type = 2;
                break;
            case "qq":
                $remark = "QQ绑定";
                $type = 3;
                break;
			case "ios":
			    $remark = "apple绑定";
			    $type = 5;
			    break;
            default:
                E('目前不支持此登录方式');
        }

        //删除已存在的授权码
        $map = array("code"=>$openid, "type"=>$type);
        D("user_bind")->where($map)->delete();

        $map = array("account"=>$mobile, "mobile"=>$mobile, "_logic"=>"or");
        $user = $model->where($map)->find();

        $entity = array(
            "status"=>1, "type"=>$type, "code"=>$openid,
            "remark"=>$remark, "createdate"=>date("Y-m-d H:i:s")
        );

        if($user){
            //保存绑定信息
            $entity["userid"] = $user["id"];
            $map = array("userid"=>$user["id"], "type"=>$type);
            $check = D("user_bind")->where($map)->find();
            if($check){
                D("user_bind")->where($map)->save($entity);
            } else{
                D("user_bind")->add($entity);
            }
        
            return $this->UserCheckLogin($user["account"], $user["password"], true, true);
        }else{
			E('该手机号未注册',30);
		}
		/* 
        $referral_code = random(10, "all");
        //邀请码
        while(true){
            $check = $model->where(array('referral_code'=>$referral_code))->find();
            if(empty($check)){
                $referral_code = $referral_code; break;
            }
            $referral_code = random(10, "all");
        }
        
        $password = md5(strtolower("bc".$mobile) . C("pwd_key"));
        $user = array(
            "account"=>$mobile, "password"=>$password, "status"=>200,
            "nickname"=>func_substr_replace($mobile, 3, 4), "mobile"=>$mobile,
            "avatar"=>"/upload/default/default_avatar.png",
            "registertime"=>date("Y-m-d H:i"), "updatetime"=>date("Y-m-d H:i"),
            "logintime"=>date("Y-m-d H:i"), "login_count"=>1, "user_money" =>0,
            "referral_code"=>$referral_code
        );

        $userid = $model->add($user);

        //保存绑定信息
        $entity["userid"] = $userid;
        $map = array("userid"=>$user["id"], "type"=>$type);
        $check = D("user_bind")->where($map)->find();
        if($check){
            D("user_bind")->where($map)->save($entity);
        } else{
            D("user_bind")->add($entity);
        }

		$user = $model->find($userid);

        //用户注册后发放优惠券
        $this->GrantUserCoupon($user);
		
        return $this->UserCheckLogin($user["account"], $user["password"], true, true); */
    }

    //小程序登录
    public function oauthxcxlogin(){
        $code = I("post.code");
		if(empty($code)){
			E("微信code参数不能为空");
        }
        
        require_once "Application/Payment/Weixin/Extend/WxPay.Config.php";
        $config = new \WxPayConfig();
        $config->hybrid = "xcx";
		
		$url = "https://api.weixin.qq.com/sns/jscode2session?appid=".$config->GetAppId()."&secret=".$config->GetAppSecret()."&js_code=".$code."&grant_type=authorization_code";

		$result = http_request($url);

		$data = json_decode($result, true);
		
		if($data["errcode"]){
			E("获取微信授权码失败，请稍后尝试");
		}
		
		return array("openid"=>$data["openid"], "session_key"=>$data["session_key"]);
    }

    //获取小程序用户信息
    public function oauthxcxuserinfo(){
        $session_key = I("post.session_key");
		if(empty($session_key)){
			E("微信session_key参数不能为空");
		}
		$encryptedData = I("post.encryptedData");
		if(empty($encryptedData)){
			E("微信encryptedData参数不能为空");
		}
		$iv = I("post.iv");
		if(empty($iv)){
			E("微信iv参数不能为空");
		}
        
        require_once "Application/Common/Common/wxBizDataCrypt.php";
        require_once "Application/Payment/Weixin/Extend/WxPay.Config.php";
        $config = new \WxPayConfig();
        $config->hybrid = "xcx";

		$wxBizDataCrypt = new \WXBizDataCrypt($config->GetAppId(), $session_key);
		$errCode = $wxBizDataCrypt->decryptData($encryptedData, $iv, $wxuser);

		if($errCode == 0){
			$wxuser = json_decode($wxuser, true);
			//检查用户是否注册
			$map = array();
			$is_register=D('user')->where($map)->find();
			if($is_register){
				$wxuser['is_register']=1;
			}else{
				$wxuser['is_register']=0;
			}
			return $wxuser;
		}
		F('error',$errCode);
		E("获取微信用户信息失败，请重新尝试");
    }
	
	//获取兴趣标签接口
	public function interest(){
		$interest_list=D('interest')->where(array('status'=>1))->select();
		$list=array();
		foreach($interest_list as $key=>$value){
			$arr['id']=$value['id'];
			$arr['title']=$value['title'];
			$list[$value['category']][]=$arr;
		}
		return $list;
	}
	
	//小程序手机号登录接口
	public function xcxmobilelogin(){
		$data = I("post.");
		
		$model = D('user');
		$be_referral_code = $data["be_referral_code"];
		$account = trim($data["mobile"]);
		
		if(empty($account)){
			E("手机号码不能为空");
		}
		if(!isMobile($account)){
			E("手机号码格式不正确");
		}
		$map = array('account'=>$account, "mobile"=>$account, "_logic"=>"or");
		$user_info=D('user')->where($map)->find();
		if($user_info){
			//账号已注册
			$user = $this->UserCheckLogin($account,'', true, true, 1);
		}else{
			//账号未注册
			$province = $data['province']?:'广东省';
			$city = $data['city']?:'广州市';
			$region = $data['region']?:'番禺区';
			
			$openid = $data["openid"];
			$hybrid = 'xcx';
			$password = 'ydchun';
			if(empty($account)){
			    E("手机号码不能为空");
			}
			if(!isMobile($account)){
			    E("手机号码格式不正确");
			}
			
			$password = md5(strtolower($password) . C("pwd_key"));
			
			//检查推荐人手机号码或邀请码
			$team_path = '';
			$is_team = $team_parent = 0;
			if($be_referral_code){
			    $map = array("referral_code"=>$be_referral_code, "mobile"=>$referral_code, "_logic"=>"OR");
			    $be_referral_user = $model->where($map)->find();
			    if($be_referral_user){
			        $be_referral_code = $be_referral_user["referral_code"];
			        // wjk  分销用户路径
			        if( $be_referral_user['team_path'] ){
			            $team_path = $be_referral_user['team_path'] . $be_referral_user['id'] . '-';
			        }else{
			            $team_path = '-' . $be_referral_user['id'] . '-';
			        }
			        $is_team = 1;
			        $team_parent = $be_referral_user['id'];
			        D('user')->save(['id' => $be_referral_user['id'], 'is_team' => 1]);
			        D('user')->where(['id' => $be_referral_user['id']])->setInc('team_children_num');
					//邀请好友后发放优惠券
					$this->GrantUserCoupon($be_referral_user,1);//邀请好友的用户的优惠券
			    } else{
			        E("填写的邀请码不存在");
			    }
			}
			
			$referral_code = random(10, "all");
			//邀请码
			while(true){
			    $check = $model->where(array('referral_code'=>$referral_code))->find();
			    if(empty($check)){
			        $referral_code = $referral_code; break;
			    }
			    $referral_code = random(10, "all");
			}
			
			$user = array(
			    "account"=>$account, "password"=>$password, "status"=>200,
			    "nickname"=>func_substr_replace($account, 3, 4), "mobile"=>$account,
			    "avatar"=>"/upload/default/default_avatar.png",
			    "registertime"=>date("Y-m-d H:i"), "updatetime"=>date("Y-m-d H:i"),
			    "logintime"=>date("Y-m-d H:i"), "login_count"=>1, "user_money" =>0,
			    "referral_code"=>$referral_code, "be_referral_code"=>$be_referral_code,
				'team_path'=>$team_path,'team_parent'=>$team_parent,'is_team'=>$is_team,
				'province'=>$province,'city'=>$city,'region'=>$region
			);
			
			$userid = $model->add($user);
			if($be_referral_code){
				$user['id']=$userid;
				$this->GrantUserCoupon($user,1);//注册用户的优惠券
			}
			//绑定用户角色
			$this->BindUserRole($userid);
			
			$user = $model->find($userid);
			
			//用户注册后发放优惠券
			$this->GrantUserCoupon($user,2);
			
			//设置用户加密信息返回
			$this->SetUserHeader($userid, $password);
			
			//绑定用户第三方信息
			if($hybrid && $openid){
				switch ($hybrid){
				    case "app":
				        $type = 1;
				        break;
				    case "xcx":
				        $type = 2;
				        break;
				    case "qq":
				        $type = 3;
				        break;
				}
				
				//删除已存在的授权码
				$map = array("code"=>$openid, "type"=>$type);
				D("user_bind")->where($map)->delete();
				
				$entity = array(
				    "status"=>1, "type"=>$type, "code"=>$openid,
				    "remark"=>$remark, "createdate"=>date("Y-m-d H:i:s"),
					"userid"=>$userid
				);
				
				//保存绑定信息
				$map = array("userid"=>$userid, "type"=>$type);
				$check = D("user_bind")->where($map)->find();
				if($check){
					D("user_bind")->where($map)->save($entity);
				} else{
					D("user_bind")->add($entity);
				}
			}
			//完成注册并绑定手机号积分任务
			D('Point','Service')->append($user['id'],'regiest');
		}
		$map = array('id'=>$user['id'],'password'=>md5(strtolower('ydchun') . C("pwd_key")));
		$default_pass=D('user')->where($map)->find();
		if($default_pass){
			$user['default_pass']=1;
		}else{
			$user['default_pass']=0;
		}
		
		return $user;
	}
	//第三方授权绑定
	public function wxoauthbind(){
	    $data = I("post.");
	
	    $openid = $data["openid"];
	    if(empty($openid)){
	        E("授权登录码不能为空");
	    }
	    $mobile = $data["mobile"];
	    if(empty($mobile)){
	        E("绑定的手机号码不能为空");
	    }
	
	    $model = D("user");
	
	    $hybrid = $data["hybrid"];
	    if(empty($hybrid)){
	        E("请选择授权方");
	    }
	
	    switch ($hybrid){
	        case "app":
	            $remark = "微信APP绑定";
	            $type = 1;
	            break;
	        case "xcx":
	            $remark = "微信小程序绑定";
	            $type = 2;
	            break;
	        case "qq":
	            $remark = "QQ绑定";
	            $type = 3;
	            break;
			case "ios":
			    $remark = "apple绑定";
			    $type = 5;
			    break;
	        default:
	            E('目前不支持此登录方式');
	    }
	
	    //删除已存在的授权码
	    $map = array("code"=>$openid, "type"=>$type);
	    D("user_bind")->where($map)->delete();
	
	    $map = array("account"=>$mobile, "mobile"=>$mobile, "_logic"=>"or");
	    $user = $model->where($map)->find();
	
	    $entity = array(
	        "status"=>1, "type"=>$type, "code"=>$openid,
	        "remark"=>$remark, "createdate"=>date("Y-m-d H:i:s")
	    );
		
	    if($user){
	        //保存绑定信息
	        $entity["userid"] = $user["id"];
	        $map = array("userid"=>$user["id"], "type"=>$type);
	        $check = D("user_bind")->where($map)->find();
	        if($check){
	            D("user_bind")->where($map)->save($entity);
	        } else{
	            D("user_bind")->add($entity);
	        }
			
			//完成注册并绑定手机号积分任务
			D('Point','Service')->append($user['id'],'regiest');
			
	        return $this->UserCheckLogin($user["account"], $user["password"], true, true);
	    }else{
			$referral_code = random(10, "all");
			//邀请码
			while(true){
				$check = $model->where(array('referral_code'=>$referral_code))->find();
				if(empty($check)){
					$referral_code = $referral_code; break;
				}
				$referral_code = random(10, "all");
			}
			
			$password = md5(strtolower("bc".$mobile) . C("pwd_key"));
			$user = array(
				"account"=>$mobile, "password"=>$password, "status"=>200,
				"nickname"=>func_substr_replace($mobile, 3, 4), "mobile"=>$mobile,
				"avatar"=>"/upload/default/default_avatar.png",
				"registertime"=>date("Y-m-d H:i"), "updatetime"=>date("Y-m-d H:i"),
				"logintime"=>date("Y-m-d H:i"), "login_count"=>1, "user_money" =>0,
				"referral_code"=>$referral_code
			);
			
			$userid = $model->add($user);
			
			//保存绑定信息
			$entity["userid"] = $userid;
			$map = array("userid"=>$user["id"], "type"=>$type);
			$check = D("user_bind")->where($map)->find();
			if($check){
				D("user_bind")->where($map)->save($entity);
			} else{
				D("user_bind")->add($entity);
			}
			
			$user = $model->find($userid);
			
			//完成注册并绑定手机号积分任务
			D('Point','Service')->append($user['id'],'regiest');
			
			//用户注册后发放优惠券
			$this->GrantUserCoupon($user);
			
			
			return $this->UserCheckLogin($user["account"], $user["password"], true, true);
		}
	}
}