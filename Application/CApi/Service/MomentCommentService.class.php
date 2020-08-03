<?php
namespace CApi\Service;

class MomentCommentService
{

    /**
     * Notes: 添加评论
     * User: dede
     * Date: 2020/2/27
     * Time: 4:45 下午
     * @param $user_id
     * @param $data
     * @return mixed
     * @throws \Think\Exception
     */
    public function publish($user_id, $data){
        $moment = D('Moment')->getOne($data['moment_id']);
        if( !$moment ){
            E('动态不存在！');
        }
        $data['user_id'] = $user_id;
        $res = D('MomentComment')->addOne($data);
        // 评论量
        D('Moment', 'Service')->commentNum($data['moment_id']);
        if( $res ){
            $this->commentNumInc($data['replay_id']);
        }
        return $res;
    }

    public function comment($moment_id, $replay_id = 0, $offset = 0, $limit = 10){
        $where = [
            'moment_id' => $moment_id,
            'replay_id' => $replay_id,
        ];
        $data = D('MomentComment')->getList($where, $offset, $limit);
        $data = $this->listFormat($data);
        return $data;
    }

    public function listFormat($data){
        foreach ( $data as &$item){
            $item['avatar'] = DoUrlHandle($item['avatar']);
            $item['add_time'] = date('Y.m.d', $item['add_time']);
            if( !$item['replay_id'] ){
                $item['replay'] = $this->comment($item['moment_id'], $item['id']);
            }
        }
        return $data;
    }

    /**
     * Notes: 评论盖楼  评论数量
     * User: dede
     * Date: 2020/2/27
     * Time: 4:47 下午
     * @param $replay_id
     */
    public function commentNumInc($replay_id){
        $where['id'] = $replay_id;
        D('MomentComment')->where($where)->setInc('comment');
    }
}