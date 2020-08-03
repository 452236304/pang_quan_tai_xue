<?php
namespace CApi\Model;

class ServiceProjectModel extends CommonModel
{
    public function batch($ids){
        if( !is_array($ids) ){
            $ids = [$ids];
        }
        $where = [
            'id' => ['IN', $ids],
            'status' => 1,
        ];
        $data = $this->where($where)->select();
        $project = [];
        foreach ( $data as $item ){
            $project[$item['id']] = $item;
        }
        return $project;
    }

    /**
     * Notes: 上门照护项目列表
     * User: dede
     * Date: 2020/3/2
     * Time: 2:37 下午
     * @param $category_id
     * @return mixed
     */
    public function categoryService($category_id){
        $where = [
            'categoryid' => $category_id,
            'status' => 1,
        ];
        $data = $this->where($where)->order('ordernum')->select();
        return $data;
    }
}