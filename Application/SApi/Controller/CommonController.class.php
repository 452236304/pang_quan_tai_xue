<?php
namespace SApi\Controller;
use Think\Controller;
class CommonController extends BaseController {
	
	//关于我们 （id：1=关于保椿,5=服务操作要求与规范,6=家政服务意外保险赠送协议）
	public function about(){

        $id = I("get.id", 0);
        $map = array("status"=>1);
        if($id){
			$map["id"] = $id;
			$about = D("about")->where($map)->order("id asc")->find();
			$about['content']=$this->UEditorUrlReplace($about['content']);
		} else{
			$about = D("about")->where($map)->order("id asc")->select();
			foreach($about as $k=>$v){
				$v['content']=$this->UEditorUrlReplace($v['content']);
				$about[$k]=$v;
			}
		}
        return $about;
	}
	public function guide(){
		$model=D('guide');
		$info=$model->where('type=1')->order('ordernum asc')->select();
		foreach($info as $k=>$v){
			$v['thumb']=$this->DoUrlHandle($v['thumb']);
			$info[$k]=$v;
		}
		$count=$model->count();
		return array('info'=>$info,$count);
	}
    //上传文件
	public function upload(){
		$folder = I("post.folder");
		if(empty($folder)){
			$folder = "file";
		}
		$exts = array("pptx","ppt","pdf","jpg","jpeg","png","xls","xlsx","doc","zip","rar","7z","txt",'avi','mov','rmvb','flv','mp4','3gp','MP4');
		$image = $this->ImageUpload("image", $folder, $exts);
		if(!$image){
			E("上传失败，请重新尝试");
		}

		$image = $this->DoUrlHandle('/upload/'.$folder.'/'.$image);
		
		return array('link'=>$image);
	}
	//base64上传文件
	public function base64_upload(){
		$folder = I('post.folder');
		if(empty($folder)){
			$folder = "files";
		}
		$base64_string = I('post.file');
		if(!$base64_string){
			E('请上传图片');
		}
		$filename = time().'.jpg';
		
		$base64_string = explode(',', $base64_string);
		$data = base64_decode($base64_string[1]);
		$url = "upload/".$folder."/".$filename;
		$result = file_put_contents($url, $data); //写入文件并保存
		return array('link'=>$this->DoUrlHandle('/'.$url),'result'=>$result);
	}
	//上传视频
	public function upload_video(){
		$folder = I("post.folder");
		if(empty($folder)){
			$folder = "file";
		}
		$exts = array('avi','mov','rmvb','flv','mp4','3gp','MP4');
		$image = $this->ImageUpload("files", $folder, $exts);
		if(!$image){
			E("上传失败，请重新尝试");
		}
	
		$image = $this->DoUrlHandle('/upload/'.$folder.'/'.$image);
		
		return array('link'=>$image);
    }
    
    //工作报名申请
    public function applyjob(){
        $data = I("post.");

        $name = $data["name"];
        if(empty($name)){
            E("请输入姓名");
        }
        $mobile = $data["mobile"];
        if(empty($mobile)){
            E("请输入手机号码");
        }
        if(!isMobile($mobile)){
            E("手机号码格式不正确");
        }
        $gender = $data["gender"];
        if(empty($gender)){
            E("请选择性别");
        }
        $birth = $data["birth"];
        if(empty($birth)){
            E("请输入年龄");
        }
		
        if ($birth > 55) {
            E('不可大于55岁');
        }
		
		$address = $data["address"];
		if(empty($address)){
			E("请输入现居区域");
		}
		
		$experience = $data["experience"];
		if(empty($experience)){
			E("请输入工作经历");
		}
		
		$certificates = $data["certificates"];
		if(empty($certificates)){
			E("请输入护理员证");
		}
		

        $model = D("apply_job");

        $map = array("name"=>$name, "mobile"=>$mobile);
        $check = $model->where($map)->find();
        if($check){
            E("您已提交报名申请，请耐心等候工作人员的反馈");
        }

        $entity = array(
            "status"=>1, "name"=>$name, "gender"=>$gender, "mobile"=>$mobile, "birth"=>$birth,
            "remark"=>"", "createdate"=>date("Y-m-d H:i:s"),
			'address'=>$address,'experience'=>$experience,'certificates'=>$certificates
        );

        $model->add($entity);

        return;
    }

    //记录服务订单服务人员的坐标 - 经纬度
    public function recordcoordinate(){
        if(!$this->UserAuthCheckLogin()) {
            return;
        }
        $user = $this->AuthUserInfo;

		$data = I("post.");
		
        //经度
        $longitude = $data["longitude"];
        //纬度
		$latitude = $data["latitude"];
		
        if($longitude && $latitude){
			$coordinate = [($longitude."|".$latitude."|".time())];
		} else{
			//批量坐标
			$coordinate = $data["coordinate"];
			if(empty($coordinate)){
				return;
			}
			$coordinate = explode(",", $coordinate);
		}

        if(count($coordinate) <= 0){
            return;
        }
        
        $model = D("Common/Coordinate");
        $model->recordcoordinate($user["id"], $coordinate);
        
        return;
    }

	//根据关键字查找协议
	public function service(){
		$keyword = I("get.keyword", 0);
        $map = array("status"=>1);
        if($keyword){
            $map["title"] = $keyword;
            $about = D("about")->where($map)->order("id asc")->find();
			$about['content']=$this->UEditorUrlReplace($about['content']);
        } else{
            $about = D("about")->where($map)->order("id asc")->select();
			foreach($about as $k=>$v){
				$v['content']=$this->UEditorUrlReplace($v['content']);
				$about[$k]=$v;
			}
        }
        return $about;
    }
    
	//新版本检测
	public function edition(){
		$data=I('get.');
		$system=$data['system'];
		$hybrid=$data['hybrid'];
		$ver=$data['ver'];
		$map['is_new']='1';
		$map['system']=$system;
		$map['hybrid']=$hybrid;
		$edition=D('edition')->where($map)->find();
		if($edition){
			$editionArr=explode('.',$edition['version']);
			$verArr=explode('.',$ver);
			if($editionArr[0]==$verArr[0]){
				if($editionArr[1]==$verArr[1]){
					if($editionArr[2]>$verArr[2]){
						$return['type']=1;
						$return['info']='有最新版本';
						$return['link']=$edition['link'];
					}else{
						$return['type']=0;
						$return['info']='无需更新';
					}
				}elseif($editionArr[1]>$verArr[1]){
					$return['type']=1;
					$return['info']='有最新版本';
					$return['link']=$edition['link'];
				}else{
					$return['type']=0;
					$return['info']='无需更新';
				}
			}elseif($editionArr[0]>$verArr[0]){
				$return['type']=1;
				$return['info']='有最新版本';
				$return['link']=$edition['link'];
			}else{
				$return['type']=0;
				$return['info']='无需更新';
			}
		}else{
			$return['type']=0;
			$return['info']='无需更新';
		}
		return $return;
	}

    # test begin

    //百度人脸识别识别
    private function bdface(){
        $checkface = I("post.checkface");
        if(empty($checkface)){
            E("请上传人脸识别的用户照片");
        }
    
        $model = D("Common/BdFace");
    
        $data = $model->FaceMatch($checkface, "/upload/test/test_avatar.jpg");
    
        if($data["result"] == "FAIL"){
            E($data["message"]);
        }
    
        return $data;
    }
	
	//百度身份证识别
	public function bdidcard(){
		$idcard = I('post.idcard');
		if(empty($idcard)){
			E('请上传身份证照片');
		}
		
		$base64=imgtobase64($idcard);
		
		$type = I('post.type');
		if($type!='front' && $type!='back'){
			E('请选择身份证的正反面');
		}
	    $model = D("Common/BdOcr");
	
	    $data = $model->IdcardMatch($base64['content'], $type);
	
	    return $data;
	}
	

    //华为号码隐私AXB模式
    private function hwaxb(){
        $data = I("post.");

        $action = $data["action"];
        if(empty($action)){
            E("请选择操作的方法");
        }

        $num_a = $data["num_a"];
        $num_b = $data["num_b"];
        $bind_id = $data["bind_id"];
        $relation_num = $data["relation_num"];

        $model = D("Common/HwAXB");

        switch ($action) {
            case "bind":
                $result = $model->Bind($num_a, $num_b);
                break;
            case "unbind":
                $result = $model->UnBind($bind_id);
                break;
            case "unbindall":
                $result = $model->UnBindAll($relation_num);
                break;
            case "update":
                $result = $model->Update($bind_id, $num_a, $num_b);
                break;
            case "query":
                $result = $model->Query($bind_id);
                break;
        }

        if(empty($result)){
            E("操作的方法不存在");
        }

        return $result;
	}
	
	private function checkordertime(){
		$ordermodel = D("service_order");

		$order = $ordermodel->find(296);

		$service_userid = 185;
		$begintime = $order["begintime"];
		$endtime = $order["endtime"];
		$map = array(
			"service_userid"=>$service_userid, "status"=>1, "execute_status"=>array("in", [0,1,2,3]), "admin_status"=>array("in", [0,1]),
			"_complex"=>array(
				"begintime"=>array(
					array("egt", $begintime), array("elt", $endtime), "and"
				),
				"endtime"=>array(
					array("egt", $begintime), array("elt", $endtime), "and"
				),
				"_complex"=>array(
					"begintime"=>array("elt", $begintime), "endtime"=>array("egt", $endtime)
				),
				"_logic"=>"or"
			)
		);
		$count = $ordermodel->where($map)->count();

		return array("count"=>$count, "sql"=>$ordermodel->getLastSql());
	}

    # test end

    //获取邀请海报
    public function invitation(){
    	if($this->UserAuthCheckLogin()){
    		$user_id=$this->AuthUserInfo["id"];
    	}else{
    		$user_id=I('get.id');
    	}
    	if(empty($user_id)){
    		E('缺少用户ID');
    	}
    	$user=D('user')->where('id='.$user_id)->find();
		$userprofile=D('user_profile')->where('userid='.$user_id)->find();
    	$invitation=D('user_invitation')->where('id=2')->find();
    	$invitation['images']=$this->DoUrlListHandle($invitation['images']);
    	$invitation['referral_code']=$userprofile['referral_code'];
    	$invitation['nickname']=$user['nickname'];
    	return $invitation;
    }
	
	//身份证检查
	public function is_idcard(){
		$idcard=I('get.idcard');
		if(is_idcard($idcard)){
			E('没问题');
		}else{
			E('有问题');
		}
    }
    
	//检查需要定位的订单数量
	public function ordernum(){
		if($this->UserAuthCheckLogin()){
			$user_id=$this->AuthUserInfo["id"];
		}else{
			return array('count'=>0);
		}
		$ordermodel = D("service_order");
		$map=array('service_userid'=>$user_id,'execute_status'=>array('in',array(1,2,3)));
		$list=$ordermodel->where($map)->select();
		$count=0;
		foreach($list as $k=>$v){
			if($v['type']==3 && $v['execute_time'] < date('Y-m-d H:i:s',time()-3600)){
				
			}else{
				$count++;
			}
			$list[$k]=$v;
		}
		return array('count'=>$count);
    }
	//检查短信发送时区
	public function smstimezone(){
		$order["begintime"]=date('Y-m-d');
		$order["endtime"]=date('Y-m-d');
		$title='124';
		$user_info['realname']='梁昊';
		$info=array(
			"mobile"=>'18102504782',"name"=>$user_info['realname'],"title"=>$title,
			"address"=>'123','time'=>$order["begintime"]
		);
		$result=D('Common/RequestSms')->SendServiceBattleSms($info);
		return array('info'=>$result,'date'=>date('Y-m-d H:i:s'));
	}
	//记录下载次数
	public function downloads_count(){
		$system=I('get.system');
		if(empty($system)){
			E('请选择系统');
		}
		$hybrid=I('get.hybrid');
		if(empty($hybrid)){
			E('请选择来源');
		}
		//查找最新版本
		$map=array('is_new'=>1,'hybrid'=>$hybrid,'system'=>$system);
		$edition=D('edition')->where($map)->find();
		
		//查找下载记录表是否有记录 有则增加下载次数无则添加下载记录
		$map=array('hybrid'=>$hybrid,'system'=>$system,'version'=>$edition['version']);
		$download=D('download_log')->where($map)->find();
		if($download){
			D('download_log')->where($map)->setInc('downloads_count',1);
		}else{
			$data=array('hybrid'=>$hybrid,'system'=>$system,'version'=>$edition['version'],'downloads_count'=>1);
			D('download_log')->add($data);
		}
		return;
	}
	
	public function platform(){
		$sysoff=F('sysoff');
		$article['sysoff']=$sysoff['sysoff'];
		return $article;
	} 
	
	//我的资质
	public function userprofile(){
		$user = D('user')->where(array('id'=>105))->find();
		
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
}