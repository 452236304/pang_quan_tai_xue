<?php
namespace CApi\Model;

/**问题收藏
 * Class QuestionCollectModel
 * @package CApi\Model
 */
class QuestionCollectModel extends CommonModel{

    /**写入
     * @要写入的数据 $data
     * @return bool|int|mixed|string
     */
    public function write($data){
        return $this->add($data);
    }

    /**我收藏的问题
     * @return mixed
     */
    public function myCollect($user_id, $offset = 0, $limit = 10){
        $order = 'add_time desc';

        $where_1 = [
            'user_id' => $user_id
        ];
        $question = $this->where($where_1)->field('question_id')->order($order)->select();
        $question_id = [];
        foreach ($question as $value){
            $question_id[] = $value['question_id'];
        }
        $where['id'] = array('in',$question_id);
        $data['rows'] = D('question')
            ->where($where)
            ->limit( $offset.','.$limit )
            ->select();

        $data['total'] = D('question')
            ->where($where)
            ->count();
        return $data;
    }

    public function getOne($where){
        return $this->where($where)->find();
    }
}##