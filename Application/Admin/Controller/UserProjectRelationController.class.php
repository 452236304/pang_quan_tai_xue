<?php
namespace Admin\Controller;
use Think\Controller;
class UserProjectRelationController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("user_project_relation");
        $userid = I("get.userid", 0);
        $map = array("type"=>1, "userid"=>$userid);
        $data = $model->where($map)->select();

        $projectmodel = D("service_project");
        foreach($data as $k=>$v){
            $map = array("categoryid"=>$v["projectid"]);
            $project = $projectmodel->where($map)->select();

            $projectids = [];
            foreach($project as $ik=>$iv){
                $projectids[] = $iv["id"];
            }

            $child = [];
            if($projectids){
                $map = array("type"=>2, "userid"=>$userid, "projectid"=>array("in", $projectids));
                $child = $model->where($map)->select();
            }
            $v["child"] = $child;

            $data[$k] = $v;
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
        $userid = I("get.userid", 0);
    	$doinfo = I("get.doinfo");
        $model = D("user_project_relation");

        if($doinfo == "modify"){
            $projectid = I("post.projectid");
            if(count($projectid) > 0){
                $map = array("userid"=>$userid);
                $model->where($map)->delete();

                foreach($projectid as $k=>$v){
                    $v = explode("|", $v);
                    if(count($v) < 3){
                        continue;
                    }
                    $id = $v[0];
                    $type = $v[1];
                    $title = $v[2];
                    
                    $entity = array(
                        "type"=>$type, "userid"=>$userid, "projectid"=>$id,
                        "title"=>$title, "createdate"=>date("Y-m-d H:i:s")
                    );

                    $model->add($entity);
                }
            }
            
            $this->redirect("UserProjectRelation/listad", $this->getMap());
        }

        $map = array("userid"=>$userid);
        $relations = $model->where($map)->select();
        $categoryids = [];
        $projectids = [];
        foreach($relations as $k=>$v){
            if($v["type"] == 1 && !in_array($v["projectid"], $categoryids)){
                $categoryids[] = $v["projectid"];
            } else if($v["type"] == 2 && !in_array($v["projectid"], $projectids)){
                $projectids[] = $v["projectid"];
            }
        }

        //健康医疗栏目
        $categorymodel = D("service_category");
        $map = array();
        $role = I("get.role", 0);
        if($role){
            $map = array("role"=>$role);
        }
        $category = $categorymodel->where($map)->order("ordernum asc")->select();
        //服务项目
        $projectmodel = D("service_project");
        foreach($category as $k=>$v){
            if(in_array($v["id"], $categoryids)){
                $v["selected"] = 1;
            } else{
                $v["selected"] = 0;
            }

            $map = array("categoryid"=>$v["id"]);
            $project = $projectmodel->where($map)->select();
            foreach($project as $ik=>$iv){
                if(in_array($iv["id"], $projectids)){
                    $iv["selected"] = 1;
                } else{
                    $iv["selected"] = 0;
                }

                $project[$ik] = $iv;
            }

            $v["project"] = $project;

            $category[$k] = $v;
        }

        $this->assign("category", $category);
        $this->assign("map", $this->getMap());
    	$this->show();
    }

    /**
     * [delad]
     * @return [type] [description]
     */
    public function delad(){
    	$model = D("user_project_relation");
        $id = I("get.id");
        
        $data = $model->find($id);
        if($data["type"] == 1){ //删除服务栏目下的所有服务项目
            $projectmodel = D("service_project");
            $map = array("categoryid"=>$data["projectid"]);
            $project = $projectmodel->where($map)->select();
            foreach($project as $ik=>$iv){
                $projectids[] = $iv["id"];
            }
            $projectids[] = $data["projectid"];

            $map = array("userid"=>$data["userid"], "projectid"=>array("in", $projectids));
            $model->where($map)->delete();
        } else{
            $model->delete($id);
        }

    	$this->redirect("UserProjectRelation/listad", $this->getMap());
    }

    public function getMap(){
        $userid = I("get.userid");
        $role = I("get.role");
        $map = array("userid"=>$userid, "role"=>$role);
        return $map;
    }
}