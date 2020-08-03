<?php
namespace Store\Controller;
use Think\Controller;
class SysuserController extends BaseController {
    public function listuser(){
			$channel = $_GET["channel"];
			$this->assign("channel",$channel);
			$sysuser = D("SysuserStore");
			//$data["userlist"] = $sysuser->relation(true)->select();
			$data["userlist"] = $sysuser->field("u.*,uc.title as classname,uc.power")->table("sj_sysuser_store u")->join("sj_sysuserclass_store uc")->where("u.typeid=uc.id")->select();

			$this->assign($data);
			$this->show();
    }

    public function modifyuser(){
    	$userclassmodel = D("Sysuserclass_store");
		$data["userclass"] = $userclassmodel->select();

		$id = I("get.id");
		$sysuser = D("SysuserStore");
		$data["userinfo"] = $sysuser->find($id);

		$doinfo = $_GET["doinfo"];
		if($doinfo=="modifyuser"){
			$sdata["typeid"] = I("post.typeid");
			$sdata["truename"] = I("post.truename");
			$sdata["username"] = I("post.username");
			$sdata["ispass"] = I("post.ispass");
			$sdata["uptime"] = time();
			$password = I("post.password");
			$repassword = I("post.repassword");
			if($sdata["typeid"]==""){
				echo "<script>alert(\"请选择用户组\");location.href='".U('Sysuser/modifyuser','id='.$id)."';</script>";
				exit();
			}elseif($sdata["username"]==""){
				echo "<script>alert(\"用户名不能为空\");location.href='".U('Sysuser/modifyuser','id='.$id)."';</script>";
				exit();
			}
			if($password!="" && $password==$repassword){
				$sdata["password"] = md5($password);
			}

			$map = array("username"=>$sdata["username"], "id"=>array("neq", $id));
			$checkusername = $sysuser->where($map)->find();
			if($checkusername){
				$this->error("用户名已存在，请重新输入");
			}

			if(empty($id)){
				$sysuser->add($sdata);
			}else{
				$sysuser->where("id=$id")->save($sdata);
			}
			echo "<script>alert(\"操作成功\");location.href='".U('Sysuser/listuser')."';</script>";
			exit();
		}
		$this->assign($data);
    	$this->show();
    }

    public function deluser(){
    	$id = I("get.id");
    	$sysuser = D("SysuserStore");
    	$sysuser->delete($id);
    	redirect(U("Sysuser/listuser"));
    }

    public function poweruser(){
    	$id = I("get.id");
			$doinfo = I("get.doinfo");
			$sysuser = D("SysuserStore");

		if($doinfo=="poweruser"){
			$power = implode(",",I("post.power"));
			if($power==""){
				$sdata["power"]="0";
			}else{
				$sdata["power"]="0,".$power;
			}
			$sysuser->where("id=$id")->save($sdata);
		}

		
		//$userInfo = $sysuser->relation(true)->find($id);
		$data["userInfo"] = $sysuser->field("u.*,uc.title as classname,uc.power as cpower")->table("sj_sysuser_store u")->join("sj_sysuserclass_store uc")->where("u.typeid=uc.id and u.id=$id")->find();

		$sysitem = D("SysitemStore");
		$data["sysitem"] = $sysitem->where("id in(".$data["userInfo"]["cpower"].")")->order("id asc")->select();
		$this->assign($data);
		
		$this->show();
    }

    public function listuserclass(){
    	$sysuserclass = D("Sysuserclass_store");
		$data["userclass"] = $sysuserclass->select();
		
		$this->assign($data);
		$this->show();
    }

    public function modifyuserclass(){
    	$id = I("get.id");
		$doinfo = I("get.doinfo");
		$sysuserclass = D("SysuserclassStore");
		$sysitem = D("SysitemStore");
		//$sysuser = D("SysuserStore");
		if($doinfo=="modify"){
			$power = implode(",",I("post.power"));
			if($power==""){
				$sdata["power"]="0";
			}else{
				$sdata["power"]="0,".$power;
			}
			if(is_numeric($id)){
				//$sysuser->saveByTypeid($id,$sdata);
				$sdata["title"] = I("post.title");
				$sysuserclass->saveOneById($id,$sdata);
			}else{
				$sdata["title"] = I("post.title");
				$id = $sysuserclass->add($sdata);
			}
		}
		if($id){
			$datainfo = $sysuserclass->getOneById($id);
			$datainfo["power"] = explode(",", $datainfo["power"]);
			$data["datainfo"] = $datainfo;
		}

		$data["sysitem"] = $sysitem->getMenuList();
		$this->assign($data);
		$this->show();
		
    }

    public function deluserclass(){
    	$id = I("get.id");
		$sysuserclass = D("Sysuserclass_store");
		$sysuserclassinfo = $sysuserclass->find($id);
		if($sysuserclassinfo["title"]!="系统管理员"){
			$sysuser = D("SysuserStore");
			$sysuserinfo = $sysuser->getByTypeid($sysuserclassinfo["id"]);
			if(count($sysuserinfo)>0){
				$this->error("该分类下有管理员存在，不可删除");
			}else{
				$sysuserclass->where("id=".$id)->delete();
				redirect(U("Sysuser/listuserclass"));
			}
		}else{
			$this->error("系统管理员分类不可删除");
		}
    }

    public function listsysitem(){
    	$sysitem = D("SysitemStore");
		$data["sysitem"] = $sysitem->getMenuList();
		$this->assign($data);
		$this->show();
	}
	
	//模块修改
	public function modifysysitem(){
		$sysitem = D("SysitemStore");
		$doinfo = I("get.doinfo");
		$id = I("get.id");
		if(is_numeric($id)){
			$data["sysitem"] = $sysitem->find($id);
		}

		if($doinfo=="modifysysitem"){
			$fdata["bid"] = I("post.bid");
			$fdata["title"] = I("post.title");
			$fdata["url"] = I("post.url","","url");
			$fdata["ismenu"] = I("post.ismenu");
			$fdata["orderby"] = I("post.orderby");
			if(is_numeric($id)){
			//资料修改
				$ppdata = $sysitem->where("id=".$fdata["bid"])->find();
				if($fdata["bid"]=="0"){//上一级为一级
					$cdata = $sysitem->find($id);
					//上一级没有变化则直接更新
					if($cdata["bid"]==$fdata["bid"]){
						$sysitem->where("id=$id")->save($fdata);
						redirect(U("Sysuser/listsysitem"));
						exit();
					}else{
						//改变了上一级
						//修改后的位置在最后
						// $childdata = $sysitem->where("childstr like '".$cdata["childstr"]."%' or id=".$id)->order("orderby asc")->select();
						// $l = count($childdata);
						// $max = getMax("sysitem","orderby");
						// $oc = $max-$childdata[0]["orderby"];
						//当前资料及子级排序修改
						// $sysitem->where("id=".$cdata["bid"]." and childnum>0")->setDec("childnum",1);
						// $sysitem->where("childstr like '".$cdata["childstr"]."%' or id=".$id)->setInc("orderby",$oc);
						// $sysitem->where("childstr like '".$cdata["childstr"]."%' or id=".$id)->setDec("depth",$cdata["depth"]);
						// $sysitem->where("orderby>=".$cdata["orderby"])->setDec("orderby",$l);

						// $sql = "update __PREFIX__sysitem set childstr=replace(childstr,'".$cdata["childstr"]."',',".$cdata["id"].",'),rootid=$id where childstr like '".$cdata["childstr"]."%' or id=".$id;
						// $sysitem->execute($sql);
						$udata["bid"] = 0;
						$sysitem->where("id=".$id)->save($udata);

						redirect(U("Sysuser/listsysitem"));
						exit();
					}
				}else{
					$sysitem->where("id=$id")->save($fdata);
					redirect(U("Sysuser/listsysitem"));

					// $cdata = $sysitem->find($id);
					// if($cdata["bid"]==$fdata["bid"]){
					// 	$sysitem->where("id=$id")->save($fdata);
					// 	redirect(U("Sysuser/listsysitem"));
					// 	exit();
					// }else{
					// 	$childdata = $sysitem->where("childstr like '".$cdata["childstr"]."%' and id=".$fdata["bid"])->select();
					// 	if(empty($childdata)){
					// 		$childdata = $sysitem->where("childstr like '".$cdata["childstr"]."%' or id=".$id)->order("orderby asc")->select();
					// 		$l = count($childdata);
					// 		//上级子级数量减1
					// 		$sysitem->where("id=".$cdata["bid"]." and childnum>0")->setDec("childnum",1);
					// 		//找出要插入的位置，先空出位置
					// 		$tmp = $sysitem->where("childstr like '".$ppdata["childstr"]."%' or id=".$id)->order("orderby desc")->find();
					// 		$sysitem->where("orderby>".$tmp["orderby"])->setInc("orderby",$l);
					// 		$jc = $tmp["orderby"]-$cdata["orderby"]+1;
					// 		$dc = $ppdata["depth"]-$cdata["depth"]+1;
					// 		$sysitem->where("childstr like '".$cdata["childstr"]."%' or id=".$id)->setInc("orderby",$jc);
					// 		$sysitem->where("childstr like '".$cdata["childstr"]."%' or id=".$id)->setInc("depth",$dc);
					// 		$sysitem->where("orderby>".$childdata[$l-1]["orderby"])->setDec("orderby",$l);
					// 		$sql = "update __PREFIX__sysitem set childstr=replace(childstr,'".$cdata["childstr"]."','".$ppdata["childstr"].$cdata["id"].",'),rootid=".$ppdata["rootid"]." where childstr like '".$cdata["childstr"]."%' or id=".$id;
					// 		$sysitem->execute($sql);
					// 		$udata["bid"] = $ppdata["id"];
					// 		$sysitem->where("id=".$id)->save($udata);
					// 		$sysitem->where("id=".$ppdata["id"])->setInc("childnum",1);

					// 		redirect(U("Sysuser/listsysitem"));
					// 		exit();
					// 	}else{
					// 		$this->error("操作错误，上一级不能为自己的子级");
					// 	}
					// }
				}
				
			}else{
			//添加新资料
				//添加一级项目
				if($fdata["parentid"]=="0"){
					//$fdata["orderby"] = getMax("sysitem","orderby");
					$rid = $sysitem->add($fdata);
					
					redirect(U("Sysuser/listsysitem"));
					exit();
				//添加二级项目
				}else{
					// $ppdata = $sysitem->where("id=".$fdata["bid"])->find();
					// $pdata = $sysitem->where("id=".$fdata["bid"]." or bid=".$fdata["bid"])->order("orderby desc")->find();
					// $sysitem->where("orderby>".$pdata["orderby"])->setInc("orderby",1);
					// $fdata["orderby"] = $pdata["orderby"]+1;
					$rid = $sysitem->add($fdata);
					redirect(U("Sysuser/listsysitem"));
					exit();
				}
			}
		}
		
		$sysitemdata = $sysitem->where("bid=0")->order("orderby asc")->select();
		$data["parents"] = $sysitemdata;
		// for($i=0;$i<count($sysitemdata);$i++){
		// 	$padstr = "";
		// 	for($j=1;$j<=$sysitemdata[$i]["depth"];$j++){
		// 		$padstr .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		// 	}
		// 	$data["parentOption"] .= "<option value=".$sysitemdata[$i]["id"]." ".getSelect('select',$sysitemdata[$i]["id"],$data["sysitem"]["parentid"]).">".$padstr.$sysitemdata[$i]["title"]."</option>";
		// }

		$this->assign($data);
		$this->show();
	}

	//删除模块
	public function delsysitem(){
		$id = I("get.id");
		$sysitem = D("SysitemStore");
		$cdata = $sysitem->find($id);
		if($cdata["childnum"]>0){
			$this->error("请先删除子级");
		}else{
			$sysitem->where("id=".$cdata["parentid"])->setDec("childnum",1);
			$sysitem->delete($id);
			$sysitem->where("orderby>".$cdata["orderby"])->setDec("orderby",1);
			redirect(U("Sysuser/listsysitem"));
			exit();
		}
	}

	//模块重新排序
	public function sysitemsortad(){
		$id = I("post.id");
        $ordernum = I("post.orderby");
        if(count($id)>0){
            $model = D("SysitemStore");
            foreach ($id as $key=>$val){
                $model->where("id=".$val)->setField("orderby", $ordernum[$key]);
            }
            $this->redirect("Sysuser/listsysitem");
            exit();
        }else{
            $this->assign("jumpUrl", U("Sysuser/listsysitem"));
            $this->error("没有进行任何操作");
            exit();
        }
	}
    

    public function modifypassword(){
    	$id = $_SESSION["storeID"];
    	$doinfo = I("get.doinfo");
    	$sysuser = D("SysuserStore");
    	$ass["userinfo"] = $sysuser->find($id);
    	if($doinfo=="modifypassword"){
			$data["truename"] = I("post.truename");
			$password = $_POST["password"];
			$repassword = $_POST["repassword"];
			if($password==$repassword && $password!=""){
				$data["password"] = md5($password);
				$res = $sysuser->where("id=".$_SESSION["storeID"])->save($data);
			}else{
				$res = $sysuser->where("id=".$_SESSION["storeID"])->save($data);
			}
			if($res){
				echo "<script>alert('修改成功!');location.href='".U('Sysuser/modifypassword')."';</script>";
				exit();
			}else{
				echo "<script>alert('没有任何修改!');location.href='".U('Sysuser/modifypassword')."';</script>";
				exit();
			}

		}
    	$this->assign($ass);
    	$this->show();

    }

    /*
     * sysconfig	系统配置项
     */
	public function sysconfig(){
		$doinfo = I("get.doinfo");
		$id = I("get.id");
		$config = D("Config");
		if($doinfo=="sysconfig"){
			$data["title"] = I("post.title");
			$data["keyword"] = I("post.keyword");
			$data["description"] = I("post.description");
			$data["author"] = I("post.author");
			$data["fontsize"] = I("post.fontsize");
			$data["spacetime"] = I("post.spacetime");
			$off['off']=I("post.off");
			F('off',$off);
			$sysoff['sysoff']=I("post.sysoff");
			F('sysoff',$sysoff);
			if($id==""){
				$config->add($data);
			}else{
				$data["id"] = $id;
				$config->save($data);
			}
			echo "<script>alert('修改成功!');location.href='".U('Sysuser/sysconfig')."';</script>";
			exit();
		}
		$data["datainfo"] = $config->find();
		$off=F('off');
		$this->assign('off',$off);
		$sysoff=F('sysoff');
		$this->assign('sysoff',$sysoff);
		$this->assign($data);
		$this->show();
	}



}