<?php
namespace Admin\Controller;
use Think\Controller;
class ServiceProjectDepositPriceController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("service_project_deposit_price");

        $projectmodel = D("service_project");
        $projectid = I("get.projectid", 0);
        $project = $projectmodel->find($projectid);
        if(empty($project)){
            $this->error("服务项目不存在");
        }
        if(!($project["time_type"] == 3 && $project["assess"] == 1)){
            $this->error("服务项目时长类型必须是月且为线下评估");
        }

        $data = [];

        $map = array("projectid"=>$projectid);
		$data = $model->where($map)->select();
		if(empty($data)){
            for($i=1;$i<=6;$i++){
                $data[] = array(
                    "id"=>0, "projectid"=>$projectid, "month"=>$i,
                    "price"=>0, "one_price"=>0, "two_price"=>0, "three_price"=>0,
                    "four_price"=>0, "five_price"=>0,
                    "remark"=>"", "createdate"=>date("Y-m-d H:i:s")
                );
            }
		}
        $this->assign("data", $data);
        $this->assign("map", $this->getMap());
        $this->show();
    }

    public function updatedeposit(){
        $projectmodel = D("service_project");
        $param = $this->getMap();
        $projectid = $param["projectid"];
        $project = $projectmodel->find($projectid);
        if(empty($project)){
            $this->error("服务项目不存在");
        }
        if($project["assess"] != 1){
            $this->error("服务项目必须为线下评估");
        }

        $id = I("post.id");
        $month = I("post.month");
        $price = I("post.price", 0);
        $one_price = I("post.one_price", 0);
        $two_price = I("post.two_price", 0);
        $three_price = I("post.three_price", 0);
        $four_price = I("post.four_price", 0);
        $five_price = I("post.five_price", 0);
        $remark = I("post.remark");

        if(count($id) > 0){
            $model = D("service_project_deposit_price");
            foreach($id as $k=>$v){
                $entity = array(
                    "projectid"=>$projectid, "month"=>$month[$k], "price"=>$price[$k], "one_price"=>$one_price[$k], "two_price"=>$two_price[$k],
                    "three_price"=>$three_price[$k], "four_price"=>$four_price[$k], "five_price"=>$five_price[$k],
                    "remark"=>$remark[$k], "createdate"=>date("Y-m-d H:i:s")
                );

                $map = array("projectid"=>$projectid, "month"=>$month[$k]);
                $check = $model->where($map)->find();
                if($check){
                    $model->where($map)->save($entity);
                } else{
                    $model->add($entity);
                }
            }

            $redirect_url = "ServiceProjectDepositPrice/listad";
            if(count($id) == 1){
                $redirect_url = "ServiceProjectDepositPrice/singlead";
            }

            $this->redirect($redirect_url, $param);
            exit();
        } else{
            $this->error("没有进行任何操作");
            exit();
        }
    }

    /**
     * [listad]
     * @return [type] [description]
     */
    public function singlead(){
        $model = D("service_project_deposit_price");

        $projectmodel = D("service_project");
        $projectid = I("get.projectid", 0);
        $project = $projectmodel->find($projectid);
        if(empty($project)){
            $this->error("服务项目不存在");
        }
        if($project["assess"] != 1){
            $this->error("服务项目必须为线下评估");
        }

        $data = [];

        $map = array("projectid"=>$projectid);
		$data = $model->where($map)->select();
		if(empty($data)){
            $data[] = array(
                "id"=>0, "projectid"=>$projectid, "month"=>0,
                "price"=>0, "one_price"=>0, "two_price"=>0, "three_price"=>0,
                "four_price"=>0, "five_price"=>0,
                "remark"=>"", "createdate"=>date("Y-m-d H:i:s")
            );
		}
        $this->assign("data", $data);
        $this->assign("map", $this->getMap());
        $this->show();
    }

    public function getMap(){
        $projectid = I("get.projectid");
        $map = array("projectid"=>$projectid);
        return $map;
    }
}