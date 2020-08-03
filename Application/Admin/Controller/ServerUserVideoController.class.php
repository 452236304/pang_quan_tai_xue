<?php
namespace Admin\Controller;

class ServerUserVideoController extends BaseController
{

    public function index(){
        $data = D('ServerUserVideo')->order('sort')->select();
        $this->assign('data', $data);
        $this->display();
    }

    public function sortad(){
        $id = I("post.id");
        $sort = I("post.sort");
        if(count($id)>0){
            $model = D("ServerUserVideo");
            foreach ($id as $key=>$val){
                $model->where("id=".$val)->setField("sort", $sort[$key]);
            }
            $this->redirect("index");
            exit();
        }else{
            $this->assign("jumpUrl", U("index"));
            $this->error("没有进行任何操作");
            exit();
        }
    }

    public function update(){
        $id = I('id', 0, 'intval');
        if( IS_AJAX ){
            $data = [
                'title' => I('title'),
                'thumb_img' => I('images'),
                'video_url' => I('video'),
                'sort' => defaultSort(),
            ];
            $msg = '';
            if( !$data['title'] ){
                $msg += '请输入标题<br/>';
            }
            if( !$data['thumb_img'] ){
                $msg += '请上传缩略图<br/>';
            }
            if( !$data['video_url'] ){
                $msg += '请上传视频<br/>';
            }
            if( $msg ){
                $this->error($msg);
            }
            if($id){
                $res = D('ServerUserVideo')->update($id, $data);
            }else{
                $res = D('ServerUserVideo')->addOne($data);
            }
            if( $res ){
                $this->success();
            }
            $this->error('操作失败!');
        }
        $data = D('ServerUserVideo')->getOne($id);
        $this->assign('data', $data);
        $this->display();
    }

    public function remove(){
        if( IS_AJAX ){
            $id = I('request.id');
            $result = D('ServerUserVideo')->remove($id);
            if( $result ){
                $this->success();
            }
            $this->error('操作失败！');
        }
    }

}