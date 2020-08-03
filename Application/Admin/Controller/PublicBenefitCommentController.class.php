<?php
namespace Admin\Controller;

class PublicBenefitCommentController extends BaseController
{

    public function index(){
        $order = "id desc";
        $p = I("get.p");
        $param = ["p"=>$p];
        $map = [];

        $data = $this->pager("PublicBenefitComment", "10", $order, $map, $param);
        foreach ($data['data']  as &$item){
            $item['activity_name'] = D('PublicBenefit')->where(['id'=>$item['activity_id']])->getField('title')?:'';
            $item['user_name'] = D('User')->where(['id'=>$item['user_id']])->getField('nickname')?:'';
            $item['replay_name'] = $item['replay_id']?'回复评论':'主评论';
        }
        $this->assign($data);
        $this->display();
    }
    public function update(){
        $id = I('id', 0, 'intval');
        $data = D('PublicBenefitComment')->getOne($id);
        if($data){
            $data['title'] = D('PublicBenefit')->where(['id' => $data['activity_id']])->getField('title');
            $user_data= D('user')->where(['id' => $data['user_id']])->find();
            $data['nickname']=$user_data['nickname']?:'平台';
            $data['avatar'] =$user_data['avatar']?DoUrlHandle($user_data['avatar']):'';
            $data['replay_name'] = $data['replay_id']?'回复评论':'主评论';
        }
        $this->assign('data', $data);
        $this->display();
    }

    public function remove(){
        if( IS_AJAX ){
            $id = I('request.id', 0, 'intval');
            $res= D('PublicBenefitComment')->where(['id' => $id])->find();
            $result = D('PublicBenefitComment')->remove($id);
            if( $result ){
                $datas = ['id'=>$res['activity_id']];
                $datas['comment_num'] = D('PublicBenefit')->where(['id' => $res['activity_id']])->getField('comment_num')-1;
                if($datas['comment_num']<0)$datas['comment_num']=0;
                D('PublicBenefit')->save($datas);
                $this->success();
            }
            $this->error('操作失败！');
        }
    }
}