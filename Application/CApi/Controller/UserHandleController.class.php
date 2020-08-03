<?php
namespace CApi\Controller;
use Think\Controller;
class UserHandleController extends BaseLoggedController {

	//更新用户资料
	public function updateuserinfo(){
		$data = I("post.");
		$user = $this->AuthUserInfo;
		$entity = array();
		if($data["identity_card"]){
			if(!is_idcard($data["identity_card"])){
				E('身份证格式不正确');
			}
			$entity['identity_card']=$data['identity_card'];
		}
		if($data["nickname"]){
			$entity['nickname']=$data['nickname'];
		}
		if($data["gender"]){
			$entity['gender']=$data['gender'];
		}
		if($data["realname"]){
			$entity['realname']=$data['realname'];
		}
		if($data["position"]){
			$entity['position']=$data['position'];
		}
		if($data["work_year"]){
			$entity['work_year']=$data['work_year'];
		}
		if($data["sign"]){
			$entity['sign']=$data['sign'];
		}
		if($data["province"]){
			$entity['province']=$data['province'];
		}
		if($data["city"]){
			$entity['city']=$data['city'];
		}
		if($data["region"]){
			$entity['region']=$data['region'];
		}
		if($data["avatar"]){
			$entity['avatar']=$data['avatar'];
		}
		if($data["images"]){
			$entity['avatar']=$data['avatar'];
		}
		if($data["idcard_image"]){
			$entity['idcard_image']=$data['idcard_image'];
		}
		
		D("user")->where("id=".$user["id"])->save($entity);
		$user=D("user")->where("id=".$user["id"])->find();
		if( !empty($user["avatar"]) && !empty($user["sign"] )){
			//完善个人信息(头像,签名)积分任务
			D('Point','Service')->append($user['id'],'complate_baseuserinfo');
		}
		
		if( !empty($user["realname"]) && !empty($user["identity_card"] )){
			//完善个人信息(实名)积分任务
			D('Point','Service')->append($user['id'],'complate_trueuserinfo');
		}
		
		return;
	}

	//更换手机号码
	public function changemobile(){
		$data = I("post.");

		$user = $this->AuthUserInfo;

		$mobile = $data["mobile"];
		if(empty($mobile)){
			E("请输入当前手机号码");
		}
		if(!isMobile($mobile)){
			E("手机号码格式不正确");
        }
		if($user["mobile"] != $mobile){
			E("当前手机号码与登录账号手机号码不一致");
		}
		$password = $data["password"];
		if(empty($password)){
			E("请输入登录密码");
		}
		$password = md5(strtolower($password) . C('pwd_key'));
        $map = array("account"=>$mobile, "password"=>$password);
        $checkpassword = D("user")->where($map)->find();
		if(empty($checkpassword)){
			E("输入的登录密码错误");
		}
		$new_mobile = $data["new_mobile"];
		if(empty($new_mobile)){
			E("请输入绑定新手机号码");
		}
		if(!isMobile($new_mobile)){
            E("绑定新手机号码格式不正确");
		}
		if($mobile == $new_mobile){
			E("当前手机号码与绑定新手机号码一致，更换失败");
		}
		$code = $data["code"];
		if(empty($code)){
			E("请输入验证码");
		}
		//检查验证码
		$this->CheckSmsCode($new_mobile, "replace", $code);

		$entity = array(
			"id"=>$user["id"], "account"=>$new_mobile, "mobile"=>$new_mobile
		);

		$is = D("user")->where("id=".$user["id"])->save($entity);
        if ($is) {
            $data = D("user")->where("id=".$user["id"])->find();
            $logintype = 1;
            if ($data['password']) {
                $logintype = 0;
            }
            $user = $this->UserCheckLogin($data['account'], $data['password'], true, true, $logintype);

            return $user;
        }
        E('更换失败');
	}

	//密码修改
	public function changepassword(){
		$user = $this->AuthUserInfo;
		
		$password = trim(I('post.password'));
		$map = array('id'=>$user['id'],'password'=>md5(strtolower($password) . C("pwd_key")));
		$password_check=D('user')->where($map)->find();
		if(empty($password_check)){
			E('当前密码错误');
		}
		$new_password = trim(I("post.new_password"));
		if(empty($new_password)){
			E("请输入新的密码");
		}
        $len = strlen($new_password);
        if (!($len >= 6 && $len <= 24)) {
            E('密码长度要6-24位');
        }

		$new_password = md5(strtolower($new_password) . C("pwd_key"));

		$model = D("user");

		$entity = array(
			"id"=>$user["id"], "password"=>$new_password
		);
		$model->where("id=".$user["id"])->save($entity);

        //设置用户加密信息返回
        $this->SetUserHeader($user["id"], $new_password);

		return;
	}

	//创建/更新收货地址
	public function updateaddress(){
		$user = $this->AuthUserInfo;

		$data = I("post.");

		$id = $data["id"];
		if(empty($id)){
			$id = 0;
		}
		$consignee = $data["consignee"];
		if(empty($consignee)){
			E("收货人不能为空");
		}
		$mobile = $data["mobile"];
		if(empty($mobile)){
			E("联系手机号码不能为空");
		}
		if(!isMobile($mobile)){
			E("手机号码格式不正确");
		}
		$province = $data["province"];
		$city = $data["city"];
		$region = $data["region"];
		if(empty($province) || empty($city) || empty($region)){
			E("省市区不能为空");
		}
		$address = $data["address"];
		if(empty($address)){
			E("详细地址不能为空");
		}
		$default = $data["default"];
		if(empty($default)){
			$default = 0;
		}

		$entity = array(
			"userid"=>$user["id"], "status"=>1, "consignee"=>$consignee, "mobile"=>$mobile,
			"province"=>$province, "city"=>$city, "region"=>$region, "address"=>$address,
			"is_default"=>$default, "type"=>0, "updatetime"=>date("Y-m-d H:i:s")
		);

		$model = D("user_address");

		if($id > 0){
            $entity['id'] = $model->where("id=".$id)->save($entity);
		} else{
			$id = $entity["id"] = $model->add($entity);
		}

		if($default == 1){
			$map = array("type"=>0, "userid"=>$user["id"], "id"=>array("neq", $id));
			$model->where($map)->save(array("is_default"=>0));
		}

		return $entity;
	}

    //查看收货地址详情
    public function detailsaddress(){
        $user = $this->AuthUserInfo;

        $id = I("post.id", 0);
        if(empty($id)){
            E("请选择收货地址");
        }

        $model = D("user_address");

        $map = array("type"=>0, "userid"=>$user["id"], "id"=>$id);
        $data = $model->where($map)->find();
        if(empty($data)){
            E("不存在此收货地址，请刷新重试");
        }

        return $data;
    }

	//设置收货地址为默认
	public function defaultaddress(){
		$user = $this->AuthUserInfo;

		$id = I("post.id", 0);
		if(empty($id)){
			E("请选择收货地址");
		}

		$model = D("user_address");

        $map = array("type"=>0, "userid"=>$user["id"], "id"=>$id);
        $data = $model->where($map)->find();
        if(empty($data)){
            E("不存在此收货地址，请刷新重试");
        }
        if ($data['is_default'] == 0) {
            $map = array("type"=>0, "userid"=>$user["id"], "id"=>$id);
            $model->where($map)->save(array("is_default"=>1));

            $map = array("type"=>0, "userid"=>$user["id"], "id"=>array("neq", $id));
            $model->where($map)->save(array("is_default"=>0));
        }else{
            $map = array("type"=>0, "userid"=>$user["id"], "id"=>$id);
            $model->where($map)->save(array("is_default"=>0));
        }

        return;
	}

	//删除收货地址
	public function deleteaddress(){
		$user = $this->AuthUserInfo;

		$id = I("post.id", 0);
		if(empty($id)){
			E("请选择要删除的收货地址");
		}

		$model = D("user_address");

		$map = array("type"=>0, "userid"=>$user["id"], "id"=>$id);
		$model->where($map)->delete();

		return;
	}

	//创建/更新服务地址
	public function updateaddressservice(){
		$user = $this->AuthUserInfo;

		$data = I("post.");

		$id = $data["id"];
		if(empty($id)){
			$id = 0;
		}
		$province = $data["province"];
		$city = $data["city"];
		$region = $data["region"];
		if(empty($province) || empty($city) || empty($region)){
			E("省市区不能为空");
		}
		$address = $data["address"];
		if(empty($address)){
			E("详细地址不能为空");
		}
		$default = $data["default"];
		if(empty($default)){
			$default = 0;
		}

		$entity = array(
			"userid"=>$user["id"], "status"=>1, "consignee"=>"", "mobile"=>"",
			"province"=>$province, "city"=>$city, "region"=>$region, "address"=>$address,
			"is_default"=>$default, "type"=>1, "updatetime"=>date("Y-m-d H:i:s")
		);

		$model = D("user_address");

		if($id > 0){
            $entity['id'] = $model->where("id=".$id)->save($entity);
		} else{
			$id = $entity["id"] = $model->add($entity);
		}

		if($default == 1){
			$map = array("type"=>1, "userid"=>$user["id"], "id"=>array("neq", $id));
			$model->where($map)->save(array("is_default"=>0));
		}

		return $entity;
	}

    //查看服务地址详情
    public function detailsaddressservice(){
        $user = $this->AuthUserInfo;

        $id = I("post.id", 0);
        if(empty($id)){
            E("请选择服务地址");
        }

        $model = D("user_address");

        $map = array("type"=>1, "userid"=>$user["id"], "id"=>$id);
        $data = $model->where($map)->find();
        if(empty($data)){
            E("不存在此服务地址，请刷新重试");
        }

        return $data;
    }

	//设置服务地址为默认
	public function defaultaddressservice(){
		$user = $this->AuthUserInfo;

		$id = I("post.id", 0);
		if(empty($id)){
			E("请选择服务地址");
		}

		$model = D("user_address");

        $map = array("type"=>1, "userid"=>$user["id"], "id"=>$id);
        $data = $model->where($map)->find();
        if(empty($data)){
            E("不存在此服务地址，请刷新重试");
        }
        if ($data['is_default'] == 0) {
            $map = array("type"=>1, "userid"=>$user["id"], "id"=>$id);
            $model->where($map)->save(array("is_default"=>1));

            $map = array("type"=>1, "userid"=>$user["id"], "id"=>array("neq", $id));
            $model->where($map)->save(array("is_default"=>0));
        }else{
            $map = array("type"=>1, "userid"=>$user["id"], "id"=>$id);
            $model->where($map)->save(array("is_default"=>0));
        }

        return;
	}

	//删除服务地址
	public function deleteaddressservice(){
		$user = $this->AuthUserInfo;

		$id = I("post.id", 0);
		if(empty($id)){
			E("请选择要删除的服务地址");
		}

		$model = D("user_address");

		$map = array("type"=>1, "userid"=>$user["id"], "id"=>$id);
		$model->where($map)->delete();

		return;
	}
	
	//收藏/取消收藏 type：1=收藏，2=取消收藏
	public function collection(){
		$user = $this->AuthUserInfo;

		$data = I("post.");

		$type = $data["type"];
		if(!in_array($type, [1,2])){
			E("请选择操作类型");
		}
		$source = $data["source"];
		if(!in_array($source, [1,2,3,4,5,6])){
			E("请选择收藏的类型");
		}
		$objectid = $data["objectid"];
		if(empty($objectid)){
			E("请选择收藏的对象");
		}
		if($type == 2){
			$objectid = explode(",", $objectid);
		}

		$model = D("user_record");

		$map = array("userid"=>$user["id"], "source"=>$source, "type"=>1, "objectid"=>$objectid);

		if($type == 1){ //收藏
			$entity = array(
				"userid"=>$user["id"], "source"=>$source, "type"=>1,
				"objectid"=>$objectid, "createdate"=>date("Y-m-d H:i:s")
			);

			$check = $model->where($map)->find();
            if (empty($check)) {
                $model->add($entity);
            }
			if($source==6){
				$map = array('id'=>$objectid);
				D('business')->where($map)->setInc('collect',1);
			}
		} else if($type == 2){ //取消收藏
            $map['objectid'] = array('in', $objectid);
			$model->where($map)->delete();
			if($source==6){
				$map = array('id'=>array('in',$objectid));
				D('business')->where($map)->setDec('collect',1);
			}
		}

		return;
	}

	//删除消息
	public function deletemessage(){
		$user = $this->AuthUserInfo;

		$id = I("post.id");
		if(empty($id)){
			E("请选择要删除的消息");
		}

		$model = D("user_message");

		$map = array("hybrid"=>"client", "userid"=>$user["id"], "id"=>$id);
		$model->where($map)->delete();

		return;
	}

	//清空消息
	public function clearmessage(){
		$user = $this->AuthUserInfo;

		$model = D("user_message");

		//消息类型（0=系统，1=订单）
		$type = I("post.type");

		$map = array("hybrid"=>"client", "userid"=>$user["id"]);
		if(in_array($type, [0,1])){
			$map["type"] = $type;
		}
		$model->where($map)->delete();
		
		return;
	}
	//创建/更新照护人基础信息
	public function base_usercare(){
		$user = $this->AuthUserInfo;
		
		$data = I("post.");
		
		$id = $data["id"];
		if(empty($id)){
			$id = 0;
		}
		$name = $data["name"];
		if(empty($name)){
			E("请输入姓名");
		}
		$gender = $data["gender"];
		if(empty($gender)){
			E("请选择性别");
		}
		$contact = $data["contact"];
		if(empty($contact)){
			E("请填写紧急联系人");
		}
		$contact_mobile = $data["contact_mobile"];
		if(empty($contact_mobile)){
			E("请填写紧急联系人电话号码");
		}
		$birth = $data["birth"];
		if(empty($birth)){
			E("请选择出生日期");
		}
		$address_type = $data["address_type"];
		
		if($address_type==1){
			$hospital = $data["hospital"];
			if(empty($hospital)){
				E("请填写医院");
			}
			$department = $data["department"];
			if(empty($department)){
				E("请填写科室");
			}
			$ward = $data["ward"];
			if(empty($ward)){
				E("请填写病房");
			}
		}else{
			$hospital = '';
			$department = '';
			$ward = '';
		}
		$longitude = $data['longitude'];//经度
		if(empty($longitude)){
			E("缺少经度");
		}
		$latitude = $data['latitude'];//纬度
		if(empty($latitude)){
			E("缺少纬度");
		}
		$province = $data["province"];
		if(empty($province)){
			E("请选择省份");
		}
		$city = $data["city"];
		if(empty($city)){
			E("请选择城市");
		}
		$region = $data["region"];
		if(empty($region)){
			E("请选择区县");
		}
		$region_detail = $data["region_detail"];
		$address = $data["address"];
		if(empty($address)){
			E("请选择详细地址");
		}
		
		$is_default = I("post.is_default",0);
		
		$model = D("user_care");
		
		if($is_default==1){
			$map = array('userid'=>$user['id']);
			$entity = array('is_default'=>0);
			$model->where($map)->save($entity);
		}
		
		$entity = array(
			"userid"=>$user["id"], "name"=>$name, "gender"=>$gender, "birth"=>$birth,
			"address_type"=>$address_type,"region"=>$region,"address"=>$address,
			"is_default"=>$is_default, "updatetime"=>date("Y-m-d H:i:s"),
			'hospital'=>$hospital,'department'=>$department,'ward'=>$ward,
			'longitude'=>$longitude,'latitude'=>$latitude,"contact"=>$contact,
			"contact_mobile"=>$contact_mobile,'province'=>$province,'city'=>$city,
			'region'=>$region,'region_detail'=>$region_detail
		);
		
		if($id > 0){
			$map = array("userid"=>$user["id"], "id"=>$id);
			$usercare = $model->where($map)->find();
			
			/* $time = strtotime("-1 month", time());
			if($usercare["status"] == 1 && $usercare["updatetime"] > $time){
				E("当前照护人信息距离上次更新未足一个月，更新失败");
			} */
		
			$model->where($map)->save($entity);
		} else{
			$entity["createdate"] = date("Y-m-d H:i:s");
			D('Point','Service')->append($user['id'],'add_usercare');
			$id = $model->add($entity);
		}
		
		$ret = array('id'=>$id, 'name'=>$entity['name']);
		
		return $ret;
	}
	//创建/更新照护人选填信息
	public function advanced_usercare(){
		$user = $this->AuthUserInfo;
		
		$data = I("post.");
		
		$id = $data["id"];
		if(empty($id)){
			E('缺少照护人基础信息');
		}
		
		$mobile = $data["mobile"]?:'';
		$avatar = $data["avatar"];
		if(empty($avatar)){
			$map = array('id'=>$id);
			$care_info=D('user_care')->alias('gender')->where($map)->find();
			if($care_info['gender']=='男'){
				$avatar = '/upload/usercare/oldman.png'; //男性默认头像
			}else{
				$avatar = '/upload/usercare/granny.png'; //女性默认头像
			}
		}
		
		$height = $data["height"];
		$weight = $data["weight"];
		$language = $data["language"];
		if(empty($language)){
			E("请选择语言能力");
		}
		$identification_type = $data["identification_type"];
		$identification = $data["identification"];
		$identification_image=$data['identification_image'];
		$diet = $data["diet"];
		if(empty($diet)){
			E("请选择饮食偏好");
		}
		
		$health=array();
		$health['action']=$data['action'];
		$health['eat']=$data['eat'];
		$health['clothes']=$data['clothes'];
		$health['wash']=$data['wash'];
		$health['shit']=$data['shit'];
		$health['urine']=$data['urine'];
		$health['urine_piping']=$data['urine_piping'];
		$health['stomach_piping']=$data['stomach_piping'];
		$health['fistula_tube']=$data['fistula_tube'];
		$health['pressure']=$data['pressure'];
		$health['tracheotomy']=$data['tracheotomy'];
		$health['realize']=$data['realize'];
		$health['dementia']=$data['dementia'];
		if($health['dementia'] == 2){ //老人痴呆，2:不清楚=0:无
			$health['dementia'] = 0;
		}
		$health['deathbed']=$data['deathbed'];
		//评估护理等级
		$result=$this->usercare_assess($health);
		$level=$result['level'];
		$health=json_encode($health,JSON_UNESCAPED_UNICODE);
		
		$relationship = $data["relationship"];
		if(empty($relationship)){
			E("请选择与被照护人的关系");
		}
		$images = $data["images"];
		$remark = $data["remark"];
		
		
		$model = D("user_care");
		
		
		
		$entity = array(
			"status"=>0, "userid"=>$user["id"], "height"=>$height, "weight"=>$weight,
			"language"=>$language, "identification_type"=>$identification_type, "identification"=>$identification, "diet"=>$diet, "health"=>$health,
			"images"=>$images, "relationship"=>$relationship, "remark"=>$remark, 
			"updatetime"=>date("Y-m-d H:i:s"),'level'=>$level,'identification_image'=>$identification_image,'avatar'=>$avatar,'mobile'=>$mobile
		);
		
		if($id > 0){
			$map = array("userid"=>$user["id"], "id"=>$id);
			$usercare = $model->where($map)->find();
			
			$time = strtotime("-1 month", time());
			if($usercare["status"] == 1 && $usercare["updatetime"] > $time){
				E("当前照护人信息距离上次更新未足一个月，更新失败");
			}
		
			$model->where($map)->save($entity);
			$ret = array('id'=>$id, 'name'=>$entity['name'],'level'=>$level);
			
			//评估照护人护理等级积分任务
			D('Point','Service')->append($user['id'],'complate_usercare');
			
			return $ret;
		} else{
			E('缺少照护人基础信息');
		}
		
		
	}
	
	//创建/更新照护人
	public function updateusercare(){
		$user = $this->AuthUserInfo;

		$data = I("post.");

		$id = $data["id"];
		if(empty($id)){
			$id = 0;
		}
		$name = $data["name"];
		if(empty($name)){
			E("请输入姓名");
		}
		$mobile = $data["mobile"];
		$gender = $data["gender"];
		if(empty($gender)){
			E("请选择性别");
		}
		$birth = $data["birth"];
		if(empty($birth)){
			E("请选择出生日期");
		}
		$height = $data["height"];
		$weight = $data["weight"];
		$language = $data["language"];
		if(empty($language)){
			E("请选择语言能力");
		}
		$identification_type = $data["identification_type"];
		if(!in_array($identification_type, [1,2,3])){
			E("请选择证件类型");
		}
		$identification = $data["identification"];
		if(empty($identification)){
			E("请输入证件号");
		}
		if($identification_type == 1){
			if (is_idcard($identification) == false) {
				E("身份证件号码格式不正确");
			}
			if(check_idcard_birth($identification, $birth) == false){
				E("身份证号码与出生日期不匹配");
			}
		}
		$identification_image=$data['identification_image'];
		$diet = $data["diet"];
		if(empty($diet)){
			E("请选择饮食偏好");
		}
		
		$health=array();
		$health['action']=$data['action'];
		$health['eat']=$data['eat'];
		$health['clothes']=$data['clothes'];
		$health['wash']=$data['wash'];
		$health['shit']=$data['shit'];
		$health['urine']=$data['urine'];
		$health['urine_piping']=$data['urine_piping'];
		$health['stomach_piping']=$data['stomach_piping'];
		$health['fistula_tube']=$data['fistula_tube'];
		$health['pressure']=$data['pressure'];
		$health['tracheotomy']=$data['tracheotomy'];
		$health['realize']=$data['realize'];
		$health['dementia']=$data['dementia'];
		if($health['dementia'] == 2){ //老人痴呆，2:不清楚=0:无
			$health['dementia'] = 0;
		}
		$health['deathbed']=$data['deathbed'];
		//评估护理等级
		$result=$this->usercare_assess($health);
		$level=$result['level'];
		$health=json_encode($health,JSON_UNESCAPED_UNICODE);
		
		/* $activity = $data["activity"];
		if(empty($activity)){
			E("请选择活动能力");
		}
		$health = $data["health"];
		if(empty($health)){
			E("请选择健康自评");
		} */
		$relationship = $data["relationship"];
		if(empty($relationship)){
			E("请选择与被照护人的关系");
		}
		$images = $data["images"];
		// if(empty($images)){
		// 	E("请上传您的病历");
		// }
		$remark = $data["remark"];

        $is_default = I("post.is_default",0);

		$model = D("user_care");

		$entity = array(
			"status"=>0, "userid"=>$user["id"], "name"=>$name,"mobile"=>$mobile, "gender"=>$gender, "birth"=>$birth, "height"=>$height, "weight"=>$weight,
			"language"=>$language, "identification_type"=>$identification_type, "identification"=>$identification, "diet"=>$diet, "health"=>$health,
			"images"=>$images, "relationship"=>$relationship, "remark"=>$remark, "is_default"=>$is_default, "updatetime"=>date("Y-m-d H:i:s"),'level'=>$level,'identification_image'=>$identification_image
		);

		if($id > 0){
			$map = array("userid"=>$user["id"], "id"=>$id);
			$usercare = $model->where($map)->find();
			
			$time = strtotime("-1 month", time());
			if($usercare["status"] == 1 && $usercare["updatetime"] > $time){
				E("当前照护人信息距离上次更新未足一个月，更新失败");
			}

			$model->where($map)->save($entity);
		} else{
			$entity["createdate"] = date("Y-m-d H:i:s");

			$id = $model->add($entity);
		}
		//评估照护人护理等级积分任务
		D('Point','Service')->append($user['id'],'complate_usercare');
        $ret = array('id'=>$id, 'name'=>$entity['name']);

		return $ret;
	}

	//验证照护人基本信息
	public function check_usercare_base(){
		$data = I("post.");
		$name = $data["name"];
		if(empty($name)){
			E("请输入姓名");
		}
		$mobile = $data["mobile"];
		// if(empty($mobile)){
		// 	E("请输入手机号");
		// }
		$gender = $data["gender"];
		if(empty($gender)){
			E("请选择性别");
		}
		$birth = $data["birth"];
		if(empty($birth)){
			E("请选择出生日期");
		}
		$height = $data["height"];
		if(empty($height)){
			E("请输入身高");
		}
		$weight = $data["weight"];
		if(empty($weight)){
			E("请输入体重");
		}
		$identification_type = $data["identification_type"];
		if(!in_array($identification_type, [1,2,3])){
			E("请选择证件类型");
		}
		$identification_image = $data["identification_image"];
		if(empty($identification_image)){
			E("请上传证件照片");
		}
		$identification = $data["identification"];
		if(empty($identification)){
			E("请输入证件号");
		}
		if($identification_type == 1){
			if (is_idcard($identification) == false) {
				E("身份证件号码格式不正确");
			}
			if(check_idcard_birth($identification, $birth) == false){
				E("身份证号码与出生日期不匹配");
			}
		}
		return ;
	}

	//删除照护人
	public function deleteusercare(){
		$user = $this->AuthUserInfo;

		$id = I("post.id", 0);
		if(empty($id)){
			E("请选择要删除的照护人");
		}

		// begin 验证照护人是否有正在执行的订单
		$ordermodel = D("service_order");
		// 提出 删除、取消、完成、退款完成 的订单
		$map = array("status"=>array("not in", [-1,2,4,6]), "careid"=>$id);
		$count = $ordermodel->where($map)->count();
		if($count > 0){
			E("照护人存在正在服务的订单，删除失败");
		}

		$model = D("user_care");

		$map = array("userid"=>$user["id"], "id"=>$id);
		$model->where($map)->delete();

		return;
	}

    //设置照护人为置顶
    public function topcare(){
        $user = $this->AuthUserInfo;

        $id = I("post.id", 0);
        if(empty($id)){
            E("请选择要置顶的照护人");
        }

        $model = D("user_care");

        $map = array("userid"=>$user["id"], "id"=>$id);
        $data = $model->where($map)->find();
        if(empty($data)){
            E("不存在此照护人，请刷新重试");
        }
        if ($data['top'] == 0) {
            $map = array("userid"=>$user["id"], "id"=>$id);
            $model->where($map)->save(array("top"=>1));

            $map = array("userid"=>$user["id"], "id"=>array("neq", $id));
            $model->where($map)->save(array("top"=>0));
        }else{
            $map = array("userid"=>$user["id"], "id"=>$id);
            $model->where($map)->save(array("top"=>0));
        }


        return;
	}
	
	//查询当前照护人护理等级
	public function usercare_level(){
		$data=I('post.');
		$result['level']=1;
		$result=$this->usercare_assess($data);
		switch($result['level']){
			case 1:
				$result['info']='半护理';
				break;
			case 2:
				$result['info']='全护理';
				break;
			case 3:
				$result['info']='特重护理';
				break;
			defalut:
				$result['info']='未知';
		}
		return $result;
	}

	//照护人护理等级评估
	private function usercare_assess($data){
		$return=array('level'=>1);
		
		//昏迷直接判断为特重护理
		if($data['realize']==2){
			$return['level']=3;
			return $return;
		}

		//管道
		if( $data['urine_piping']==1 || $data['stomach_piping']==1 || $data['fistula_tube']==1 || $data['tracheotomy']){
			$gd=1;
		}else{
			$gd=0;
		}
		
		//大小便失控
		if($data['shit']||$data['urine']){
			$incontinence=1;
		}else{
			$incontinence=0;
		}
		//全护理判断 1.卧床 2.轮椅+喂食 3.轮椅+大小便失禁 4.大小便失控 5.管道
		if($data['action']==3 || ($data['action']==1 && $data['eat']>=1) || ($data['action']==2 && $data['incontinence']==1) || $incontinence==1){
			$return['level']=2;
			//排除痴呆以外是否全护理
			$total_nursing=1;
		}elseif($data['dementia']==1 || $gd==1){
			$return['level']=2;
			//痴呆造成的全护理
			$total_nursing=0;
		}
		
		//特重护理判断
		if(($total_nursing==1&&$gd==1) || ($total_nursing==1&&$data['dementia']==1) || ($gd==1&&$data['dementia']==1)){
			$return['level']=3;
		}
		
		return $return;
	}

    /**
     * Notes: 我的动态列表
     * User: dede
     * Date: 2020/3/10
     * Time: 2:34 下午
     * @return array
     */
	public function moment(){
        $user = $this->AuthUserInfo;
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;
        $data = D('moment', 'Service')->myMoment($user['id'], $begin, $row);
        $count = $data['total'];
        $totalpage = ceil($count / $row);
        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        $data = [ 'moment' => $data['rows'] ];
        return $data;
    }

    /**
     * Notes: 动态草稿箱
     * User: dede
     * Date: 2020/3/10
     * Time: 9:49 下午
     * @return array
     */
    public function draft(){
        $user = $this->AuthUserInfo;
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;
        $data = D('moment', 'Service')->draft($user['id'], $begin, $row);
        $count = $data['total'];
        $totalpage = ceil($count / $row);
        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        $data = [ 'moment' => $data['rows'] ];
        return $data;
    }

    /**
     * Notes: 我的粉丝
     * User: dede
     * Date: 2020/3/11
     * Time: 10:00 上午
     * @return array
     */
    public function fans(){
        $user = $this->AuthUserInfo;
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $begin = ($page-1)*$row;
        $data = D('User', 'Service')->fans($user['id'], $begin, $row);
        $count = $data['total'];
        $totalpage = ceil($count / $row);
        $this->SetPaginationHeader($totalpage, $count, $page, $row);

        $data = [ 'users' => $data['rows'] ];
        return $data;
    }

    /**
     * Notes: 我的关注
     * User: dede
     * Date: 2020/3/25
     * Time: 2:24 下午
     * @return array
     */
    public function follow(){
        $user = $this->AuthUserInfo;
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $group_id = I('group_id', 0, 'intval');
        $begin = ($page-1)*$row;
        $data = D('User', 'Service')->follow($user['id'], $group_id, $begin, $row);
        $count = $data['total'];
        $totalpage = ceil($count / $row);
        $this->SetPaginationHeader($totalpage, $count, $page, $row);
        $group = D('UserFollow', 'Service')->groupList($user['id']);
        $data = [ 'users' => $data['rows'], 'group' => $group ];
        return $data;
    }
}