<?php
/* *
 * 功能：单笔转账到支付宝账户接口(alipay.fund.trans.toaccount.transfer)接口业务参数封装
 * 版本：2.0
 * 修改日期：2020-06-17
 */


class AlipayTradeTransferContentBuilder
{

    // 商户转账唯一订单号。发起转账来源方定义的转账单据ID，用于将转账回执通知给来源方。
    private $out_biz_no;

    // 收款方账户类型。可取值：1、ALIPAY_USERID：支付宝账号对应的支付宝唯一用户号。以2088开头的16位纯数字组成。2、ALIPAY_LOGONID：支付宝登录号，支持邮箱和手机号格式。
    private $payee_type;

    // 收款方账户。与payee_type配合使用。付款方和收款方不能是同一个账户。
    private $payee_account;

    // 转账金额，单位：元。
    private $amount;

    // 付款方姓名（最长支持100个英文/50个汉字）。显示在收款方的账单详情页。如果该字段不传，则默认显示付款方的支付宝认证姓名或单位名称。
    private $payer_show_name;

    // 收款方真实姓名（最长支持100个英文/50个汉字）。
    private $payee_real_name;

    // 转账备注（支持200个英文/100个汉字）。
    private $remark	;

    private $bizContentarr = array();

    private $bizContent = NULL;

    public function getBizContent()
    {
        if(!empty($this->bizContentarr)){
            $this->bizContent = json_encode($this->bizContentarr,JSON_UNESCAPED_UNICODE);
        }
        return $this->bizContent;
    }

    public function __construct()
    {
        $this->bizContentarr['productCode'] = "QUICK_MSECURITY_PAY";
    }

    public function AlipayTradeWapPayContentBuilder()
    {
        $this->__construct();
    }

    public function getOut_biz_no()
    {
        return $this->out_biz_no;
    }
    
    public function setOut_biz_no($out_biz_no)
    {
        $this->out_biz_no = $out_biz_no;
        $this->bizContentarr['out_biz_no'] = $out_biz_no;
    }
	
	public function getPayee_type()
	{
	    return $this->payee_type;
	}
	
	public function setPayee_type($payee_type)
	{
	    $this->payee_type = $payee_type;
	    $this->bizContentarr['payee_type'] = $payee_type;
	}

    public function getPayee_account()
    {
        return $this->payee_account;
    }
    
    public function setPayee_account($payee_account)
    {
        $this->payee_account = $payee_account;
        $this->bizContentarr['payee_account'] = $payee_account;
    }
	
	public function getAmount()
	{
	    return $this->amount;
	}
	
	public function setAmount($amount)
	{
	    $this->amount = $amount;
	    $this->bizContentarr['amount'] = $amount;
	}
	
	public function getPayer_show_name()
	{
	    return $this->payer_show_name;
	}
	
	public function setPayer_show_name($payer_show_name)
	{
	    $this->payer_show_name = $payer_show_name;
	    $this->bizContentarr['payer_show_name'] = $payer_show_name;
	}
	
	public function getPayee_real_name()
	{
	    return $this->payee_real_name;
	}
	
	public function setPayee_real_name($payee_real_name)
	{
	    $this->payee_real_name = $payee_real_name;
	    $this->bizContentarr['payee_real_name'] = $payee_real_name;
	}
	
	public function getRemark()
	{
	    return $this->remark;
	}
	
	public function setRemark($remark)
	{
	    $this->remark = $remark;
	    $this->bizContentarr['remark'] = $remark;
	}
}

?>