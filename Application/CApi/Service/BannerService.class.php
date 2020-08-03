<?php
namespace CApi\Service;

class BannerService
{

    /**
     * Notes: 轮播图列表
     * User: dede
     * Date: 2020/7/20
     * Time: 7:02 下午
     * @param $type
     * @return mixed
     */
    public function show($type){
        $map = array(
            "status"=>1,
            "type"=>$type
        );
        $banner = D('Banner')->where($map)->select();

        foreach ($banner as &$item) {
            $item["image"] = DoUrlHandle($item["image"]);

            if($item["param"]){
                $item["param"] = json_decode($item["param"], true);
            } else{
                $item["param"] = array("param_type"=>"-1", "param_id"=>"");
            }
        }
        return $banner;
    }
}