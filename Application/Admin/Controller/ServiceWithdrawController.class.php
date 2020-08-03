<?php
namespace Admin\Controller;

class ServiceWithdrawController extends BaseController
{

    public function index(){
        $pagenum = I('p', 1, 'intval');
        $limit = 10;
        $where = [];
        $list = D('ServiceWithdraw')->getList($where, ($pagenum-1)*$limit, $limit, 'createtime', 'desc');
        $status_arr = [ 0 => '待审核', 1 => '审核通过', -1 => '审核不通过' ];
        foreach ( $list['rows'] as &$item){
            $item['status_name'] = $status_arr[$item['status']];
        }
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
        if( !$id ){
            $this->error('非法操作！');
        }
        if( IS_AJAX ){
            $data = [
                'status' => I('status', 0, 'intval'),
                'remark' => I('remark'),
            ];
            if( $data['status'] == 1 && !$data['remark'] ){
                $this->error('请输入备注');
            }
            D()->startTrans();
            if( $data['status'] ){
                $data['check_time'] = date('Y-m-d H:i:s');
                $data['check_admin'] = $_SESSION["manID"];
            }
            $res = D('ServiceWithdraw')->update($id, $data);
            if( $res ){
                if( $data['status'] == 1 ){
                    //发钱
					$info = D('ServiceWithdraw')->getOne($id);
					
					$ali_data = array(
						"ordersn"=>$info['sn'], "pay_type"=>'ALIPAY_LOGONID',
						"account"=>$info['ali_account'], "amount"=>$info['amount'],
						'show_name'=>'一点椿','real_name'=>$info['ali_name'],'remark'=>$data['remark'], 
					);
					$alimodel = D("Payment/AlipayTransfer");
					$parameter = $alimodel->AlipayTransfer($ali_data);
					
					$entity = array('result'=>$parameter);
					D('ServiceWithdraw')->update($id, $entity);
                }
                if( $res ){
                    D()->commit();
                    $this->success();
                }
            }
            D()->rollback();
            $this->error('操作失败！');
        }

        $data = D('ServiceWithdraw')->getOne($id);
        $this->assign('data', $data);
        $this->display();
    }
}