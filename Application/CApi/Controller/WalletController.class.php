<?php
namespace CApi\Controller;

class WalletController extends BaseLoggedController
{
    /**
     * Notes: 可提现金额
     * User: dede
     * Date: 2020/3/5
     * Time: 3:43 下午
     */
    public function index(){
        $user = $this->AuthUserInfo;
		$amount = D('WalletWithdraw')->where(array('status'=>0,'user_id'=>$user['id']))->field('SUM(amount) amount')->group('status')->find();
		if(empty($amount['amount'])){
			$amount['amount'] = 0;
		}
        $config = D('withdraw_config')->find(1);
        $data = [
            'user_money' => $user['user_money'],
            'examine'=>$amount['amount'],
            'remark' => $config['remark']
        ];
        return $data;
    }

    /**
     * Notes: 提现申请
     * User: dede
     * Date: 2020/3/5
     * Time: 3:35 下午
     * @return array
     * @throws \Think\Exception
     */
    public function withdraw(){
        $user = $this->AuthUserInfo;
        // 规则校验
        $result = D('Common/Withdraw')->check($user['id'], 1);
        if( !$result['success'] ){
            E($result['info']);
        }

        $amount = I('amount', 0, 'floatval');
        $account = I('account');
        $account_name = I('account_name');
        $mobile = I('mobile');
        $code = I('code');
        if(!isMobile($mobile)){
            E("手机号码格式不正确", 1002);
        }
        // 检查是否当前用户电话号码
        if( $mobile != $user['mobile'] ){
            E('手机号码和当前用户手机号码不一致');
        }
        if( !$code ){
            E('请输入验证码');
        }
        $this->CheckSmsCode($mobile, 'wallet', $code);
        if( !$amount ){
            E('请输入提现金额');
        }
        if( !$account ){
            E('请输入支付宝账号');
        }
        if( !$account_name ){
            E('请输入支付宝实名');
        }

        if( $amount > $user['user_money'] ){
            E('可提现余额不足');
        }

        $data = [
            'user_id' => $user['id'],
            'mobile' => $mobile,
            'amount' => $amount,
            'account' => $account,
            'account_name' => $account_name,
        ];
        $res = D('WalletWithdraw')->addOne($data);
        if( $res ){
            return [ 'wallet_withdraw_id' => $res ];
        }
        E('操作失败！');
    }

    /**
     * Notes: 提现申请记录
     * User: dede
     * Date: 2020/3/10
     * Time: 5:52 下午
     * @return array
     */
    public function withdrawHistory(){
        $page = I("get.page", 1);
        $row = I("get.row", 10);
        $offset = ($page-1)*$row;
        $user = $this->AuthUserInfo;
        $where = ['user_id' => $user['id']];
        $status = I('status', 0, 'intval');
        if( $status == 1 ){
            $where['status'] = 0;
        }else if( $status == 2 ){
            $where['status'] = 1;
        }
        $data = D('WalletWithdraw')->getList($where, $offset, $row);
        $list = [];
        $status = [
            0 => '待审核',
            1 => '已提现',
            -1 => '审核不通过',
        ];
        foreach ( $data['rows'] as $item ){
            $list[] = [
                'id' => $item['id'],
                'add_time' => date('Y-m-d', $item['add_time']),
                'amount' => $item['amount'],
                'remark' => $item['remark'],
                'status' => $item['status'],
                'status_title' => $status[$item['status']],
            ];
        }

        $count = $data['total'];
        $totalpage = ceil($count / $row);
        $this->SetPaginationHeader($totalpage, $count, $page, $row);
        $amount = D('WalletWithdraw')->amountDrawn($user['id']);

        return ['list' => $list, 'amount' => $amount];
    }
}