<?php
namespace CApi\Model;

class TopicModel extends CommonModel
{
    /**
     * Notes: 添加话题
     * User: dede
     * Date: 2020/2/24
     * Time: 4:06 下午
     * @param $topic
     * @return bool|string
     */
    public function batchAdd($topic){
        if( !is_array($topic) ){
            $topic = [$topic];
        }
        $add = [];
        foreach ($topic as $item){
            $where = ['title' => $item];
            $res = $this->where($where)->count();
            if( $res ){
                $this->joinIn($item);
                continue;
            }
            $add[] = [ 'title' => $item, 'join_in' => 1, 'add_time' => time() ];
        }
        if( $add ){
            $res = $this->addAll($add);
            return $res;
        }else{
            return true;
        }
    }

    /**
     * Notes:参与话题
     * User: dede
     * Date: 2020/2/24
     * Time: 4:06 下午
     * @param $topic
     */
    public function joinIn($topic){
        $where['title'] = $topic;
        $this->where($where)->setInc('join_in');
    }
}