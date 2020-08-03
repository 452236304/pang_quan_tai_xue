<?php
namespace CApi\Controller;
use Think\Controller;
class CommonController extends BaseController {
	
	//关于我们（id：1=关于保椿,2=平台服务协议,3=上门服务协议,5=服务操作要求与规范,6=家政服务意外保险赠送协议）
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
		$info=$model->where('type=0')->order('ordernum asc')->select();
		foreach($info as $k=>$v){
			$v['thumb']=$this->DoUrlHandle($v['thumb']);
			$info[$k]=$v;
		}
		$count=$model->count();
		return array('info'=>$info,$count);
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

    //上传文件
	public function upload(){
		$folder = I("post.folder");
		if(empty($folder)){
			$folder = "files";
		}
		$exts = array("pptx","ppt","pdf","jpg","jpeg","png","xls","xlsx","doc","zip","rar","7z","txt");
		$files = $this->ImageUpload("files", $folder, $exts);
		if(!$files){
			E("上传失败，请重新尝试");
		}

		$files = $this->DoUrlHandle('/upload/'.$folder.'/'.$files);
		
		return array('link'=>$files);
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
	
	//获取所有后台设置的保险名称
	public function insurance(){
		$data=I('get.');
		$map['type']=2;
		$map['status']=1;
		$id=$data['id'];
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
	
	//更新服务表单数据
	public function updateform(){

		$time = date("Y-m-d H:i:s");

		$list = [
			array("category"=>"环境清洁与安全检查", "title"=>"房间清洁", "source"=>"自营", "createdate"=>$time),
			array("category"=>"环境清洁与安全检查", "title"=>"用具清洁", "source"=>"自营", "createdate"=>$time),
			array("category"=>"环境清洁与安全检查", "title"=>"设施检查", "source"=>"自营", "createdate"=>$time),
			array("category"=>"环境清洁与安全检查", "title"=>"康乐活动", "source"=>"自营", "createdate"=>$time),

			array("category"=>"生活护理", "title"=>"床铺整洁", "source"=>"自营", "createdate"=>$time),
			array("category"=>"生活护理", "title"=>"助行检查", "source"=>"自营", "createdate"=>$time),
			array("category"=>"生活护理", "title"=>"协助移位", "source"=>"自营", "createdate"=>$time),
			array("category"=>"生活护理", "title"=>"穿衣更衣", "source"=>"自营", "createdate"=>$time),
			array("category"=>"生活护理", "title"=>"面口清洁", "source"=>"自营", "createdate"=>$time),
			array("category"=>"生活护理", "title"=>"助浴护理", "source"=>"自营", "createdate"=>$time),
			array("category"=>"生活护理", "title"=>"头部清洁", "source"=>"自营", "createdate"=>$time),
			array("category"=>"生活护理", "title"=>"剃须护理", "source"=>"自营", "createdate"=>$time),
			array("category"=>"生活护理", "title"=>"理发护理", "source"=>"自营", "createdate"=>$time),
			array("category"=>"生活护理", "title"=>"阴肛护理", "source"=>"自营", "createdate"=>$time),
			array("category"=>"生活护理", "title"=>"手足清洁", "source"=>"自营", "createdate"=>$time),
			array("category"=>"生活护理", "title"=>"睡眠护理", "source"=>"自营", "createdate"=>$time),

			array("category"=>"协助进食/给药", "title"=>"管饲进食", "source"=>"自营", "createdate"=>$time),
			array("category"=>"协助进食/给药", "title"=>"喂饭进食", "source"=>"自营", "createdate"=>$time),
			array("category"=>"协助进食/给药", "title"=>"口服给药", "source"=>"自营", "createdate"=>$time),

			array("category"=>"卧位护理", "title"=>"更换体位", "source"=>"自营", "createdate"=>$time),
			array("category"=>"卧位护理", "title"=>"拍背按摩", "source"=>"自营", "createdate"=>$time),
			array("category"=>"卧位护理", "title"=>"协助运动", "source"=>"自营", "createdate"=>$time),
			array("category"=>"卧位护理", "title"=>"压疮防护", "source"=>"自营", "createdate"=>$time),

			array("category"=>"排泄护理", "title"=>"更换尿裤", "source"=>"自营", "createdate"=>$time),
			array("category"=>"排泄护理", "title"=>"大小便情况", "source"=>"自营", "createdate"=>$time),
			array("category"=>"排泄护理", "title"=>"尿管护理", "source"=>"自营", "createdate"=>$time),
			array("category"=>"排泄护理", "title"=>"导尿护理", "source"=>"自营", "createdate"=>$time),
			array("category"=>"排泄护理", "title"=>"排泄训练", "source"=>"自营", "createdate"=>$time),
			array("category"=>"排泄护理", "title"=>"便秘护理", "source"=>"自营", "createdate"=>$time),
			array("category"=>"排泄护理", "title"=>"造瘘口护理", "source"=>"自营", "createdate"=>$time),

			array("category"=>"心里慰藉", "title"=>"心理关怀", "source"=>"自营", "createdate"=>$time),
		];

		$model = D("service_form");

		foreach($list as $k=>$v){
			$map = array("category"=>$v["category"], "title"=>$v["title"]);
			$check = $model->where($map)->find();
			if($check){
				$map = array("id"=>$check["id"]);
				$model->where($map)->save($v);
			} else{
				$model->add($v);
			}
		}

		return;
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
		
		$user = D('user')->where('id='.$user_id)->find();
		$invitation = D('user_invitation')->where('id=1')->find();
		$invitation['images'] = $this->DoUrlListHandle($invitation['images']);
		$invitation['referral_code'] = $user['referral_code'];
		$invitation['nickname'] = $user['nickname'];

		return $invitation;
	}

	//获取最新系统消息
	public function system_msg(){
		$map=array();
		$map['status']=1;
		$map['hybrid']='client';
		$order='updatetime desc';
		$detail=D('system_message')->field('id,title,content')->where($map)->order($order)->find();
		if(empty($detail)){
			$detail['id']=0;
			$detail['title']='';
			$detail['content']='';
		}
		return $detail;
	}
	
	//添加未读消息
	public function add_unread(){
		$data=I('get.');
		$user_id=$data['userid'];
		$unread=D('unread')->where('user_id='.$user_id)->find();
		if($unread){
			D('unread')->where('user_id='.$user_id)->setInc('count',1);
		}else{
			$save=array('user_id'=>$user_id,'count'=>1);
			D('unread')->add($save);
		}
		return;
	}

	//查看未读消息数量
	public function unread(){
		if($this->UserAuthCheckLogin()){
			$user = $this->AuthUserInfo;
			$info = D('unread')->where('user_id='.$user['id'])->find();
			$return['count'] = $info['count']?:0;
			if($return['count']>99){
				$return['count']='99+';
			}
		} else{
			$return['count'] = 0;
		}
		return $return;
	}

	//清空未读消息
	public function clear_unread(){
		if($this->UserAuthCheckLogin()){
			$user = $this->AuthUserInfo;
			$info=D('unread')->where('user_id='.$user['id'])->find();
			if($info){
				D('unread')->where('user_id='.$user['id'])->save(array('count'=>0));
			}
		}
		return;
	}

	//个人中心单广告图
	public function my_banner(){
		$info=D('banner')->field('image,link,param')->where('id=50')->find();
		$info['image']=$this->DoUrlHandle($info['image']);
		return $info;
	}

	//家护师底部广告图
	public function service_banner(){
		$bannermodel = D("banner");
		$map = array("status"=>1, "type"=>4);
		$list = $bannermodel->where($map)->order("ordernum asc")->select();
		foreach ($list as $k=>$v) {
			$v["image"] = $this->DoUrlHandle($v["image"]);
            
            if($v["param"]){
                $v["param"] = json_decode($v["param"], true);
            } else{
                $v["param"] = array("param_type"=>"-1", "param_id"=>"");
            }

			$list[$k] = $v;
		}

		return $list;
	}
	public function platform(){
		$platform=$this->GetHttpHeader('platform');
		/* if($platform=='xcx'){
			$off=F('off');
			$article['off']=$off['off'];
		}else{
			$article['off']='1';
		} */
		
		$offconfig=F('offconfig');
		return $offconfig;
	} 
	public function testsms(){
		//短信通知抢单成功
		$user_info=D('user_profile')->where(array('userid'=>185))->find();
		$info=array(
			"mobile"=>$user_info['mobile'],"name"=>'姓名',"title"=>'七天照护',
			"address"=>'地点','time'=>date('Y-m-d H:i:s')
		);
		$resutl=D('Common/RequestSms')->SendServiceBattleSms($info);
		return $result;
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
	//获取小程序用户手机号码
	public function wxmobile(){
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
			return $wxuser;
		}
	
		E("获取微信用户信息失败，请重新尝试");
	}
	public function send_igetui(){
		$clientid=I('get.clientid','86e28b3ae73cc5cfec7441ceb3d73937');
		$system=I('get.system','android');
		$title='测试';
		$text='内容';
		$push=D("Common/IGeTuiMessagePush");
		$push->setHybrid('service');
		$rs=$push->PushMessageToSingle($clientid,$system,$title,$text,json_encode($ext=array('type'=>1,'id'=>111)));
		return $rs;
	}
	public function age_test(){
		return array('info'=>getAgeMonth('1946-01-16'));
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
	//百度身份证识别判断是否满50岁 
	public function bdidcard_five(){
		$idcard = I('post.idcard');
		if(empty($idcard)){
			E('请上传身份证照片');
		}
		
		$base64 = imgtobase64($idcard);
		
		$type = 'front';
	    $model = D("Common/BdOcr");
	
	    $data = $model->IdcardMatch($base64['content'], $type);
		
		$birth = $data['words_result']['出生']['words'];
		$birth = getAge(date('Y-m-d',strtotime($birth)));
		
		if($birth<50){
			//未满50岁返回CODE789
			return array('code'=>789);
		}
		
	    return $data;
	}
	
	//获取小程序码
	public function xcx_code(){
		//$user = $this->AuthUserInfo; //$user['referral_code'];
		$ext=I('get.');
		//$ext=['page'=>'pages/unLogin/unLogin','scene'=>$user['referral_code']];
		$code=D('XcxHandle')->GetXcxCode($ext);
		$code = $this->DoUrlHandle($code);
		return array('link'=>$code);
	}
	//省市区
	public function address(){
		$address = D('city')->select();
		foreach($address as $k=>&$v){
			$map = array('city_id'=>$v['id']);
			$v['district'] = D('district')->where($map)->select();
		}
		return $address;
	}
	//提现测试(微信)
	public function withdraw_wx(){
		$data = array('ordersn'=>$this->BuildOrderSN(),'openid'=>'oHp1fuOU26Ey-YvvI2Eh1GWqxqDE','desc'=>'佣金提现测试');
		$wxmodel = D("Payment/WxJsApi");
		$parameter = $wxmodel->WxPayWithdraw($data);
		return $parameter;
	}
	//提现测试(支付宝)
	public function withdraw_ali(){
		$data = array(
			"ordersn"=>$this->BuildOrderSN(), "pay_type"=>'ALIPAY_LOGONID',
			"account"=>'1870864084@qq.com', "amount"=>0.1,
			'show_name'=>'一点椿','real_name'=>'梁昊','remark'=>'测试', 
		);
		$alimodel = D("Payment/AlipayTransfer");
		$parameter = $alimodel->AlipayTransfer($data);
		//echo $parameter;exit;
		//var_dump($parameter);
		return array('response'=>$parameter);
	}
	
	//分销记录(测试)
	public function brokerage(){
		$where = array();
		$start_time = date('Y-m-d', strtotime('-30 day'));
		$where['createdate'] = ['egt', $start_time];
		$id = I('get.id');
		$log = D('Brokerage', 'Service')->history($id, $where);
		return $log;
	}
	public function record_log(){
		$record_log = F('record_log');
		$record_log .= '\n\r'.I('get.name').':'.I('get.log');
		F('record_log',$record_log);
		return ;
	}
	
	//记录小程序码参数
	public function save_xcx_code(){
		$scene = htmlspecialchars_decode(I('post.scene'));
		if(empty($scene)){
			E('参数不能为空');
		}
		$is_set = D('xcx_code')->where(array('scene'=>$scene))->find();
		if($is_set){
			return array('id'=>$is_set['id']);
		}
		$id = D('xcx_code')->add(array('scene'=>$scene));
		return array('id'=>$id);
	}
	//读取小程序码参数
	public function load_xcx_code(){
		$id = I('get.id');
		if(empty($id)){
			E('id不能为空');
		}
		$xcx_code = D('xcx_code')->where(array('id'=>$id))->find();
		return $xcx_code;
	}
	public function demo(){
		var_dump(D('PointLog','Service')->check_tag('258','complate_usercare1'));
	}
}