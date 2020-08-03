<?php
namespace CApi\Service;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class PublicBenefitService
{

    const STATUS=[0=>'未开始', 1=>'招募中 ', 2=>'已结束'];

    public function getList($where = [], $offset = 0, $limit = 10){
        $data = D('PublicBenefit')->getList($where, $offset, $limit);
        if( !$data['total'] ){
            $data['rows'] = [];
            return $data;
        }
        foreach ( $data['rows'] as &$item ){
            $item = $this->listFormat($item);
            unset($data['content'], $data['add_time']);
        }
        return $data;
    }

    public function listFormat($data){
        if( $data['resource_type'] == 1 ){
            $data['resource'] = explode(',', $data['resource']);
            $data['resource'] = array_slice($data['resource'], 0, 3);
            foreach ($data['resource'] as &$item){
                $item = DoUrlHandle($item);
            }
        }
        $data['status_text'] = self::STATUS[$data['status']];
        $data['start_time'] = date('Y-m-d', $data['start_time']);
        $data['add_time'] = date('Y-m-d', $data['add_time']);
        return $data;
    }

    public function details($id){
        $data = D('PublicBenefit')->getOne($id);
        $data = $this->listFormat($data);
        return $data;
    }

    public function comment($activity_id, $replay_id = 0, $offset = 0, $limit = 10){
        $where = [
            'activity_id' => $activity_id,
            'replay_id' => $replay_id,
        ];
        $data = D('PublicBenefitComment')->getList($where, $offset, $limit);
        return $data;
    }
    public function commentListFormat($data){
        foreach ( $data as &$item){
            $item['avatar'] = DoUrlHandle($item['avatar']);
            $item['add_time'] = date('Y.m.d', $item['add_time']);
            if( !$item['replay_id'] ){
                $item['replay'] = $this->comments($item['activity_id'], $item['id']);
            }
        }
        return $data;
    }
    //所有回复评论
    public function comments($activity_id, $replay_id = 0){
        $where = [
            'activity_id' => $activity_id,
            'replay_id' => $replay_id,
        ];
        $data = D('PublicBenefitComment')->getLists($where);
        if (count($data['rows']) > 0){
            foreach ($data['rows'] as &$value){
                $value['add_time'] = date('Y.m.d', $value['add_time']);
            }
        }
        return $data;
    }
}