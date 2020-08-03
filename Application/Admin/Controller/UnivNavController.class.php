<?php
namespace Admin\Controller;

class UnivNavController extends BaseController
{

    public function index(){
        $type = I('type', 0, 'intval');
        $where = [ 'type' => $type ];
        $data = D('UnivNav')->where($where)->order('sort')->select();
        $this->assign('data', $data);
        $this->display();
    }

    public function update(){
        $id = I('id', 0, 'intval');
        if( IS_AJAX ){
            $data = [
                'title' => I('title'),
                'url' => I('url'),
                'sort' => defaultSort(),
            ];
            if( $id ){
                $result = D('UnivNav')->update($id, $data);
            }else{
                $result = D('UnivNav')->addOne($data);
            }
            if( $result ){
                $this->success();
            }
            $this->error('操作失败！');
        }
        $data = D('UnivNav')->getOne($id);
        $this->assign('data', $data);
        $this->display();
    }

    public function remove(){
        if( IS_AJAX ){
            $id = I('request.id', 0, 'intval');
            $result = D('UnivNav')->remove($id);
            if( $result ){
                $this->success();
            }
            $this->error('操作失败！');
        }
    }
}