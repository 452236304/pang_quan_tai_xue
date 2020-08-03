<?php
namespace CApi\Service;

class ServiceProjectService
{
    /**
     * Notes: 批量查询服务信息
     * User: dede
     * Date: 2020/2/26
     * Time: 5:46 下午
     * @param $ids
     */
    public function batch($ids){
        $data = D('ServiceProject')->batch($ids);
        foreach ( $data as &$item ){
            $item = $this->listFormat($item);
        }
        return $data;
    }

    public function project(){
        $category = D('ServiceCategory')->serviceCategory();
        $data = [];
        foreach ( $category as $key => &$item){
            $data[$key] = [
                'category_id' => $item['id'],
                'title' => $item['title'],
                'project' => [],
            ];
            $project = D('ServiceProject')->categoryService($item['id']);
            foreach ( $project as $value ){
                $data[$key]['project'][] = $this->listFormat($value);
            }
        }
        return $data;
    }

    /**
     * Notes: 服务搜索
     * User: dede
     * Date: 2020/3/26
     * Time: 11:55 上午
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function search($where, $offset = 0, $limit = 0){
        $where['status'] = 1;
        $data['total'] = D('ServiceProject')->where($where)->order('ordernum')->count();
        if( $limit ){
            $data['rows'] = D('ServiceProject')->where($where)->order('ordernum')->limit( $offset . ',' . $limit)->select();

        }else{
            $data['rows'] = D('ServiceProject')->where($where)->order('ordernum')->select();
        }
        foreach ( $data['rows'] as &$value ){
            $value = $this->listFormat($value);
        }
        return $data;
    }

    /**
     * Notes: 列表数据处理
     * User: dede
     * Date: 2020/3/2
     * Time: 2:22 下午
     * @param $data
     * @return array
     */
    public function listFormat($data){
        $project = [
            'service_id' => $data['id'],
            'title' => $data['title'],
            'thumb' => DoUrlHandle($data['thumb']),
            'price' => $data['price'],
            'market_price' => $data['market_price'],
        ];
        return $project;
    }
}