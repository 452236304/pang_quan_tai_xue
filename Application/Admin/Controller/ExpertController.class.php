<?php
namespace Admin\Controller;

class ExpertController extends BaseController
{

    public function index(){
        $pagenum = I('p', 1, 'intval');
        $limit = 10;
        $where = [];
        $list = D('Expert')->getList($where, ($pagenum-1)*$limit, $limit, 'id', 'desc');

        $data['data'] = $list['rows'];
        $page = new \Think\Page($list['total'],$limit);
        $page->setConfig("theme","%FIRST% %LINK_PAGE% %END%");
        if( $page->totalPages > 1 ){
            $data["pageshow"] = $page->show();
        }
        $this->assign($data);
        $this->display();
    }

    public function update(){
        $id = I('id', 0, 'intval');
        if( IS_AJAX ){
            $data = [
                'name' => I('name'),
                'titles' => I('titles'),
                'avatar' => I('images'),
                'org' => I('org'),
                'sort' => defaultSort(),
            ];
            $msg = '';
            if( !$data['name'] ){
                $msg .= '请输入姓名<br/>';
            }
            if( !$data['avatar'] ){
                $msg .= '请上传头像<br/>';
            }
            if( !$data['titles'] ){
                $msg .= '请输入职称<br/>';
            }
            if( !$data['org'] ){
                $msg .= '请输入机构名称<br/>';
            }
            if($msg){
                $this->error($msg);
            }
            if( $id ){
                $res = D('Expert')->update($id, $data);
            }else{
                $res = D('Expert')->addOne($data);
            }
            if( $res ){
                $this->success();
            }
            $this->error('操作失败！');
        }
        $data = D('Expert')->getOne($id);
        $this->assign('data', $data);
        $this->display();
    }
}