<?php
namespace CApi\Controller;

class QuestionHandleController extends BaseLoggedController
{

    /**
     * Notes: 提交问题
     * User: dede
     * Date: 2020/7/15
     * Time: 11:25 上午
     */
    public function ask(){
        $user = $this->AuthUserInfo;
        $title = I('title');
        $content = I('content');
        $images = I('images');//（string，逗号分隔）
        $draft = I('draft',0,'intval');

        //是否草稿
        if($draft){
            $this->required($title);
            $this->lengthlimit($title);//限长
            $this->required($content);
            $this->required($images);

            $images = json_encode(explode(",",$images));
        }else{
            //有一个即可
            if(empty($title) && empty($content) && empty($images)){
                E('请填写内容');exit;
            }
            if (!empty($title)){
                $this->lengthlimit($title);//限长
            }
            if(!empty($images)){
                $images = json_encode(explode(",",$images));
            }
        }

        $data = [
            "user_id" => $user['id'],
            "title" => $title,
            "content" => $content,
            "images" => $images,
            "add_time" => time(),
            "draft" => $draft
        ];

        $res = D('question')->write($data);
        if($res){
            $message = [
                "message" => $res
            ];
            return $message;
        }else{
            E('提交失败');
        }

    }

    /**
     * Notes: 提交回答
     * User: dede
     * Date: 2020/7/15
     * Time: 11:26 上午
     */
    public function answer(){
        $user = $this->AuthUserInfo;

        $question_id = I('question_id');//对应问题id
        $content = I('content');
        $images = I('images');//（string，逗号分隔）
        $draft = I('draft',0,'intval');

        $this->required($question_id);
        //是否草稿
        if($draft){
            $this->required($content);
            $this->required($images);
            $images = json_encode(explode(",",$images));
        }else{
            if(empty($content) && empty($images)){
                E('请填写内容');exit;
            }elseif (!empty($images)){
                $images = json_encode(explode(",",$images));
            }
        }

        $data = [
            "question_id" => $question_id,
            "user_id" => $user['id'],
            "content" => $content,
            "images" => $images,
            "add_time" => time(),
            "draft" => $draft
        ];

        $res = D('answer')->write($data);
        if($res){
            $where = [
                "id" => $question_id
            ];
            D('question')->where($where)->setInc('answer_num',1);//对应问题加回答数量
            $message = [
                "message"=>$res
            ];
            return $message;
        }else{
            E('发布失败');
        }

    }

    /**
     * Notes: 提交回答评论
     * User: dede
     * Date: 2020/7/15
     * Time: 11:26 上午
     */
    public function answerComment(){
        $user = $this->AuthUserInfo;

        $answer_id = I('answer_id');//对应回答id
        $content = I('content');

        $this->required($answer_id);
        $this->required($content);

        $data = [
            "user_id" => $user['id'],
            "answer_id" => $answer_id,
            "content" => $content,
            "add_time" => time()
        ];

        $res = D('answerComment')->write($data);

        if($res){
            $where = [
                "id" => $answer_id
            ];
            D('answer')->where($where)->setInc('comment_num',1);//对应回答加评论数量
            $message = [
                "message"=>$res
            ];
            return $message;
        }else{
            E('发布失败');
        }
    }

    /**
     * Notes: 我提交的问题
     * User: dede
     * Date: 2020/7/15
     * Time: 11:29 上午
     */
    public function myQuestion(){
        $user = $this->AuthUserInfo;

        $page = I('page', 1, 'intval');
        $limit = I('limit', 10, 'intval');
        $offset = ($page-1)*$limit;

        $where = [
            "user_id" => $user['id']
        ];
        $order = "add_time desc";
        $list = D('question')->myQuestion($where,$order,$offset,$limit);

        foreach($list['rows'] as &$value){
            $value['add_time'] = $this->getDate($value['add_time']);

            if(!empty($value['images'])){
                $value['images'] = json_decode($value['images'],true);
                foreach($value['images'] as &$v){
                    $v = $this->DoUrlHandle($v);
                }
            }
        }

        $count = $list['total'];
        $totalpage = ceil($count / $limit);
        $this->SetPaginationHeader($totalpage,$count,$page,$limit);

        $data = [
            "list" => $list['rows']
        ];
        return $data;
    }

    /**
     * Notes: 我提交的答案
     * User: dede
     * Date: 2020/7/15
     * Time: 11:28 上午
     */
    public function myAnswer(){
        $page = I('page', 1, 'intval');
        $limit = I('limit', 10, 'intval');
        $offset = ($page-1)*$limit;

        $user = $this->AuthUserInfo;

        $where = [
            "user_id" => $user['id']
        ];
        $order = "add_time desc";
        $list = D('answer')->myAnswer($where,$order, $offset, $limit);
        foreach($list['rows'] as &$value){
            $value['add_time'] = $this->getDate($value['add_time']);
            $title = D('question')->where(['id' => $value['question_id']])->field(['title'])->find();
            $value['title'] = $title['title'];
            $value['answer_num'] = D('answer')->where(['question_id' => $value['question_id']])->count();
            if(!empty($value['images'])){
                $value['images'] = json_decode($value['images'],true);
                foreach($value['images'] as &$v){
                    $v = $this->DoUrlHandle($v);
                }
            }
        }

        $count = $list['total'];
        $totalpage = ceil($count / $limit);
        $this->SetPaginationHeader($totalpage,$count,$page,$limit);

        $data = [
            "list" => $list['rows']
        ];
        return $data;
    }

    /**
     * Notes:添加/取消收藏
     * User: dede
     * Date: 2020/7/23
     * Time: 10:21 上午
     */
    public function collect(){
        $user = $this->AuthUserInfo;
        $question_id = I('question_id',0,'intval');
        $where = [
            'user_id' => $user['id'],
            'question_id' => $question_id,
        ];
        $collect = D('QuestionCollect')->where($where)->find();
        if( $collect ){
            D('QuestionCollect')->where($where)->delete();
        }else{
            $data = [
                'user_id' => $user['id'],
                'question_id' => $question_id,
                'add_time' => time()
            ];
            D('QuestionCollect')->add($data);
        }
        return true;
    }

    /**
     * Notes: 我收藏的问题
     * User: dede
     * Date: 2020/7/15
     * Time: 11:28 上午
     */
    public function myCollect(){
        $page = I('page', 1, 'intval');
        $limit = I('limit', 10, 'intval');
        $offset = ($page-1)*$limit;
        $user = $this->AuthUserInfo;

        $data = D('QuestionCollect')->myCollect($user['id'], $offset, $limit);
        foreach($data['rows'] as &$value){
            $value['add_time'] = $this->getDate($value['add_time']);
            if(!empty($value['images'])){
                $value['images'] = json_decode($value['images']);
                foreach($value['images'] as &$v){
                    $v = $this->DoUrlHandle($v);
                }
            }
        }

        $count = $data['total'];
        $totalpage = ceil($count / $limit);
        $this->SetPaginationHeader($totalpage,$count,$page,$limit);

        $data = [
            "list" => $data['rows']
        ];
        return $data;
    }

    public function myQuestionAnswer(){
        $user = $this->AuthUserInfo;
        $question_id = I('question_id',0, 'intval');
        if( !$question_id ){
            E('请先选择问题');
        }

        $where = [
            'question_id' => $question_id,
            'user_id' => $user['id']
        ];
        $anwer = D('Answer')->where($where)->find();
        $anwer['images'] = json_decode($anwer['images'], true);
        foreach ( $anwer['images'] as &$item ){
            $item = DoUrlHandle($item);
        }
        $anwer['add_time'] = date('Y-m-d H:i:s');
        return $anwer;
    }

    /**
     * Notes: 删除问题
     * User: dede
     * Date: 2020/7/23
     * Time: 6:44 下午
     * @return mixed
     * @throws \Think\Exception
     */
    public function removeQuestion(){
        $user = $this->AuthUserInfo;
        $question_id = I('question_id', 0, 'intval');

        $where = [
            'id' => $question_id,
            'user_id' => $user['id']
        ];
        $question = D('question')->where($where)->find();
        if( !$question ){
            E('非法操作');
        }
        $res = D('question')->where($where)->delete();
        return $res;
    }

    /**
     * Notes: 删除答案
     * User: dede
     * Date: 2020/7/23
     * Time: 6:46 下午
     * @return mixed
     * @throws \Think\Exception
     */
    public function removeAnswer(){
        $user = $this->AuthUserInfo;
        $answer_id = I('answer_id', 0, 'intval');

        $where = [
            'id' => $answer_id,
            'user_id' => $user['id']
        ];
        $question = D('Answer')->where($where)->find();
        if( !$question ){
            E('非法操作');
        }
        $res = D('Answer')->where($where)->delete();
        return $res;
    }

    /**
     * Notes: 添加/取消问题点赞
     * User: dede
     * Date: 2020/7/23
     * Time: 7:14 下午
     */
    public function answerGood(){
        $user = $this->AuthUserInfo;

        $answer_id = I('answer_id', 0, 'intval');
        $data = D('Answer')->find($answer_id);
        if( !$data ){
            E('非法操作！');
        }
        $where = [
            'type' => 1,
            'user_id' => $user['id'],
            'param' => $answer_id
        ];
        $result = D('QuestionPraise')->where($where)->find();
        if( $result ){
            $res = D('QuestionPraise')->where($where)->delete();
            D('answer')->where(['id' => $answer_id])->setDec('favour_num');
            return ['status' => 0];
        }else{
            $data = [
                'type' => 1,
                'user_id' => $user['id'],
                'param' => $answer_id,
                'add_time' => time(),
            ];
            $res = D('QuestionPraise')->add($data);
            D('answer')->where(['id' => $answer_id])->setInc('favour_num');
            return ['status' => 1];
        }
    }

    /**
     * Notes: 添加/取消回答评论点赞
     * User: dede
     * Date: 2020/7/23
     * Time: 8:05 下午
     * @return mixed
     * @throws \Think\Exception
     */
    public function answerCommentGood(){
        $user = $this->AuthUserInfo;

        $comment_id = I('comment_id', 0, 'intval');
        $data = D('AnswerComment')->find($comment_id);
        if( !$data ){
            E('非法操作！');
        }
        $where = [
            'type' => 2,
            'user_id' => $user['id'],
            'param' => $comment_id
        ];
        $result = D('QuestionPraise')->where($where)->find();
        if( $result ){
            $res = D('QuestionPraise')->where($where)->delete();
            D('AnswerComment')->where(['id' => $comment_id])->setDec('favour_num');
            return ['status' => 0];
        }else{
            $data = [
                'type' => 2,
                'user_id' => $user['id'],
                'param' => $comment_id,
                'add_time' => time(),
            ];
            $res = D('QuestionPraise')->add($data);
            D('AnswerComment')->where(['id' => $comment_id])->setInc('favour_num');
            return ['status' => 1];
        }
    }

    /**
     * Notes: 回复评论
     * User: dede
     * Date: 2020/7/23
     * Time: 8:08 下午
     */
    public function replayComment(){
        $user = $this->AuthUserInfo;
        $comment_id = I('comment_id', 0, 'intval');
        $data = D('AnswerComment')->find($comment_id);
        if( !$data ){
            E('非法操作！');
        }

        $content = I('content');
        if( !$content ){
            E('请输入评论内容');
        }

        $data = [
            'user_id' => $user['id'],
            'answer_id' => $data['answer_id'],
            'replay_id' => $comment_id,
            'content' => $content,
            'add_time' => time(),
        ];
        D('AnswerComment')->add($data);
        // 增加评论的评论数
        D('AnswerComment')->where(['id' => $comment_id])->setInc('replay_num');
        // 增加回答的评论数
        D('Answer')->where(['id' => $data['answer_id']])->setInc('comment_num');
    }

    /**必填内容
     * @param $data
     * @return string
     * @throws \Think\Exception
     */
    protected function required($data){
        if(empty($data)){
            E('请填写内容');exit;
        }
    }

    /**内容限长
     * @param $str
     * @param int $minlength
     * @param int $maxlength
     * @throws \Think\Exception
     */
    private function lengthlimit($str,$minlength=5,$maxlength=30){
        $strlen = mb_strlen($str);
        if($strlen < $minlength || $strlen > $maxlength){
            E('标题字数5~30个字');exit;
        }
    }

    /**时间戳转日期格式
     * @时间戳 $time
     * @return false|string
     */
    protected function getDate($time){
        return date("Y-m-d",$time);
    }
}##