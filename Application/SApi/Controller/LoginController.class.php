<?php
namespace SApi\Controller;
use Think\Controller;
class LoginController extends BaseController {

	//手机号验证
	public function check_mobile(){
		$data=I('post.');
		$mobile = $data["mobile"];
		$action = $data["action"];
		$role=$data['role'];
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
				$map=array();
				$map['userid']=$checkuser['id'];
				$map['role']=array('neq',1);
				$role=D('user_role')->where($map)->find();
				if($role){
					E('您好,该手机号已经提交过注册过或已被限制申请,请更换手机或联系一点椿平台');
				}
			}
		}else{
			$model = D("user_role");
			$map = array("userid"=>$checkuser['id'], "status"=>1, "role"=>array("in", [2,3,4,5,6]));
			$count = $model->where($map)->count();
			if($count <= 0){
			    E("当前账号未注册服务人员，登录失败",44);
			}
			if(!$checkuser){
				E('该手机号未注册');
			}
			
		}
		return;
	}

	//验证码验证
	public function check_code(){
		$data=I('post.');
		$mobile = trim($data["mobile"]);
		$action = trim($data["action"]);
		$code = trim($data["code"]);
		//检查验证码
		$this->CheckSmsCode($mobile,$action,$code);
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
		
		//登录成功的时候判断是否有可接订单
		if($user){
			
			$where=array();
			$where['service_userid']=0;
			$where['status']=1;
			$where['begintime']=array('gt',date('Y-m-d H:i:s'));
			$is_order=D('service_order')->where($where)->find();
			if($is_order){
				$hybrid='service';
				$igeitui = D("Common/IGeTuiMessagePush");
				$igeitui->setHybrid($hybrid);
				$user_info=D('user')->where('id='.$user['id'])->find();
				$igeitui->PushMessageToSingle($user_info["sclientid"], $user_info["ssysten"], "您有一条新的消息",'【一点椿】又有新的服务订单了，赶紧打开一点椿服务端APP抢单啦。有提成，有补贴，有保险，有晋升，感谢您的付出，为长者提供温馨贴心的照护服务。');
				$requestsms=D('Common/RequestSms');
				//发送短信通知 (SMS_174993316)
				$requestsms->SendRemindOrder($info);
			}
			if($user['profile']['face']==''){
				$user['face']='0';
			}else{
				$user['face']='1';
			}
		}
		
		//用户角色信息
		$rolemodel = D("user_role");
		$map = array("userid"=>$user["id"]);
		$roles = $rolemodel->where($map)->order('role desc')->select();
		$user["role"] = array();
		foreach($roles as $k=>$v){
			$user["role"][] = $v["role"];
		}
        return $user;
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

        $model = D("user");

        $map = array("account"=>$account, "mobile"=>$account, "_logic"=>"or");
        $user = $model->where($map)->find();
        if(empty($user)){
            E('手机号码还未注册');
        }

        $entity = array(
            "id"=>$user["id"], "password"=>md5(strtolower($password) . C("pwd_key")),
            "updatetime"=>date("Y-m-d H:i:s")
        );
        $model->where("id=".$user["id"])->save($entity);

        return;
	}
	
	//用户注册
	public function register(){
		$data=I('post.');
		
		//账号=手机号
		$account = $data["mobile"];
		
		//验证码
		$code = $data["code"];
		
		//签名图片
		$sign = $data["sign"];
		
		$hybrid = 'app';
		$openid = $data["openid"];
		
		//用户资质认证 内容
		$profile=htmlspecialchars_decode($data['profile']);
		$profile=json_decode($profile,true);
		
		//身份证图片
		$idcard_image = $profile["idcard_image"];
		if(empty($idcard_image)){
			E('缺少身份证照片');
		}
		
		//体检表图片
		$test_image = $profile["test_image"];
		if(empty($idcard_image)){
			E('缺少体检表图片');
		}
		
		//密码
		$password = $data["password"];
		
		//身份 1=用户,2=送餐员,3=家护师,4=康复师,5=医生,6=护士
 		$role=$data['role'];
		
		//人脸识别照片
		$face=$data['face'];
		
		if(!in_array($role,array(1,2,3,4,5,6))){
			E('无效身份');
		}
		
		if(empty($account)){
		    E("手机号码不能为空");
		}
		if(!isMobile($account)){
		    E("手机号码格式不正确");
		}
		if(empty($password)){
		    E("密码不能为空");
		}
		$password = md5(strtolower($password) . C("pwd_key"));
		
		$model = D("user");
		
		$where = array("u.account"=>$account, "u.mobile"=>$account, "_logic"=>"or");
		$map['_complex']=$where;
		$map['ur.role']=$role;
		$checkuser = $model->alias('u')->join('LEFT JOIN sj_user_role ur on u.id=ur.userid')->where($map)->find();
		if($checkuser){
		    E("手机号码已经被注册");
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
		
		//判断是否有用户信息 有则更新账号密码 无则创建用户user表信息
		$user=$model->alias('u')->where($where)->find();
		if($user){
			//user表信息
			$d["mobile"]=$d['account']=$account;
			$d["password"]=$password;
			$model->where("id=".$user['id'])->save($d);
		}else{
			$d = array(
			    "account"=>$account, "password"=>$password, "status"=>200,
			    "nickname"=>func_substr_replace($account, 3, 4), "mobile"=>$account,
			    "avatar"=>"/upload/default/default_avatar.png",
			    "registertime"=>date("Y-m-d H:i"), "updatetime"=>date("Y-m-d H:i"),
			    "logintime"=>date("Y-m-d H:i"), "login_count"=>1, "user_money" =>0,
			    "referral_code"=>$referral_code,
			);
			$user['id']=$model->add($d);
		}
		
		$profile_info=D("user_profile")->where(array('userid'=>$user['id']))->find();
		if(!$profile_info){
			D("user_profile")->add(array('userid'=>$user['id']));
		}
		
		$user['role']=array($role);
		
		if(!$profile){
			E('没收到profile');
		}
		$this->updateuserprofile($user,$profile);
		D("user_profile")->where(array('userid'=>$user['id']))->save(array('face'=>$face,'sign_image'=>$sign,'idcard_image'=>$idcard_image,'test_image'=>$test_image));
		
		$id=$user['id'];
		if($id > 0){
		    $model->where("id=".$id)->save($d);
		}else{
		    if ($role != 1 && $checkuser) {
		        //创建服务用户时，判断是否已存在这个账号，存在则赋值userid
		        $userid = $checkuser['id'];
		        $rolemodel = D("user_role");
		        $map = array('role'=>array('gt',1), "userid"=>$userid);
		        $isrole = $rolemodel->where($map)->find();
		        if ($isrole) {
		            switch ($isrole['role']) {
		                case 2: $str = "送餐员"; break;
		                case 3: $str = "家护师"; break;
		                case 4: $str = "康复师"; break;
		                case 5: $str = "医生"; break;
		                case 6: $str = "护士"; break;
		            }
		            E('已添加了'.$str.',不能再添加其他服务人员');
		        }
		        $this->BindUserRole($userid, $role);
		    }else{
		        $userid = $model->add($d);
		        //绑定用户角色
		        $this->BindUserRole($userid, 1);
		        if ($role != 1) {
		            $this->BindUserRole($userid, $role);
		        }
		    }
		}
		$this->BindUserRole($id, $role);
		
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
		
		return ;
	}
	
	//更新用户资质
	public function updateuserprofile($user,$data){
		
		$roles = $user["role"];
		$data['papers'] = json_decode($data['papers'],true);
	    if(!$data['papers']){
	    	E('没收到papers');
	    }
		//姓名
		$realname = $data["realname"];
		if(empty($realname)){
			E("请输入姓名");
		}
		//性别
		$gender = $data["gender"];
		if(empty($gender)){
			E("请选择性别");
		}
		//出生日期
		$birth = $data["birth"];
		if(empty($birth)){
			E("请选择出生日期");
		}
		//身份证号码
		$idcard = $data["idcard"];
		if(empty($idcard)){
			E("请输入身份证号码");
		}
		//手机号码
		$mobile = $data["mobile"];
		if(empty($mobile)){
			E("请输入手机号码");
		}
		//身高
		$height = $data["height"];
		if(empty($height)){
			E("请输入身高");
		}
		//体重
		$weight = $data["weight"];
		if(empty($weight)){
			E("请输入体重");
		}
		//家护师
		if(in_array(3, $roles)){
			
			//护理级别
			$major_level = $data["major_level"];
			if(!in_array($major_level, [1,2,3])){
				E("请选择护理级别");
			}
		}
		//省份
		$province = $data["province"];
		if(empty($province)){
			E("请选择省份");
		}
		//城市
		$city = $data["city"];
		if(empty($city)){
			E("请选择城市");
		}
		//区县
		$region = $data["region"];
		if(empty($region)){
			E("请选择区县");
		}
		//送餐员
		if(in_array(2, $roles)){
			//所属餐厅
			$resid = $data["resid"];
			if(empty($resid)){
				E("请选择所属餐厅");
			}
		} else{
			//学历
			$education = $data["education"];
			if(!in_array($education, [1,2,3])){
				E("请选择学历");
			}
			//工作年限
			$work_year = $data["work_year"];
			if(empty($work_year)){
				E("请输入工作年限");
			}
		}
		//家护师,康复师
		if(in_array(3, $roles) || in_array(4, $roles)){
			//语言
			$language = $data["language"];
			if(empty($language)){
				E("请选择语言");
			}
		}
		//医生、护士
		if(in_array(5, $roles) || in_array(6, $roles)){
			//职称
			$position = $data["position"];
			if(empty($position)){
				E("请输入职称");
			}
			//科室
			$department = $data["department"];
			if(empty($department)){
				E("请输入科室");
			}
		}
		//家护师、医生、护士
		if(in_array(3, $roles) || in_array(5, $roles) || in_array(6, $roles)){
			//特长
			$major = $data["major"];
			if(empty($major)){
				E("请选择专业特长");
			}
		}
		//个人简介
		$intro = $data["intro"];
	
		//证件信息
		$papers = $data["papers"];
		if(empty($papers) || count($papers) <= 0){
			E("请上传证件信息");
		}
		//健康证
		if(empty($papers["type_2"]) || empty($papers["type_2"]["images"])){
			E("请上传健康证信息");
		}/* 
		if(empty($papers["type_2"]["begintime"]) || empty($papers["type_2"]["validtime"])){
			E("请选择健康证有效日期");
		} */
		//学历
		if(!in_array(2, $roles) && !in_array(3,$roles)){
			if(empty($papers["type_3"])){
				E("请上传学历信息");
			}
			/* if(empty($papers["type_3"]["name"])){
				E("请输入教育机构名称");
			}
			if(empty($papers["type_3"]["begintime"]) || empty($papers["type_3"]["validtime"])){
				E("请上传教育时间");
			}
			if($papers["type_3"]["begintime"] >= $papers["type_3"]["validtime"]){
				E("教育结束时间必须大于开始时间");
			} */
			if(empty($papers["type_3"]["images"])){
				E("请上传学历证书");
			}
		}
		
		//用户附加信息
		$usermodel = D("user_profile");
		
		//检查推荐人手机号码或邀请码
		$be_referral_code=$data['be_referral_code'];
		if($be_referral_code){
		    $map = array("referral_code"=>$be_referral_code, "mobile"=>$referral_code, "_logic"=>"OR");
		    $be_referral_user = $usermodel->where($map)->find();
		    if($be_referral_user){
		        $be_referral_code = $be_referral_user["referral_code"];
		    } else{
		        $be_referral_code = null;
		    }
		}
		
		$referral_code = random(10, "all");
		//邀请码
		while(true){
		    $check = $usermodel->where(array('referral_code'=>$referral_code))->find();
		    if(empty($check)){
		        $referral_code = $referral_code; break;
		    }
		    $referral_code = random(10, "all");
		}
		
		$entity = array(
			"realname"=>$realname, "gender"=>$gender, "birth"=>$birth, "idcard"=>$idcard, "height"=>$height, "weight"=>$weight, "mobile"=>$mobile,
			"major_level"=>getIntValue($major_level), "province"=>$province, "city"=>$city, "region"=>$region, "resid"=>getIntValue($resid),
			"language"=>$language, "education"=>getIntValue($education), "work_year"=>getIntValue($work_year), "position"=>$position,
			"department"=>$department, "major"=>$major, "intro"=>$intro, "status"=>2, "updatetime"=>date("Y-m-d H:i:s"),'referral_code'=>$referral_code,'be_referral_code'=>$be_referral_code
		);
		$map = array("userid"=>$user["id"]);
		$usermodel->where($map)->save($entity);
		
		$map=array('id'=>$user['id']);
		$entity=array(
			"province"=>$province, "city"=>$city, "region"=>$region,
			"realname"=>$realname,"identity_card"=>$idcard
		);
		D('user')->where($map)->save($entity);
		
		//用户专业信息
		$papersmodel = D("user_papers");
		
		$time = date("Y-m-d H:i:s");
	
	
		//健康证
		$papers["type_2"]["images"]=$this->DelUrlListHandle($papers["type_2"]["images"]);
		$entity = array(
			"userid"=>$user["id"], "type"=>2, "status"=>0, "name"=>"健康证", "images"=>$papers["type_2"]["images"],
			"begintime"=>$papers["type_2"]["begintime"], "validtime"=>$papers["type_2"]["validtime"],
			"createdate"=>$time, "updatetime"=>$time
		);
		$map = array("userid"=>$user["id"], "type"=>2);
		$checkpaper = $papersmodel->where($map)->find();
		if(empty($checkpaper)){
			$papersmodel->add($entity);
		} else{
			if($entity["images"] != $checkpaper["images"] || $entity["begintime"] != $checkpaper["begintime"]
				|| $entity["validtime"] != $checkpaper["validtime"]){
				$papersmodel->where($map)->save($entity);
			}
		}
		
		//学历
		$entity = array(
			"userid"=>$user["id"], "type"=>3, "status"=>0, "name"=>$papers["type_3"]["name"], "images"=>$papers["type_3"]["images"],
			"begintime"=>$papers["type_3"]["begintime"], "validtime"=>$papers["type_3"]["validtime"],
			"createdate"=>$time, "updatetime"=>$time
		);
		$map = array("userid"=>$user["id"], "type"=>3);
		$checkpaper = $papersmodel->where($map)->find();
		if(empty($checkpaper)){
			$papersmodel->add($entity);
		} else{
			if($entity["name"] != $checkpaper["name"] || $entity["images"] != $checkpaper["images"] ||
				$entity["begintime"] != $checkpaper["begintime"] || $entity["validtime"] != $checkpaper["validtime"]){
				$papersmodel->where($map)->save($entity);
			}
		}
	
		//专业资质
		$map = array("userid"=>$user["id"], "type"=>4);
		$papersmodel->where($map)->delete();
		foreach($papers["type_4"] as $k=>$v){
			$entity = array(
				"userid"=>$user["id"], "type"=>4, "status"=>0, "name"=>$v["name"], "images"=>$v["images"],
				"begintime"=>$v["begintime"], "validtime"=>$v["validtime"],
				"createdate"=>$time, "updatetime"=>$time
			);
			$papersmodel->add($entity);
		}
	
		//从业经验
		$map = array("userid"=>$user["id"], "type"=>5);
		$papersmodel->where($map)->delete();
		foreach($papers["type_5"] as $k=>$v){
			$entity = array(
				"userid"=>$user["id"], "type"=>5, "status"=>0, "name"=>$v["name"],
				"begintime"=>$v["begintime"], "validtime"=>$v["validtime"], "job"=>$v["job"],
				"createdate"=>$time, "updatetime"=>$time
			);
			$papersmodel->add($entity);
		}
	    $data['updatetime'] = $time;
		return $data;
	}
	//第三方登录
	public function oauthlogin(){
	    $data = I("post.");
	    //第三方授权码openid
	    $openid = $data["openid"];
	    if(empty($openid)){
	        E("授权登录码不能为空");
	    }
	    $hybrid = $data["hybrid"]?:'sapp';
	    if(empty($hybrid)){
	        E("请选择授权方");
	    }
		switch ($hybrid){
		    case 'sapp':
		        $type = 4;
		        break;
			case 'sios':
			    $type = 6;
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
	
	    return $this->UserCheckLogin($user["account"], $user["password"], true, true);
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
	
	    $hybrid = $data["hybrid"]?:'sapp';
	    if(empty($hybrid)){
	        E("请选择授权方");
	    }
	    
	    switch ($hybrid){
	        case "sapp":
	            $remark = "微信APP绑定";
	            $type = 4;
	            break;
	    	case "sios":
	    	    $remark = "apple绑定";
	    	    $type = 6;
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
			$model = D("user_role");
			$map = array("userid"=>$user["id"], "status"=>1, "role"=>array("in", [2,3,4,5,6]));
			$count = $model->where($map)->count();
			if($count <= 0){
			    E("当前用户为非服务人员，将前往注册",30);
			}
			
			
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
	}
}