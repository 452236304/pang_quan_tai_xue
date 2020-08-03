<?php
namespace Common\Model;

class CoordinateModel{

    //读取服务订单服务人员的坐标 - 经纬度
    public function readcoordinate($order){
		$list = array();
		
		if(!(in_array($order["status"], [1,4]) && in_array($order["execute_status"], [0,1,2,3,4]) && $order["admin_status"] == 1)){
			return $list;
        }

        $path = "./coordinate/service_order/order_".$order["id"]."/";
        if(!is_dir($path)){
            return $list;
        }

        $files = dir($path);
        while($filename = $files->read()){
            if($filename != "." && $filename != ".."){
                $file = $path.$filename;
                if(!is_file($file)){
                    continue;
                }

                $coordinate = file_get_contents($file);
                if($coordinate){
                    $coordinate = rtrim($coordinate, "\r\n");
                }
                $coordinate = explode("\r\n", $coordinate);
                foreach($coordinate as $k=>$v){
                    if(empty($v)){
                        continue;
                    }
                    $v = explode("|", $v);
                    if(count($v) != 3){
                        continue;
                    }

                    $item = array(
                        "longitude"=>$v[0], "latitude"=>$v[1], "time"=>date("Y-m-d H:i:s", $v[2]), "stamp"=>$v[2]
                    );
                    $list[$filename][] = $item;
                }
            }
        }

        return $list;
    }

    //读取最新的服务订单服务人员坐标 - 经纬度
    public function readnewcoordinate($order){
        $coordinate = array(
            "longitude"=>"", "latitude"=>"", "time"=>""
        );
		
        $list = $this->readcoordinate($order);
        if(count($list) <= 0){
            return $coordinate;
        }

        //日期集合倒序
        $list = array_reverse($list);

        foreach($list as $k=>$v){
            if($k != date("Y-m-d")){
                return $coordinate;
            }

            if(count($v) <= 0){
                return $coordinate;
            }

            //经纬度集合倒序
            $v = array_reverse($v);

            $coordinate = $v[0];

            break;
        }

        return $coordinate;
    }

    //记录服务订单服务人员的坐标 - 经纬度
    public function recordcoordinate($userid, $coordinate){
        if(count($coordinate) <= 0){
            return;
        }
        // $longitude = str_replace("|", "-", $longitude);
        // $latitude = str_replace("|", "-", $latitude);
        
		$ordermodel = D("service_order");

        //服务订单正在服务中 / 服务订单开始前一小时 / 服务订单结束后一小时
        $currenttime = date("Y-m-d H:i");
        $begintime = date("Y-m-d H:i", strtotime("+1 hour", time()));
        $endtime = date("Y-m-d H:i", strtotime("-1 hour", time()));
        $map = "service_userid=".$userid." and type=2 and admin_status=1 and ((status=1 and execute_status in (1,2)) "
            ."or (status=1 and execute_status=0 and begintime < '".$begintime."') "
            ."or (status=1 and execute_status=3 and execute_time >= '".$endtime."') "
            ."or (status=4 and execute_status=4 and execute_time >= '".$endtime."')) ";
        $list = $ordermodel->where($map)->select();

        $recordmodel = D("service_order_record");
        
        foreach($list as $k=>$v){
            //检查服务人员完成服务时间是否超出一个小时
            if(in_array($v["execute_status"], [3,4])){
                $map = array("orderid"=>$v["id"], "execute_status"=>3);
                $record = $recordmodel->where($map)->find();
                if($record && $record["updatetime"] < $endtime){
                    continue;
                }
            }

            $path = "./coordinate/service_order/order_".$v["id"];
            if (!is_dir($path)){
                mkdir($path); 
            }
            $file = $path."/".date("Y-m-d");
            if(file_exists($file)){
                file_put_contents($file);
            }

            $openfile = fopen($file, "a");

            //$content = $longitude."|".$latitude."|".time()."\r\n";

            $content = "";
            foreach($coordinate as $ik=>$iv){
                if(empty($iv)){
                    continue;
                }
                $content .= $iv."\r\n";
            }

            fwrite($openfile, $content);

            fclose($openfile);
        }
        
        return;
    }

}