<?php
namespace CApi\Model;

class WalletWithdrawModel extends CommonModel
{

    public function amountDrawn($user_id){
        $where = [
            'user_id' => $user_id,
            'status' => 1,
        ];
        $amount = $this->where($where)->sum('amount');
        return floatval($amount);
    }

}