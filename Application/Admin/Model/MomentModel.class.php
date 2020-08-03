<?php
namespace Admin\Model;

class MomentModel extends CommonModel
{

    public function addOne($data)
    {
        D()->startTrans();
        $moment_id = parent::addOne($data);
        if( !$moment_id ){
            D()->rollback();
        }
        $topic_pattern = "/\#([^\#|.]+)\#/";
        preg_match_all($topic_pattern, $data['content'], $topic);
        foreach ( $topic[1] as $value ){
            $where = ['title' => $value ];
            $data = D('topic')->where($where)->find();
            // 创建话题
            if( !$data ){
                $save = [
                    'title' => $value,
                    'join_in' => 1,
                    'add_time' => time(),
                ];
                $topic_id = D('topic')->add($save);
                if( !$topic_id ){
                    D()->rollback();
                }
            }else{
                $topic_id = $data['id'];
                D('topic')->where(['id'=>$topic_id])->setInc('join_in');
            }
            // 动态与话题关联关系
            $moment_topic = [
                'moment_id' => $moment_id,
                'topic_id' => $topic_id,
            ];
            $res = D('MomentTopic')->add($moment_topic);
            if( !$res ){
                D()->rollback();
            }
        }
        D('User')->setInc('moments');
        D()->commit();
        return $moment_id;
    }

    public function search($keyword){
        $where['content'] = ['like', '%'. $keyword .'%' ];
        $where['del_time'] = 0;
        $data = $this->where($where)->select();
        return $data;
    }

    public function getList($where, $offset = 0, $limit = 10, $sort = 'id', $order = 'desc')
    {
        $where['del_time'] = 0;
        $data['total'] = $this->alias('M')
            ->join('__USER__ AS U ON M.user_id = U.id', 'left')
            ->where($where)
            ->count();
        $field = array('U.nickname', 'M.id', 'M.title', 'M.add_time');
        $data['rows'] = $this->alias('M')
            ->join('__USER__ AS U ON M.user_id = U.id', 'left')
            ->field($field)
            ->where($where)
            ->order($sort. ' ' . $order)
            ->limit( $offset . ',' . $limit )
            ->select();
        return $data;
    }

    public function batch($id){
        if( !is_array($id) ){
            $id = [$id];
        }
        $where['M.id'] = ['IN', $id];
        $field = array('U.nickname', 'M.id', 'M.content', 'M.add_time');
        $data = $this->alias('M')
            ->join('__USER__ AS U ON M.user_id = U.id')
            ->field($field)
            ->where($where)
            ->select();
        return $data;
    }

    public function remove($id)
    {
        $data = [
            'id' => $id,
            'del_time'  => time(),
        ];
        return $this->save($data);
    }

}