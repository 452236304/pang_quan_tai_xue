<?php
namespace CApi\Model;

class ProductModel extends CommonModel{

    public function batch($ids){
        if( !is_array($ids) ){
            $ids = [$ids];
        }
        $where = [
            'id' => ['IN', $ids],
            'status' => 1,
        ];
        $data = $this->where($where)->select();
        $product = [];
        foreach ( $data as $item ){
            $product[$item['id']] = $item;
        }
        return $product;
    }

    public function getList($where, $offset = 0, $limit = 10, $sort = 'id', $order = 'desc')
    {
        $order = "p.top desc, p.recommend desc, p.ordernum asc, p.sales desc";
        $date['total'] = $this->alias('p')
            ->where($where)
            ->join('LEFT JOIN sj_product_attribute a on p.id=a.productid')
            ->group('p.id')
            ->count();
        if( $limit ){
            $data['rows'] = $this->alias('p')
                ->field('p.*,MAX(a.price) max_price,MIN(a.price) min_price')
                ->where($where)
                ->join('LEFT JOIN sj_product_attribute a on p.id=a.productid')
                ->group('p.id')
                ->order($order)
                ->limit($offset, $limit)
                ->select();
        }else{
            $data['rows'] = $this->alias('p')
                ->field('p.*,MAX(a.price) max_price,MIN(a.price) min_price')
                ->where($where)
                ->join('LEFT JOIN sj_product_attribute a on p.id=a.productid')
                ->group('p.id')
                ->order($order)
                ->select();
        }
        return $data;
    }
}