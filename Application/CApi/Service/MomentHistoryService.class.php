<?php
namespace CApi\Service;

class MomentHistoryService
{

    public function append($user_id, $moment_id){
        $data = [
            'user_id' => $user_id,
            'moment_id' => $moment_id,
        ];
        $res = D('MomentHistory')->where($data)->find();
        if( $res ){
            $res = D('MomentHistory')->where($data)->save(['add_time' => time()]);
        }else{
            $res = D('MomentHistory')->addOne($data);
        }
        return $res;
    }
}