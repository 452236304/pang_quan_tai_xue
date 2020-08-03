<?php
namespace CApi\Model;

class UserModel extends CommonModel
{

    /**
     * Notes: 推荐用户列表
     * User: dede
     * Date: 2020/3/10
     * Time: 2:08 下午
     * @return mixed
     */
    public function recommend(){
        $where = [
            'status' => 200,
            'is_recommend' => 1,
        ];
        $data = $this->where($where)->select();
        return $data;
    }

    /**
     * Notes: 粉丝排行榜top
     * User: dede
     * Date: 2020/3/10
     * Time: 2:08 下午
     * @param int $limit
     * @return mixed
     */
    public function fansTop($limit= 10){
        $where = [
            'status' => 200,
        ];
        $data = $this
            ->where($where)
            ->order('fans DESC')
            ->limit($limit)
            ->select();
        return $data;
    }
}