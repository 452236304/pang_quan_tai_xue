<?php
namespace CApi\Model;

/**问题
 * Class QuestionModel
 * @package CApi\Model
 */
class QuestionModel extends CommonModel{

    //按条件查
    public function getTotal($field=null,$where=null,$order=null,$begin=0,$limit=10){
        $res = $this->field($field)
            ->where($where)
            ->order($order)
            ->limit($begin,$limit)
            ->select();
        return $res;
    }

    /**写入
     * @要写入的数据 $data
     * @return bool|int|mixed|string
     */
    public function write($data){
        return $this->add($data);
    }

    /**我提交的问题
     * @return mixed
     */
    public function myQuestion($where=null,$order=null, $offset = 0, $limit = 10){
        $field = "id,title,content,images,answer_num,share_num,draft,add_time";
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

    //问题详情
    public function getQuestion($where){
        $field = "Q.id, Q.title, Q.content, Q.answer_num,Q.add_time,U.id as user_id, U.nickname,U.avatar,U.fans, Q.images";
        $res =  $this->alias('Q')
            ->join('__USER__ U on U.id = Q.user_id')
            ->field($field)
            ->where($where)
            ->find();
        return $res;
    }

    //我的提问
    public function countQ($where){
        return $this->where($where)->count();
    }

}##