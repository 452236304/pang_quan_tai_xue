<?php
namespace Admin\Controller;

class AphorismController extends BaseController
{

    public function index(){
        $pagenum = I('p', 1, 'intval');
        $limit = 10;
        $where = [];
        $list = D('Aphorism')->getList($where, ($pagenum-1)*$limit, $limit, 'id', 'desc');

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
                'content' => I('content'),
                'author' => I('author'),
                'sort' => defaultSort(),
            ];
			$startdate = I('startdate');
			$enddate = I('enddate');
			if($startdate || $enddate){
				if(empty($startdate)){
					$this->ajaxError('请设置开始时间');
				}
				if(empty($enddate)){
					$this->ajaxError('请设置结束时间');
				}
				if($startdate>$enddate){
					$this->ajaxError('结束时间小于开始时间');
				}
				$map = array(
					"_complex"=>array(
						"startdate"=>array(
							array("egt", $startdate), array("elt", $enddate), "and"
						),
						"enddate"=>array(
							array("egt", $startdate), array("elt", $enddate), "and"
						),
						"_complex"=>array(
							"startdate"=>array("elt", $startdate), "enddate"=>array("egt", $enddate)
						),
						"_logic"=>"or"
					)
				);
				if($id){
					$map['id']=array('neq',$id);
				}
				$is_set=D('Aphorism')->where($map)->find();
				if($is_set){
					$this->ajaxError('当前时间段已存在寄语');
				}
				$data['startdate']=$startdate;
				$data['enddate']=$enddate;
			}
			
            $msg = '';
            if( !$data['content'] ){
                $msg .= '请输入内容<br/>';
            }
            if( !$data['author'] ){
                $data['author']='';
            }
            if($msg){
                $this->ajaxError($msg);
            }
            if( $id ){
                $res = D('Aphorism')->update($id, $data);
            }else{
                $res = D('Aphorism')->addOne($data);
            }
            $this->success();
        }
        $data = D('Aphorism')->getOne($id);
        $this->assign('data', $data);
        $this->display();
    }

    public function remove(){
        if( IS_AJAX ){
            $id = I('request.id');
            $result = D('Aphorism')->remove($id);
            if( $result ){
                $this->success();
            }
            $this->error('操作失败！');
        }
    }
}