<?php
namespace Admin\Model;

class WalletLogModel extends CommonModel
{

    public function addOne($data)
    {
        $user = D('User')->find($data['user_id']);
        $data['before'] = $user['user_money'];
        $data['after'] = $data['before'] + $data['adjust'];
        return parent::addOne($data); // TODO: Change the autogenerated stub
    }
}