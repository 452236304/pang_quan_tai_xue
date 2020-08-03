<?php
namespace CApi\Model;

/**回答评论
 * Class AnswerCommentModel
 * @package CApi\Model
 */
class AnswerCommentModel extends CommonModel{

    /**写入
     * @要写入的数据 $data
     * @return bool|int|mixed|string
     */
    public function write($data){
        return $this->add($data);
    }

    //回答评论列表
    public function answerComment($where,$order,$begin=0,$limit=10){
        $field = "AC.id, AC.answer_id,AC.content,AC.add_time,AC.replay_num,U.id AS user_id,U.nickname,U.avatar,AC.favour_num";
        $res = $this->alias('AC')
            ->join("__USER__ U on U.id = AC.user_id")
            ->field($field)
            ->where($where)
            ->order($order)
            ->limit($begin,$limit)
            ->select();

        $conut = $this->alias('AC')
            ->join("__USER__ U on U.id = AC.user_id")
            ->where($where)
            ->count();

        return $data = [
            "res" =>$res,
            "count" =>$conut
        ];

    }

    public function replay($where){
        $field = "AC.id, AC.content,AC.add_time,U.id,U.nickname,U.avatar";
        $data = $this->alias('AC')
            ->join("__USER__ U on U.id = AC.user_id")
            ->field($field)
            ->where($where)
            ->order('AC.add_time desc')
            ->select();
        return $data;
    }

}