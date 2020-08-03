<?php
namespace CApi\Service;

class ProductService
{
    /**
     * Notes: 批量查询服务信息
     * User: dede
     * Date: 2020/2/26
     * Time: 5:46 下午
     * @param $ids
     */
    public function batch($ids){
        $data = D('Product')->batch($ids);
        foreach ( $data as &$item ){
            $item = $this->listFormat($item);
        }
        return $data;
    }

    /**
     * Notes: 分类商品列表
     * User: dede
     * Date: 2020/3/2
     * Time: 3:41 下午
     * @param $category_id
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    public function categoryProduct($category_id, $offset = 0, $limit = 10){
        $map = [
            "p.status"=>1,
            "p.type"=>0,
            'a.status'=>1,
            'p.categoryid' => $category_id,
        ];
        $data = D('Product')->getList($map, $offset, $limit);
        foreach ( $data['rows'] as &$item ){
            $item = $this->listFormat($item);
        }
        return $data;

    }

    /**
     * Notes: 限时秒杀商品
     * User: dede
     * Date: 2020/3/17
     * Time: 4:25 下午
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    public function seckill($offset = 0, $limit = 3){
        $map = [
            "p.status"=>1,
            "p.type"=>0,
            'a.status'=>1,
            'p.seckill' => 1,
        ];
        $data = D('Product')->getList($map, $offset, $limit);
        foreach ( $data['rows'] as &$item ){
            $item = $this->listFormat($item);
        }
        return $data;
    }

    /**
     * Notes: 优惠选购
     * User: dede
     * Date: 2020/3/17
     * Time: 4:28 下午
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    public function discounts($offset = 0, $limit = 6){
        $map = [
            "p.status"=>1,
            "p.type"=>0,
            'a.status'=>1,
            'p.discounts' => 1,
        ];
        $data = D('Product')->getList($map, $offset, $limit);
        foreach ( $data['rows'] as &$item ){
            $item = $this->listFormat($item);
        }
        return $data;
    }

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

    /**
     * Notes: 搜索商品
     * User: dede
     * Date: 2020/3/26
     * Time: 12:00 下午
     * @param $where
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    public function search($where, $offset = 0, $limit = 0){
        $where['p.status'] = 1;
        $where['p.type'] = 0;
        $where['a.status'] = 1;
        $data = D('Product')->getList($where, $offset, $limit);
        foreach ( $data['rows'] as &$item ){
            $item = $this->listFormat($item);
        }
        return $data;

    }
}