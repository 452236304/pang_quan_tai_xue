<?php
namespace CApi\Service;

class DiscoverNavService
{

    /**
     * Notes: 获取导航栏目
     * User: dede
     * Date: 2020/2/26
     * Time: 5:17 下午
     */
    public function nav(){
        $data = D('DiscoverNav')->nav();
        $nav = [];
        foreach ($data as $item){
            $nav[] = [
                'nav_id' => $item['id'],
                'title' => $item['title'],
            ];
        }
        return $nav;
    }
}