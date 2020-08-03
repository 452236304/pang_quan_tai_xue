<?php
namespace SApi\Controller;
use Think\Controller;
class UserHandleController extends BaseLoggedController {

	//更新用户头像
	public function updateavatar(){
		$user = $this->AuthUserInfo;

		$exts = array("jpg","jpeg","png");
		$image = $this->ImageUpload("image", "user", $exts);
		if(!$image){
			E("头像上传失败，请重新尝试");
		}

		$avatar = '/upload/user/'.$image;

		$model = D("user");

		$entity = array("avatar"=>$avatar);
		$model->where("id=".$user["id"])->save($entity);

		return array("avatar"=>$this->DoUrlHandle($avatar));
	}
	//更新用户人脸识别照片
	public function updateface(){
		$user=$this->AuthUserInfo;
		$d['face']=I('post.face');
		$map=array('userid'=>$user['id']);
		D('user_profile')->where($map)->save($d);
		return;
	}
	//更新用户资质
	public function updateuserprofile(){
		$user = $this->AuthUserInfo;

		$profile = $user["profile"];

		// $endtime = strtotime("+3 month", strtotime($profile["updatetime"]));
		// $begintime = time();
		// if($profile["status"] == 1 && $begintime < $endtime){
		// 	E("通过审核的认证资料3个月内无法进行修改，操作失败");
		// }
		
		$roles = $user["role"];

		$data = $_POST;
		
		$data['papers'] = json_decode($data['papers'],true);
        
		//人脸识别照片
		$face=$data['face'];
		if(empty($face)){
			E("请上传人脸识别照片");
		}
		//身份证照片类型 0大陆身份证 1港澳台身份证 2其他
		$idcard_type=$data['idcard_type']?:0;
		//身份证照片
		$idcard_image=$data['idcard_image'];
		if(empty($idcard_image)){
			E("请上传身份证照片");
		}
		
		//体检表照片
		$test_image=$data['test_image'];
		if(empty($test_image)){
			E("请上传体检表照片");
		}
		
		D('user_profile')->where('userid='.$user['id'])->save(array('face'=>$face,'idcard_image'=>$idcard_image,'idcard_type'=>$idcard_type,'test_image'=>$test_image));
		
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
		//家护师
		if(in_array(3, $roles)){
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
		//家护师,康复师
		if(in_array(3, $roles) || in_array(4, $roles)){
			//语言
			$language = $data["language"];
			if(empty($language)){
				E("请选择语言");
			}
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
		}
		/* if(empty($papers["type_2"]["begintime"]) || empty($papers["type_2"]["validtime"])){
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
		$entity = array(
			"realname"=>$realname, "gender"=>$gender, "birth"=>$birth, "idcard"=>$idcard, "height"=>$height, "weight"=>$weight, "mobile"=>$mobile,
			"major_level"=>getIntValue($major_level), "province"=>$province, "city"=>$city, "region"=>$region, "resid"=>getIntValue($resid),
			"language"=>$language, "education"=>getIntValue($education), "work_year"=>getIntValue($work_year), "position"=>$position,
			"department"=>$department, "major"=>$major, "intro"=>$intro, "status"=>2, "updatetime"=>date("Y-m-d H:i:s")
		);
		$map = array("userid"=>$user["id"]);
		$usermodel->where($map)->save($entity);

		//用户专业信息
		$papersmodel = D("user_papers");
		
		$time = date("Y-m-d H:i:s");


		//健康证
		//$papers["type_2"]["images"]=$this->DelUrlListHandle($papers["type_2"]["images"]);
		$entity = array(
			"userid"=>$user["id"], "type"=>2, "status"=>0, "name"=>"健康证", "images"=>$papers["type_2"]["images"],
			"begintime"=>$papers["type_2"]["begintime"], "validtime"=>$papers["type_2"]["validtime"],
			"updatetime"=>$time
		);
		$map = array("userid"=>$user["id"], "type"=>2);
		$checkpaper = $papersmodel->where($map)->find();
		if(empty($checkpaper)){
			$entity['createdate']=$time;
			$papersmodel->add($entity);
		} else{
			if($entity["images"] != $checkpaper["images"] || $entity["begintime"] != $checkpaper["begintime"] || $entity["validtime"] != $checkpaper["validtime"]){
				$papersmodel->where($map)->save($entity);
			}
		}
		
		//学历
		$entity = array(
			"userid"=>$user["id"], "type"=>3, "status"=>0, "name"=>$papers["type_3"]["name"], "images"=>$papers["type_3"]["images"],
			"begintime"=>$papers["type_3"]["begintime"], "validtime"=>$papers["type_3"]["validtime"],
			"updatetime"=>$time
		);
		$map = array("userid"=>$user["id"], "type"=>3);
		$checkpaper = $papersmodel->where($map)->find();
		if(empty($checkpaper)){
			$entity['createdate']=$time;
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
	
	//密码修改
	public function changepassword(){
		$user = $this->AuthUserInfo;

		$new_password = I("post.new_password");
		if(empty($new_password)){
			E("请输入新的密码");
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
	
	//设置消息已读
	public function readmessage(){
		$user = $this->AuthUserInfo;

		$id = I("post.id", 0);
		if(empty($id)){
			E("请选择消息");
		}

		$model = D("user_message");

		$map = array("hybrid"=>"service", "userid"=>$user["id"], "id"=>$id);
		$entity  =array("status"=>1);
		$model->where($map)->save($entity);

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

		$map = array("hybrid"=>"service", "userid"=>$user["id"], "id"=>$id);
		$model->where($map)->delete();

		return;
	}

	//清空消息
	public function clearmessage(){
		$user = $this->AuthUserInfo;

		$model = D("user_message");

		//消息类型（0=系统，1=订单）
		$type = I("get.type");

		$map = array("hybrid"=>"service", "userid"=>$user["id"]);
		if(in_array($type, [0,1])){
			$map["type"] = $type;
		}
		$model->where($map)->delete();
		
		return;
	}
	
}