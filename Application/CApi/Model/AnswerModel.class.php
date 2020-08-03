<?php
namespace CApi\Model;

/**问题回答
 * Class AnswerModel
 * @package CApi\Model
 */
class AnswerModel extends CommonModel{

    /**写入
     * @要写入的数据 $data
     * @return bool|int|mixed|string
     */
    public function write($data){
        return $this->add($data);
    }

    //我的回答
    public function myAnswer($where=null,$order=null, $offset=0, $limit=10){
        $field = "id,question_id,content,images,share_num,comment_num,favour_num,draft,add_time";
        $data['rows'] =  $this->field($field)
            ->where($where)
            ->order($order)
            ->limit($offset.','.$limit)
            ->select();
        $data['total'] =  $this
            ->where($where)
            ->count();
        return $data;
    }

    /**问题详情
     * @param $where
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    public function Detail($where,$order,$offset=0,$limit=10){
        $field = "A.id, A.question_id,A.user_id,A.content,A.images,A.share_num,A.comment_num,A.favour_num,A.add_time,U.nickname,U.avatar";
        $data['total'] = $this->alias('A')
            ->join('__USER__ U on U.id = A.user_id')
            ->where($where)
            ->count();
        $data['rows'] = $this->alias('A')
            ->join('__USER__ U on U.id = A.user_id')
            ->field($field)
            ->where($where)
            ->order($order)
            ->limit($offset.",".$limit)
            ->select();
        return $data;
    }

    public function getOne($where){
        return $this->where($where)->find();
    }

    /**回答详情
     * @param $where
     * @return mixed
     */
    public function answerDetail($where){
        $field = "Q.id AS question_id, Q.title,Q.answer_num,U.id as user_id,U.avatar,U.nickname, A.id, A.add_time,A.content,A.share_num,A.comment_num,A.favour_num,A.images";
        $res = $this->alias('A')
            ->join('__USER__ U on U.id = A.user_id')
            ->join('__QUESTION__ Q on Q.id = A.question_id')
            ->field($field)
            ->where($where)
            ->find();
        return $res;
    }

    //我的回答
    public function countA($where){
        return $this->where($where)->count();
    }
}##