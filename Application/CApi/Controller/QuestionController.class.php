<?php
namespace CApi\Controller;

class QuestionController extends BaseController
{

    /**
     * Notes: 问题列表，按添加时间倒序排列
     * User: dede
     * Date: 2020/7/15
     * Time: 10:09 上午
     */
    public function index(){
        //是否登录
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;

            $user['avatar'] = $this->DoUrlHandle($user['avatar']);//补全域名

            $where["user_id"] = $user['id'];
            $countQuestion = D('question')->countQ($where);
            $countAnswer = D('answer')->countA($where);
            //我的问答
            $userList = [
                "nickname" => $user['nickname'],
                "avatar" => $user['avatar'],
                "totalquestion" => $countQuestion,
                "totalanswer" => $countAnswer,
            ];

        }else{
            $user['id'] = 0;
            $userList = [];
        }

        $page = I('page', 1, 'intval');
        $limit = I('limit', 10, 'intval');
        $begin = ($page-1)*$limit;
        $table = D('question');
        $res = $table->alias('Q')
            ->join('__USER__ U on U.id = Q.user_id')
            ->field('Q.id,Q.title,Q.images,Q.answer_num,Q.add_time,U.nickname')
            ->order('add_time desc')
            ->limit($begin,$limit)
            ->select();

        foreach($res as $k=>&$v){
            $jetLag = floor((time() - $v['add_time']) / 86400);

            if($jetLag > 7){
                $v['add_time'] = date("m-d H:i",$v['add_time']);
            }elseif($jetLag == 0){
                $v['add_time'] = "1天内";
            }else{
                $v['add_time'] = $jetLag."天前";
            }

            if(!empty($v['images'])){
                $v['images'] = json_decode($v['images'],true);
                if(count($v['images']) > 3){
                    $v['images'] = array_slice($v['images'],0,3);//最多保留3个
                }
                foreach($v['images'] as &$val){
                    $val = $this->DoUrlHandle($val);
                }
            }

        }//foreachend;

        $count = $table -> count();
        $totalpage = ceil($count / $limit);
        $this->SetPaginationHeader($totalpage, $count, $page, $limit);

        $banner_type = 1010;
        $banner = D('Banner', 'Service')->show($banner_type);

        $data = [
            "banner" => $banner,
            "userinfo" => $userList,
            "list" => $res
        ];
        return $data;
    }

    public function listed(){
        $page = I('page', 1, 'intval');
        $limit = I('limit', 10, 'intval');
        $begin = ($page-1)*$limit;
        $table = D('question');
        $data = $table->alias('Q')
            ->join('__USER__ U on U.id = Q.user_id')
            ->field('Q.id,Q.title,Q.images,Q.answer_num,Q.add_time,U.nickname')
            ->order('add_time desc')
            ->limit($begin,$limit)
            ->select();

        $count = $table->alias('Q')
            ->join('__USER__ U on U.id = Q.user_id')
            ->count();
        $totalpage = ceil($count / $limit);
        $this->SetPaginationHeader($totalpage, $count, $page, $limit);

        return ['list' => $data];
    }

    /**
     * Notes: 问题热榜，按照回答数量倒序排列， 并返回最近一个回答
     * User: dede
     * Date: 2020/7/15
     * Time: 10:14 上午
     */
    public function hot(){
        $page = I('page', 1, 'intval');
        $limit = I('limit', 10, 'intval');
        $offset = ($page-1)*$limit;

        $where = ['draft' => 0];
        $field = ['id', 'title', 'answer_num'];
        $list = D('Question')
            ->field($field)
            ->where($where)
            ->order('add_time desc')
            ->limit(10)
            ->select();
        $field = ['U.id AS user_id', 'U.avatar', 'U.nickname', 'A.id', 'A.content'];
        foreach ( $list as &$item ){
            $where = [
                'question_id' => $item['id'],
                'draft' => 0
            ];
            $answer = D('Answer')->alias('A')
                ->join('__USER__ U ON U.id = A.user_id')
                ->where($where)
                ->field($field)
                ->order('add_time desc')
                ->find();
            $item['answer'] = $answer;
        }

        $data = [
            "list" => $list
        ];
        return $data;
    }

    /**
     * Notes: 问题详情,并返回第一页回答
     * User: dede
     * Date: 2020/7/15
     * Time: 10:17 上午
     */
    public function detail(){
        $id = I('id', 0, 'intval');//问题id

        $where = ["Q.id"=>$id];
        $res = D('question')->getQuestion($where);
        if(!$res) E("不存在此问题");

        $res['avatar'] = $this->DoUrlHandle($res['avatar']);
        $res['add_time'] = $this->getDate($res['add_time']);
        $res['fans'] = $this->thousands($res['fans']);
        $res['images'] = json_decode($res['images'], true);
        foreach ($res['images'] as &$item){
            $item = DoUrlHandle($item);
        }

        //是否登录
        if($this->UserAuthCheckLogin()){
            $user_id = $this->AuthUserInfo['id'];
            $where = [
                'user_id' => $user_id,
                'question_id' => $id
            ];
            $ifCollect = D('questionCollect')->getOne($where);
            $collect = $ifCollect ? 1 : 0;//是否已收藏

            $ifReply = D('answer')->getOne($where);
            $reply = $ifReply ? 1 : 0;//是否已回答

        }else{
            $user_id['id'] = 0;
            $collect = 0;
            $reply = 0;
        }

        $page = I('page', 1, 'intval');//
        $limit = I('limit', 10, 'intval');//

        $begin = ($page-1)*$limit;//

        $where2 = ['A.question_id'=>$id];
        $order = ['A.add_time desc'];
        $res2 = D("answer")->Detail($where2,$order,$begin,$limit);

        foreach($res2['rows'] as &$v){
            $v['avatar'] = $this->DourlHandle($v['avatar']);//补全域名
            $v['add_time'] = $this->getDate($v['add_time']);

            if(!empty($v['images'])){
                $v['images'] = json_decode($v['images'],true);
                if(count($v['images']) > 3){
                    $v['images'] = array_slice($v['images'],0,3);//图片最多保留3个
                }
                foreach($v['images'] as &$val){
                    $val = $this->DoUrlHandle($val);//补全图片路径
                }
            }

            $v['share_num'] = $this->thousands($v['share_num']);//数千处理
            $v['comment_num'] = $this->thousands($v['comment_num']);
            $v['favour_num'] = $this->thousands($v['favour_num']);

            //对回答作者的关注状态
            $concerned = D('UserFollow')->concerned($user_id, $v['user_id']);
            $v['concerned'] = $concerned ? 1 : 0;

        }//foreachend;

        $res['collect'] = $collect;
        $res['reply'] = $reply;
        $res['answer'] = $res2['rows'];

        // 我的回答
        if( $this->UserAuthCheckLogin() ){
            $user = $this->AuthUserInfo;
        }else{
            $user['id'] = 0;
        }
        $where = [
            'question_id' => $id,
            'user_id' => $user['id']
        ];
        $answer = D('Answer')->where($where)->find();

        $data = [
            "list" => $res,
            'my_answer_id' => intval($answer['id']),
        ];
        return $data;
    }


    /**
     * Notes: 问题的回答列表
     * User: dede
     * Date: 2020/7/15
     * Time: 10:25 上午
     */
    public function answer()
    {
        $id = I('id', 0, 'intval');//问题id
        $res = D('question')->find($id);
        if(!$res) E("不存在此问题");

        $page = I('page', 1, 'intval');
        $limit = I('limit', 10, 'intval');
        $begin = ($page-1)*$limit;

        $where2 = ['A.question_id'=>$id];
        $order = ['A.add_time desc'];
        $data = D("answer")->Detail($where2,$order,$begin,$limit);
        $count = $data['total'];
        $totalpage = ceil($count / $limit);
        $this->SetPaginationHeader($totalpage,$count,$page,$limit);

        //是否登录
        if($this->UserAuthCheckLogin()){
            $user_id = $this->AuthUserInfo['id'];
        }else{
            $user_id['id'] = 0;
        }

        foreach($data['rows'] as $k=>&$v){
            $v['avatar'] = $this->DourlHandle($v['avatar']);//补全域名
            $v['add_time'] = $this->getDate($v['add_time']);
            $title = D('question')->where(['id' => $v['question_id']])->field(['title'])->find();
            $v['title'] = $title['title'];
            if(!empty($v['images'])){
                $v['images'] = json_decode($v['images'],true);
                if(count($v['images']) > 3){
                    $v['images'] = array_slice($v['images'],0,3);//图片最多保留3个
                }
                foreach($v['images'] as &$val){
                    $val = $this->DoUrlHandle($val);
                }
            }

            $v['share_num'] = $this->thousands($v['share_num']);//数千处理
            $v['comment_num'] = $this->thousands($v['comment_num']);
            $v['favour_num'] = $this->thousands($v['favour_num']);

            //对回答作者的关注状态
            $concerned = D('UserFollow')->concerned($user_id, $v['user_id']);
            $v['concerned'] = $concerned ? 1 : 0;

        }//foreachend;

        $data = [
            "list" => $data['rows'],
        ];
        return $data;
    }

    /**
     * Notes: 回答详情
     * User: dede
     * Date: 2020/7/15
     * Time: 10:32 上午
     */
    public function answerDetail()
    {
        $id = I('id', 0, 'intval');//回答id

        $where = [
            'A.id' => $id
        ];
        $res = D('answer')->answerDetail($where);
        if(!$res) E('不存在此回答');

        $res['avatar'] = $this->DoUrlHandle($res['avatar']);
        $res['add_time'] = $this->getDate($res['add_time']);
        //是否登录
        if($this->UserAuthCheckLogin()){
            $user_id = $this->AuthUserInfo['id'];

            //对回答作者的关注状态
            $concerned = D('UserFollow')->concerned($user_id, $res['user_id']);
            $res['concerned'] = $concerned ? 1 : 0;
            $question_praise = D('QuestionPraise')->where(['user_id' => $user_id,'type' => 1,'param' => $res['id']])->find();
            $res['is_praise'] = $question_praise ? 1 : 0;
        }else{
            //对回答作者的关注状态
            $res['concerned'] = 0;
            $res['is_praise'] = 0;
        }

        $res['images'] = json_decode($res['images'],true);

        $where = [
            'id' => [ 'lt', $res['id']],
            'question_id' => $res['question_id'],
            'draft' => 0,
        ];

        $next = D('Answer')->where($where)->order('add_time desc')->find();

        $data = [
            "list" => $res,
            'next_answer_id' => $next['id'] ? $next['id'] : 0,
        ];
        return $data;
    }

    /**
     * Notes: 回答评论列表
     * User: dede
     * Date: 2020/7/15
     * Time: 10:39 上午
     */
    public function answerComment(){
        $id = I('id', 0, 'intval');//回答id
        $page = I('page', 1, 'intval');
        $limit = I('limit', 10, 'intval');
        $begin = ($page-1)*$limit;

        $result = D('Answer')->find($id);
        if(!$result) E('不存在此回答');

        $where = [
            'AC.answer_id'=> $id,
            'replay_id' => 0,
        ];
        $order = "AC.add_time desc";
        $res = D('answerComment')->answerComment($where,$order,$begin,$limit);

        $list = $res['res'];
        $count = $res['count'];

        foreach($list as &$value){
            $value['add_time'] = $this->getDate($value['add_time']);
            $value['avatar'] = $this->DoUrlHandle($value['avatar']);
            $where = [
                'answer_id' => $id,
                'replay_id' => $value['id']
            ];
            $replay = D('answerComment')->replay($where);
            foreach ( $replay as &$item){
                $item['add_time'] = $this->getDate($value['add_time']);
                $item['avatar'] = $this->DoUrlHandle($value['avatar']);
            }
            $value['replay'] = $replay;
            //是否登录
            if($this->UserAuthCheckLogin()){
                $user_id = $this->AuthUserInfo['id'];
                $question_praise = D('QuestionPraise')->where(['user_id' => $user_id,'type' => 2,'param' => $value['id']])->find();
                $value['is_praise'] = $question_praise ? 1 : 0;
            }else{
                $value['is_praise'] = 0;
            }
        }

        $totalpage = ceil($count / $limit);
        $this->SetPaginationHeader($totalpage,$count,$page,$limit);

        $data = [
            "list" => $list
        ];
        return $data;
    }

    /**答题
     * DateTime 2020-07-22 17:21:00
     * author huangkedong
     * @return array
     */
    public function answerSheet(){
        $page = I('page',1,'intval');
        $limit = I('limit',10,'intval');
        $begin = ($page-1)*$limit;

        $filed = "id,title,answer_num";
        $order = "add_time desc";
        $res = D('question')->getTotal($filed,null,$order,$begin,$limit);

        $data = [
            "list" => $res
        ];
        return $data;
    }

    /**时间戳转日期格式
     * @时间戳 $time
     * @return false|string
     */
    protected function getDate($time){
        return date("Y-m-d",$time);
    }

    /**数字过万
     * @param $num
     * @return string
     */
    protected function thousands($num){
        if($num >= 10000){
            $num = number_format($num / 10000,1) . "万";
        }
        return $num;
    }

    /**数组截取
     * @param $array
     * @param $length
     * @return array
     */
    protected function arrayCut($array,$length){
        if(count($array) > $length){
            return array_slice($array,0,$length);
        }else{
            return $array;
        }
    }


}##