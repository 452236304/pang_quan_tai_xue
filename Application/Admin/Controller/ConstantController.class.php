<?php
namespace Admin\Controller;

class ConstantController extends BaseController
{

    /**
     * Notes: 分销比例设置
     * User: dede
     * Date: 2020/3/4
     * Time: 2:44 下午
     */
    public function teamProfit(){
        $id = I('id', 0, 'intval');
        $tag = 'team_profit';
        if( IS_AJAX ){
            $part1 = I('part1');
            $part2 = I('part2');
            foreach ( $part1 as &$value){
                $value = intval($value);
            }
            foreach ( $part2 as &$value){
                $value = intval($value);
            }
            $data = [
                'tag' => $tag,
                'value' => [
                    'part1' => $part1,
                    'part2' => $part2,
                ],
            ];
			$data['value']=json_encode($data['value']);
            if( $id ){
                $res = D('Constant')->update($id, $data);
            }else{
                $res = D('Constant')->addOne($data);
            }
            if( $res ){
                $this->success();
            }
            $this->error('操作失败！');

        }
        $data = D('Constant')->getByTag($tag);
        $this->assign('data', $data);
        $this->display();
    }

    public function withdraw(){
        $min = D('Constant')->where(['tag' => 'min_amount'])->find();
        $this->assign('min_amount', $min['value']);
        $max = D('Constant')->where(['tag' => 'max_amount'])->find();
        $this->assign('max_amount', $max['value']);
        $start = D('Constant')->where(['tag' => 'start_date'])->find();
        $this->assign('start_date', $start['value']);
        $end = D('Constant')->where(['tag' => 'end_date'])->find();
        $this->assign('end_date', $end['value']);
        if( IS_AJAX ){
            $min_amount = I('min_amount', 0, 'floatval');
            $max_amount = I('max_amount', 0, 'floatval');
            $start_date = I('start_date', 0, 'intval');
            $end_date = I('end_date', 0, 'intval');
            if( $min_amount > $max_amount ){
                $this->error('最小金额必须大于等级最大金额');
            }
            $data = [
                'tag' => 'min_amount',
                'value' => $min_amount,
            ];

            if( $min ){
                $res = D('Constant')->update($min['id'], $data);
            }else{
                $res = D('Constant')->addOne($data);
            }

            $data = [
                'tag' => 'max_amount',
                'value' => $max,
            ];

            if( $max ){
                $res = D('Constant')->update($max['id'], $data);
            }else{
                $res = D('Constant')->addOne($data);
            }

            $data = [
                'tag' => 'start_date',
                'value' => $start_date,
            ];

            if( $start ){
                $res = D('Constant')->update($start['id'], $data);
            }else{
                $res = D('Constant')->addOne($data);
            }

            $data = [
                'tag' => 'end_date',
                'value' => $end_date,
            ];
            if( $end ){
                $res = D('Constant')->update($end['id'], $data);
            }else{
                $res = D('Constant')->addOne($data);
            }

            $this->success();
        }
        $this->display();
    }
}