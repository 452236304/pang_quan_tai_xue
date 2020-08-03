<?php
namespace Admin\Controller;
use Think\Controller;
class UserCareController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("user_care");
        $userid = I("get.userid", 0);
        $status = I("get.status", -1);
        $param = $this->getMap();
        $map = array();
        if ($userid > 0) {
            $map['userid'] = $userid;
        }
        if($status > -1){
            $map['status'] = $status;
        }
        if($param["keyword"]){
            $where["name"] = array("like","%".$param["keyword"]."%");
            $where["identification"] = array("like","%".$param["keyword"]."%");
            $where["_logic"] = "or";
            $map["_complex"] = $where;
        }
        $data = $model->where($map)->order("id ASC")->select();
		foreach($data as $k=>$v){
			//护理等级判断
			switch($v['level']){
				case 1:
					$v['level']='半护理';
					break;
				case 2:
					$v['level']='全护理';
					break;
				case 3:
					$v['level']='特重护理';
					break;
				default:
					$v['level']='未知';
			}
			$data[$k]=$v;
		}
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
        $model = D("user_care");
        $data["info"] = $model->find($id);
		
		//健康 部分数据处理
		$health=json_decode($data['info']['health'],true);
		$data['info']['action']=$health['action'];
		$data['info']['eat']=$health['eat'];
		$data['info']['clothes']=$health['clothes'];
		$data['info']['wash']=$health['wash'];
		$data['info']['shit']=$health['shit'];
		$data['info']['urine']=$health['urine'];
		$data['info']['urine_piping']=$health['urine_piping'];
		$data['info']['stomach_piping']=$health['stomach_piping'];
		$data['info']['fistula_tube']=$health['fistula_tube'];
		$data['info']['pressure']=$health['pressure'];
		$data['info']['tracheotomy']=$health['tracheotomy'];
		$data['info']['realize']=$health['realize'];
		$data['info']['dementia']=$health['dementia'];
		$data['info']['deathbed']=$health['deathbed'];
		
		//护理等级判断
		switch($data['info']['level']){
			case 1:
				$data['info']['level']='半护理';
				break;
			case 2:
				$data['info']['level']='全护理';
				break;
			case 3:
				$data['info']['level']='特重护理';
				break;
			default:
				$data['info']['level']='未知';
		}
		
        if($doinfo == "modify"){
            //$d["userid"] = I("get.userid", 0);
            $d["status"] = I("post.status", 0);
            $d["name"] = I("post.name");
            $d["gender"] = I("post.gender");
            $d["birth"] = I("post.birth");
            $d["height"] = I("post.height", 0);
            $d["weight"] = I("post.weight");
            $d["care"] = I("post.care");
            $d["geo"] = I("post.geo");
            $d["province"] = I("post.province");
            $d["city"] = I("post.city");
            $d["region"] = I("post.region");
            $d["address"] = I("post.address");
            $d["identification"] = I("post.identification");
            $d["identification_type"] = I("post.identification_type");
            $d["images"] = I("post.images");
            $d["remark"] = I("post.remark");
            $d["is_default"] = I("post.is_default", 0);
            $d["updatetime"] = date("Y-m-d H:i");

            if($id == 0){
                $d["createdate"] = date("Y-m-d H:i");
            }

            if($id > 0){
                $model->where("id=".$id)->save($d);
            }else{
                //$model->add($d);
            }
            
            $this->redirect("UserCare/listad", $this->getMap());
        }

        $this->assign($data);
        $this->assign("map", $this->getMap());
    	$this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("user_care");
    	$id = I("get.id");
    	$model->delete($id);
    	$this->redirect("UserCare/listad", $this->getMap());
    }

    public function getMap(){
        $userid = I("get.userid");
        $status = I("get.status");
        $keyword = I("post.keyword");
        if(empty($keyword)){
            $keyword = mb_convert_encoding($_GET["keyword"], "UTF-8", "gb2312");
        }
        $map = array("userid"=>$userid,'keyword'=>$keyword,'status'=>$status);
        return $map;
    }
}