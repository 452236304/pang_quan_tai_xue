<?php
namespace CApi\Service;

class WalletLogService
{
    /**
     * Notes: 添加用户钱包日志
     * User: dede
     * Date: 2020/3/5
     * Time: 6:18 下午
     * @param $user_id
     * @param $adjust
     */
    public function addLog($user_id, $adjust, $remark = ''){
        $user = D('User')->find($user_id);
        $after = bcadd($user['user_money'], $adjust, 2);
        $after = $after  > 0 ? $after : 0;
        $log = [
            'user_id' => $user_id,
            'before' => $user['user_money'],
            'adjust' => $adjust,
            'after' => $after,
            'remark' => $remark,
        ];
        $res = D('WalletLog')->addOne($log);
        if( !$res ){
            return false;
        }

        // 修改用户余额
        $save_user = [
            'id' => $user_id,
            'user_money' => $after,
            'grand_total_money' => $user['grand_total_money'] + $adjust,
        ];
        $res = D('User')->save($save_user);
        if( !$res ){
            return false;
        }
        return true;
    }
}