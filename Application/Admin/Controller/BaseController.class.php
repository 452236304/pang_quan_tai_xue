<?php
namespace Admin\Controller;
use Think\Controller;
class BaseController extends Controller {
	
	public function __construct(){
		header("content-type:text/html; charset=utf-8");
		parent::__construct();
        $res = $this->checkpower(CONTROLLER_NAME."/".ACTION_NAME);
		if (!$res) {
			redirect(U('Index/login'));
			exit();
		}
	}
	
    /*
     * pager	公用分页列表控制器	
	 * @param	$model		string	数据模型名称
	 * @param	$pagenum	int		当前页
	 * @param	$orderby	string	排序字段
	 * @param	$map		string	查询条件
	 * @return	$data		array	返回记录集
     */
	public function pager($model,$pagenum,$orderby,$map="",$param=""){
		if(is_string($model)){
			$m = D($model);
			$count = $m->where($map)->count();
		} else{
			$m = $model["mo"];
			$count = $model["count"];
		}
		$page = new \Think\Page($count,$pagenum,$param);
		$data["data"] = $m->where($map)->order($orderby)->limit($page->firstRow,$page->listRows)->select();
		foreach($param as $key=>$val) {
            $Page->parameter[$key] = urlencode($val);
		}
		$page->setConfig("theme","%FIRST% %LINK_PAGE% %END%");
		$data["pageshow"] = $page->show();
		return $data;
	}

	/*
     * checkpower	检测操作权限	
	 * @param	$power		string	待检测权限项
	 * @return	$res		bool	返回True或False
     */
	public function checkpower($power){
		$expowerlist = array(
			'9001' => 'Public/verify',
			'9002' => 'Index/login',
			'9003' => 'Index/logout',
			);
		$sessionpower = array(
			'9004' => 'Index/index',
			);
		$sysitem = D("sysitem");
		$si = $sysitem->getOneByUrl($power);
		if(array_search($power,$sessionpower) && empty($_SESSION["manID"])){
			return false;
		}else if(!$si){
			return true;
		}else{
			if(array_search($power,$expowerlist)){
				return true;
			}else if(empty($_SESSION["manID"])){
				return false;
			}else{
				$sysuser = D("sysuser");
				$userinfo = $sysuser->getOneById($_SESSION["manID"]);
				if($userinfo["types"]==0){
					return true;
				}else{
					$classinfo=D('sysuserclass')->find($userinfo['typeid']);
					$userpower = explode(",", $classinfo["power"]);
					if(in_array($si["id"], $userpower)){
						return true;
					}else{
						return false;
					}
				}
			}
		}
		
	}

	/*
     * uploadfile	公共表单上传文件
	 * @return	$info		array	返回上传文件信息
     */
	public function uploadfile($dir, $exts=array('pdf',"jpg","jpeg","png")){
		$upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     524288000 ;// 设置附件上传大小
        $upload->exts      =     $exts;// 设置附件上传类型
        $upload->autoSub  =      false; // 自动子目录
        $upload->rootPath  =      "./upload/"; // 设置附件根目录
        $upload->savePath  =      './'.$dir.'/'; // 设置附件上传目录
        $info   =   $upload->upload();
        return $info;
	}

	/*
     * powerlist	权限列表
	 * @return	$list		array	返回权限列表信息
     */
	public function powerlist(){
		return array(
			'1'	=>	'Sysuser/listuser',
			'2'	=>	'Sysuser/modifyuser',
			'3'	=>	'Sysuser/deluser',
			'4'	=>	'Sysuser/poweruser',
			'5'	=>	'Sysuser/listuserclass',
			'6'	=>	'Sysuser/modifyuserclass',
			'7'	=>	'Sysuser/listsysitem',
			'8'	=>	'Sysuser/modifypassword',
			'9'	=>	'Sysuser/sysconfig',
			);
	}

    protected function ajaxError($message, $code = 0){
        header("HTTP/1.1 400 Error");
        $this->ajaxReturn(array("message"=>$message, "code"=>$code), "json");
    }

    //检查绑定用户角色
    protected function BindUserRole($userid, $role){
        //用户角色表
        $model = D("user_role");

        $add = array(
            "userid"=>$userid, "status"=>1, "role"=>$role,
            "remark"=>"", "createdate"=>date("Y-m-d H:i:s")
        );
        $map = array("userid"=>$userid, "role"=>$role);
        $checkrole = $model->where($map)->find();
        if($checkrole){
            return;
        }

        $model->add($add);
    }

    //通过申请退款
    protected function pass($data){
	    $orderid = $data['orderid'];
        $refund_money = $data['refund_money'];
        $ordertype = $data['ordertype'];

        switch($ordertype){
            case 1: //商城订单
                $model = D('product_order');
                break;
            case 2: //机构订单
                $model = D('org_order');
                break;
            case 3: //服务订单
                $model = D('service_order');
                break;
            default:
                alert_back("订单类型有误，操作失败");
            return false;
        }

        $order = $model->where('id='.$orderid)->find();
        if(empty($order)){
            alert_back("订单不存在，操作失败");
        }
        $is = 0;
        //微信支付/支付宝 原路返还订单金额
        if($order["amount"] > 0 && $order["logid"] > 0){
            $paylog = D("pay_log")->find($order["logid"]);
            if($paylog && $paylog["isonline"] == 1 && $paylog["ispaid"] == 1){
                $data = array(
                    "out_trade_no"=>($paylog["type"]."_".$paylog["sn"]), "refund_reason"=>"",
                    "total_fee"=>$paylog["amount"], "refund_fee"=>$refund_money, "hybrid"=>$paylog['hybrid']
                );
                switch($order["pay_type"]){
                    case 1: //微信退款
                        $wxmodel = D("Payment/WxRefund");
                        $result = $wxmodel->WxPayRefund($data);
                        break;
                    case 2: //支付宝退款
                        $alimodel = D("Payment/AlipayRefund");
                        $result = $alimodel->AliPayRefund($data);
                        break;
                }
                //退款失败
                if(is_string($result)){
                    alert_back($result);
                }
                $r_order = array("status"=>6);
                $model->where("id=".$order["id"])->save($r_order);
                $is = 1;
            }
        }
        if ($is != 1) {
            alert_back('退款失败');
        }

        return ;
    }
}