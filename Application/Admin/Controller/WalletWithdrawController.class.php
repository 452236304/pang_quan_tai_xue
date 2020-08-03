<?php
namespace Admin\Controller;

class WalletWithdrawController extends BaseController
{

    public function index(){
        $pagenum = I('p', 1, 'intval');
        $limit = 10;
        $where = [];
        $list = D('WalletWithdraw')->getList($where, ($pagenum-1)*$limit, $limit, 'id', 'desc');
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
            if( $data['status'] == -1 && !$data['remark'] ){
                $this->error('请输入备注');
            }
            D()->startTrans();
            if( $data['status'] ){
                $data['check_time'] = time();
                $data['check_admin'] = $_SESSION["manID"];
            }
            $res = D('WalletWithdraw')->update($id, $data);
            if( $res ){
                if( $data['status'] == 1 ){
                    // 检查余额
                    $wallet = D('WalletWithdraw')->getOne($id);
                    $user = D('User')->find($wallet['user_id']);
                    if( $wallet['amount'] > $user['user_money'] ){
                        D()->rollback();
                        $this->error('用户余额不足');
                    }

                    // 支付宝打款
                    $info = D('WalletWithdraw')->getOne($id);
                    $ali_data = array(
                        "ordersn"=>$info['id'],
                        "pay_type"=>'ALIPAY_LOGONID',
                        "account"=>$info['account'],
                        "amount"=>$info['amount'],
                        'show_name'=>'一点椿',
                        'real_name'=>$info['account_name'],
                        'remark'=>$data['remark'],
                    );
                    $alimodel = D("Payment/AlipayTransfer");
                    $resultCode = $alimodel->AlipayTransfer($ali_data);
                    $entity = array('result'=>$resultCode);
                    D('WalletWithdraw')->update($id, $entity);
                    if(!empty($resultCode)&&$resultCode == 10000){

                    } else {
                        D()->rollback();
                        $this->error('支付宝转账失败');
                    }

                    // 添加钱包日志
                    $log = [
                        'user_id' => $user['id'],
                        'adjust' => -$wallet['amount'],
                        'remark' => '提现',
                    ];
                    $res = D('WalletLog')->addOne($log);
                    if( !$res ){
                        D()->rollback();
                        $this->error('操作失败！');
                    }
                    $save_user = [
                        'id' => $user['id'],
                        'user_money' => $user['user_money'] - $wallet['amount'],
                    ];
                    $res = D('User')->save($save_user);
                }
                if( $res ){
                    D()->commit();
                    $this->success();
                }
            }
            D()->rollback();
            $this->error('操作失败！');
        }

        $data = D('WalletWithdraw')->getOne($id);
        $this->assign('data', $data);
        $this->display();
    }
}