<?php
namespace  CApi\Service;

class TopicService
{
    /**
     * Notes: 过滤动态中的话题
     * User: dede
     * Date: 2020/3/16
     * Time: 2:08 下午
     * @param $moment_id
     * @param $content
     * @return mixed
     */
    public function filterTopic($moment_id, $content)
    {
        $topic_pattern = "/\#([^\#|.]+)\#/";
        preg_match_all($topic_pattern, $content, $topic);
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
            }else{
                $topic_id = $data['id'];
                D('topic')->where(['id'=>$topic_id])->setInc('join_in');
            }
            // 动态与话题关联关系
            $moment_topic = [
                'moment_id' => $moment_id,
                'topic_id' => $topic_id,
            ];
            D('MomentTopic')->add($moment_topic);
        }
        return true;
    }

    /**
     * Notes: 参与人数top10
     * User: dede
     * Date: 2020/3/2
     * Time: 11:42 上午
     * @param $limit
     * @return mixed
     */
    public function top($page = 1, $limit = 10){
        $data = D('Topic')
            ->field('id, title, join_in')
            ->order('join_in', 'desc')
            ->limit(($page-1)*$limit, $limit)
            ->select();
        return $data;
    }

    /**
     * Notes: 话题搜索
     * User: dede
     * Date: 2020/3/2
     * Time: 11:43 上午
     * @param $key
     */
    public function search($key, $offset = 0, $limit = 10){
        $where['title'] = ['like', '%'.$key.'%'];
        $data = D('Topic')
            ->field('id, title')
            ->where($where)
            ->order('join_in', 'desc')
            ->limit($offset . ',' . $limit)
            ->select();
        return $data;
    }
}