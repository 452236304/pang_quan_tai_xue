<?php
namespace Admin\Controller;
use Think\Controller;
class ServiceTimeController extends BaseController {

    /**
     * [listad]
     * @return [type] [description]
     */
    public function listad(){
        $model = D("service_time");
        $param = $this->getMap();
        $map = array("projectid"=>$param["projectid"]);
        $list = $model->where($map)->order("days asc, begintime asc")->select();

        $data = array();
        for($i=1;$i<=15;$i++){
            $item = array("id"=>$i, "title"=>("第".$i."天"), "list"=>[], "count"=>0);
            foreach($list as $k=>$v){
                if($v["days"] == $i){
                    $item["list"][] = $v;
                }
            }
            $item["count"] = count($item["list"]);
            $data["days_".$i] = $item;
        }
        $this->assign("data", $data);
        $this->assign("map", $param);
        $this->show();
    }

    /**
     * [modifyad]
     * @return [type] [description]
     */
    public function modifyad(){
    	$doinfo = I("get.doinfo");
        $model = D("service_time");

        $param = $this->getMap();

        if($doinfo == "modify"){

            $projectid = $param["projectid"];
            if(empty($projectid)){
                $this->error("请选择关联的服务项目");
            }
            $days = $param["days"];
            if(empty($days)){
                $this->error("请选择所属周期");
            }

            $times = I("post.times");
            if(count($times) > 0){
                $map = array("projectid"=>$projectid, "days"=>$days);
                $model->where($map)->delete();

                $titles = I("post.titles");

                foreach($times as $k=>$v){
                    $v = explode("|", $v);
                    if(count($v) < 2){
                        continue;
                    }

                    $begin_stamp = $v[0];
                    $end_stamp = $v[1];
                    $title = "";

                    foreach($titles as $ik=>$iv){
                        $iv = explode("|", $iv);
                        if(count($iv) < 2){
                            continue;
                        }

                        $b = $iv[0];
                        $t = $iv[1];
                        if($b == $begin_stamp){
                            $title = $t;
                            break;
                        }
                    }

                    $entity = array(
                        "status"=>1, "projectid"=>$projectid, "days"=>$days, "title"=>$title,
                        "begintime"=>$this->calctime($begin_stamp, 1), "endtime"=>$this->calctime($end_stamp, 1),
                        "nervous"=>0, "price"=>0, "createdate"=>date("Y-m-d H:i:s")
                    );

                    $model->add($entity);
                }
            }
            
            $this->redirect("ServiceTime/listad", $param);
        }

        $projectmodel = D("service_project");
        $project = $projectmodel->find($param["projectid"]);
        if(empty($project)){
            $this->error("服务项目不存在");
        }
        if(!in_array($project["time_type"], [0,1])){
            $this->error("服务项目时长类型必须为分钟或者小时.".$project["time_type"]);
        }

        $map = array("projectid"=>$param["projectid"], "days"=>$param["days"]);
        $servicetime = $model->where($map)->select();

        $inteval = 0;
        if($project["time_type"] == 0){
            $inteval = $project["time"];
        } else if($project["time_type"] == 1){
            $inteval = $project["time"] * 60;
        }

        $begintime = $project["begin_hour"] * 60;
        $endtime = $project["end_hour"] * 60;
        $i = 30;
        while (true) {
            $begin = $begintime;
            $end = $begin + $inteval;
            
            if($end > $endtime){
                break;
            }

            $item = array(
                "begintime"=>$this->calctime($begin, 1), "begin_stamp"=>$begin,
                "endtime"=>$this->calctime($end, 1), "end_stamp"=>$end,
                "title"=>"", "selected"=>0
            );

            foreach($servicetime as $k=>$v){
                $begin_stamp = $this->calctime($v["begintime"], 2);
                $end_stamp = $this->calctime($v["endtime"], 2);

                if($begin_stamp == $begin && $end_stamp == $end){
                    $item["selected"] = 1;
                    $item["title"] = $v["title"];
                    break;
                }
            }

            $list[] = $item;

            $begintime += $i;
        }

        $this->assign("times", $list);
        $this->assign("map", $param);
    	$this->show();
    }

    private function calctime($time, $type = 1){
        if(empty($time)){
            return "";
        }

        if($type == 1){
            $hour = intval($time / 60);
            $minute = intval($time % 60);

            if($hour < 10){
                $hour = "0".$hour;
            }
            if($minute < 10){
                $minute = "0".$minute;
            }

            return $hour.":".$minute;
        }

        $time = explode(":", $time);
        if(count($time) != 2){
            return 0;
        }

        $hour = intval($time[0]) * 60;
        
        $minute = intval($time[1]);

        return $hour + $minute;
    }

    public function getMap(){
        $projectid = I("get.projectid", 0);
        $days = I("get.days", 0);
        $map = array("projectid"=>$projectid, "days"=>$days);
        return $map;
    }
}