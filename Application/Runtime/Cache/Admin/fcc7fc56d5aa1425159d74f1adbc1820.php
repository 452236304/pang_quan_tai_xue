<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html>


<!-- Mirrored from www.zi-han.net/theme/hplus/ by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 20 Jan 2016 14:16:41 GMT -->
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="renderer" content="webkit">
    <meta http-equiv="Cache-Control" content="no-siteapp" />
    <title>思久科技后台管理系统</title>

    <meta name="keywords" content="思久科技后台管理系统,响应式后台">
    <meta name="description" content="思久科技后台管理系统是一个完全响应式，基于Bootstrap3最新版本开发的扁平化主题，她采用了主流的左右两栏式布局，使用了Html5+CSS3等现代技术">

    <!--[if lt IE 9]>
    <meta http-equiv="refresh" content="0;ie.html" />
    <![endif]-->

    <link rel="shortcut icon" href="favicon.ico">
    
    <link rel="shortcut icon" href="favicon.ico"> 
    <link href="/Public/Admin/css/bootstrap.min14ed.css?v=3.3.6" rel="stylesheet">
    <link href="/Public/Admin/css/font-awesome.min93e3.css?v=4.4.0" rel="stylesheet">
    <link href="/Public/Admin/css/plugins/iCheck/custom.css" rel="stylesheet">
    <link href="/Public/Admin/css/plugins/chosen/chosen.css" rel="stylesheet">
    <link href="/Public/Admin/css/plugins/colorpicker/css/bootstrap-colorpicker.min.css" rel="stylesheet">
    <link href="/Public/Admin/css/plugins/cropper/cropper.min.css" rel="stylesheet">
    <link href="/Public/Admin/css/plugins/switchery/switchery.css" rel="stylesheet">
    <link href="/Public/Admin/css/plugins/jasny/jasny-bootstrap.min.css" rel="stylesheet">
    <link href="/Public/Admin/css/plugins/nouslider/jquery.nouislider.css" rel="stylesheet">
    <link href="/Public/Admin/css/plugins/datapicker/datepicker3.css" rel="stylesheet">
    <link href="/Public/Admin/css/plugins/ionRangeSlider/ion.rangeSlider.css" rel="stylesheet">
    <link href="/Public/Admin/css/plugins/ionRangeSlider/ion.rangeSlider.skinFlat.css" rel="stylesheet">
    <!-- <link href="/Public/Admin/css/plugins/awesome-bootstrap-checkbox/awesome-bootstrap-checkbox.css" rel="stylesheet"> -->
    <link href="/Public/Admin/css/plugins/clockpicker/clockpicker.css" rel="stylesheet">
    <link href="/Public/Admin/css/animate.min.css" rel="stylesheet">
    <link href="/Public/Admin/css/plugins/fullcalendar/fullcalendar.css" rel="stylesheet">
    <link href="/Public/Admin/css/plugins/fullcalendar/fullcalendar.print.css" rel="stylesheet">
    <link href="/Public/Admin/css/plugins/summernote/summernote.css" rel="stylesheet">
    <link href="/Public/Admin/css/plugins/summernote/summernote-bs3.css" rel="stylesheet">
    <link href="/Public/Admin/css/style.min862f.css?v=4.1.0" rel="stylesheet">

    <link href="/Public/Admin/css/bootstrap.min14ed.css?v=3.3.6" rel="stylesheet">
    <link href="/Public/Admin/css/font-awesome.min93e3.css?v=4.4.0" rel="stylesheet">
    <link href="/Public/Admin/css/animate.min.css" rel="stylesheet">
    <link href="/Public/Admin/css/style.min862f.css?v=4.1.0" rel="stylesheet">
    <link href="/Public/Admin/css/plugins/chosen/chosen.css" rel="stylesheet">
    <link href="/Public/Admin/css/style.min862f.css?v=4.1.0" rel="stylesheet">
    <link href="/Public/Admin/css/H-ui.css" rel="stylesheet">

    <link href="/Public/Admin/css/global.css" rel="stylesheet">
    <link href="/Public/Admin/plugins/layui/css/layui.css" rel="stylesheet">
    <link href="/Public/Admin/css/plugins/chosen/chosen.css" rel="stylesheet">
    <link href="/Public/Admin/css/plugins/bootstrap-table/bootstrap-table.min.css" rel="stylesheet">
</head>
<div class="col-sm-12">
	<button style="z-index:100;position:fixed;bottom:10px;left:10px;" class="btn btn-warning" type="button" onclick="javascript:location.reload()">刷新</button>
</div>
<style>
    .header_title {
        text-align: center;
        font-size: 20px;
        cursor: pointer;
    }

    .header_active {
        font-weight: bold;
    }
</style>

<body class="gray-bg">
    <form id="form1" name="form1" method="post"
        action="<?php echo U('ServiceOrder/listad', 'type='.$map['type'].'&admin_status='.$map['admin_status']);?>">
        <div class="wrapper wrapper-content animated fadeInRight">
            <div class="row">
                <?php if($_GET['status']!='sh'){ ?>
                <div class="col-sm-12">
                    <div class="ibox float-e-margins row">
                        <div class="col-sm-2 header_title <?php echo $_GET['type']=='2'?'header_active':''; ?>"
                            onclick="javascript:location.href='<?php echo U('ServiceOrder/listad').'?type=2';?>'">
                            服务订单</div>
                        <div class="col-sm-2 header_title <?php echo $_GET['type']=='1'?'header_active':''; ?>"
                            onclick="javascript:location.href='<?php echo U('ServiceOrder/listad').'?type=1';?>'">
                            送餐订单</div>
                        <div class="col-sm-2 header_title <?php echo $_GET['status']=='sy'?'header_active':''; ?>"
                            onclick="javascript:location.href='<?php echo U('ServiceOrder/listad').'?status=sy';?>'">爽约订单</div>
                        <div class="col-sm-2 header_title <?php echo $_GET['status']=='dq'?'header_active':''; ?>"
                            onclick="javascript:location.href='<?php echo U('ServiceOrder/listad').'?status=dq';?>'">待抢订单</div>
                        <div class="col-sm-2 header_title <?php echo $_GET['admin_status']=='2'?'header_active':''; ?>"
                            onclick="javascript:location.href='<?php echo U('ServiceOrder/listad').'?admin_status=2';?>'">审核不通过订单
                        </div>
                    </div>
                </div>
                <?php } ?>
                <div class="col-sm-12">
                    <div class="ibox float-e-margins row">
                        <div class="col-sm-3">
                            <!-- <select class="form-control" name="pay_status">
                                <option value="-1">所有支付状态</option>
                                <option value='3' <?php echo (getSelect(select,3,$where["pay_status"])); ?>>已支付</option>
                                <option value="0" <?php echo (getSelect(select,'0',$where["pay_status"])); ?>>未支付</option>
                            </select> -->
                        </div>
                        <div class="col-sm-3">
                            <select class="form-control" name='orderstatus'>
                                <option value="-1">所有订单状态</option>
                                <option value="1" <?php echo (getSelect(select,1,$where["orderstatus"])); ?>>已超时</option>
                                <option value="2" <?php echo (getSelect(select,2,$where["orderstatus"])); ?>>待付款</option>
                                <option value="3" <?php echo (getSelect(select,3,$where["orderstatus"])); ?>>已取消</option>
                                <option value="4" <?php echo (getSelect(select,4,$where["orderstatus"])); ?>>已完成</option>
                                <option value="5" <?php echo (getSelect(select,5,$where["orderstatus"])); ?>>申请退款</option>
                                <option value="6" <?php echo (getSelect(select,6,$where["orderstatus"])); ?>>已退款</option>
                                <option value="7" <?php echo (getSelect(select,7,$where["orderstatus"])); ?>>待审核</option>
                                <option value="8" <?php echo (getSelect(select,8,$where["orderstatus"])); ?>>待接单</option>
                                <option value="9" <?php echo (getSelect(select,9,$where["orderstatus"])); ?>>待确认开始</option>
                                <option value="10" <?php echo (getSelect(select,10,$where["orderstatus"])); ?>>服务中</option>
                                <option value="11" <?php echo (getSelect(select,11,$where["orderstatus"])); ?>>待确认完成</option>
                                <option value="12" <?php echo (getSelect(select,12,$where["orderstatus"])); ?>>线下评估中</option>
                                <option value="13" <?php echo (getSelect(select,13,$where["orderstatus"])); ?>>待缴付尾款</option>
                            </select>
                        </div>
                        <div class="input-group col-sm-6">
                            <input type="text" placeholder="流水号 / 用户昵称 / 标题  / 联系人 / 联系电话" name="keyword"
                                class="form-control" value="<?php echo ($map["keyword"]); ?>">
                            <span class="input-group-btn">
                                <button type="submit" class="btn btn-primary">搜索</button>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="ibox float-e-margins">
                        <div class="ibox-content">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th width="100" class="TC">ID</th>
                                        <th width="100" class="TC">流水号</th>
                                        <th width="200">标题</th>
                                        <?php if($map["type"] == 2): ?><th width="150" class="TC">服务栏目</th>
                                            <th width="150" class="TC">服务人员姓名</th>
                                            <th width="100" class="TC">服务等级</th>
                                            <th width="100" class="TC">服务时长</th><?php endif; ?>
                                        <?php if($map["type"] == 1): ?><th width="100" class="TC">餐次</th><?php endif; ?>
                                        <th width="100" class="TC">用户昵称</th>
                                        <th width="100" class="TC">联系人</th>
                                        <th width="100" class="TC">联系电话</th>
                                        <th width="100" class="TC">地区</th>
                                        <th width="150" class="TC">订单状态</th>
                                        <th width="100" class="TC">订单金额</th>
                                        <!-- <th width="150" class="TC">审核</th> -->
                                        <th width="150" class="TC">下单时间</th>
                                        <?php if($map["status"] == 'sy'): ?><th width="150" class="TC">爽约时间</th><?php endif; ?>
                                        <th width="150" class="TC">订单来源</th>
                                        <th width="300" class="TC">操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(is_array($data)): $n = 0; $__LIST__ = $data;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($n % 2 );++$n;?><tr>
                                            <td align="center"><?php echo ($vo["id"]); ?><input type="hidden" name="id[]"
                                                    value="<?php echo ($vo["id"]); ?>" />
                                            </td>
                                            <td align="center"><?php echo ($vo["sn"]); ?></td>
                                            <td><?php echo ($vo["title"]); ?></td>
                                            <?php if($map["type"] == 2): ?><td align="center"><?php echo ($vo["category"]); ?></td>
                                                <td align="center"><?php echo ($vo["service_realname"]); ?></td>
                                                <td align="center">
                                                    <?php if($vo["service_level"] > 0): echo ($vo["service_level"]); ?>星
                                                        <?php else: ?>
                                                        -<?php endif; ?>
                                                </td>
                                                <td align="center"><?php echo ($vo["time"]); echo (getTimeType($vo["time_type"])); ?></td><?php endif; ?>
                                            <?php if($map["type"] == 1): ?><td align="center"><?php echo (getMealLevel($vo["res_type"])); ?></td><?php endif; ?>
                                            <td align="center"><?php echo ($vo["nickname"]); ?></td>
                                            <td align="center"><?php echo ($vo["contact"]); ?></td>
                                            <td align="center"><?php echo ($vo["mobile"]); ?></td>
                                            <td align="center"><?php echo ($vo["province"]); ?> / <?php echo ($vo["city"]); ?> / <?php echo ($vo["region"]); ?></td>
                                            <td align="center">
                                                <?php if($vo["status"] == 1 and $vo["pay_status"] != 3 and $vo['createdate'] < $where['outtime']): ?>已超时
                                                    <?php elseif($vo["status"] == 1 and $vo["pay_status"] != 3): ?>
                                                    待付款
                                                    <?php elseif($vo["status"] == 2): ?>
                                                    已取消
                                                    <?php elseif($vo["status"] == 4): ?>
                                                    已完成
                                                    <?php elseif($vo["status"] == 5): ?>
                                                    申请退款
                                                    <?php elseif($vo["status"] == 6): ?>
                                                    已退款
                                                    <?php elseif($vo["status"] == 1 and $vo["admin_status"] == 2): ?>
                                                    审核不通过
                                                    <?php elseif($vo["status"] == 1 and $vo["admin_status"] == 0): ?>
                                                    待审核
                                                    <?php elseif($vo["status"] == 1 and $vo["admin_status"] == 1 and $vo["service_userid"] == 0): ?>
                                                    待接单
                                                    <?php elseif($vo["assess"] == 1 and $vo["status"] == 1 and $vo["assess_status"] == 1): ?>
                                                    线下评估中
                                                    <?php elseif($vo["assess"] == 1 and $vo["status"] == 1 and $vo["assess_status"] == 2 and $vo["again_status"] == 1): ?>
                                                    待缴付尾款
                                                    <?php elseif($vo["status"] == 1 and $vo["admin_status"] == 1 and $vo["service_userid"] > 0 and $vo["execute_status"] == 0): ?>
                                                    <?php if($vo["type"] == 1): ?>等待配送
                                                        <?php else: ?>
                                                        等待服务<?php endif; ?>
                                                    <?php elseif($vo["status"] == 1 and $vo["admin_status"] == 1 and $vo["service_userid"] > 0 and $vo["execute_status"] == 1): ?>
                                                    <?php if($vo["type"] == 1): ?>配送中
                                                        <?php else: ?>
                                                        待确认开始服务<?php endif; ?>
                                                    <?php elseif($vo["status"] == 1 and $vo["admin_status"] == 1 and $vo["service_userid"] > 0 and $vo["execute_status"] == 2): ?>
                                                    <?php if($vo["type"] == 1): ?>配送中
                                                        <?php else: ?>
                                                        服务中<?php endif; ?>
                                                    <?php elseif($vo["status"] == 1 and $vo["admin_status"] == 1 and $vo["service_userid"] > 0 and $vo["execute_status"] == 3): ?>
                                                    待确认完成
                                                    <?php elseif($vo["status"] == 1 and $vo["admin_status"] == 1 and $vo["service_userid"] > 0 and $vo["execute_status"] == 7): ?>
                                                    已爽约<?php endif; ?>
                                            </td>
                                            <td align="center">
                                                <?php if($vo["again_count"] > 0): ?><p><?php echo (calc($vo["amount"],$vo['again_price'],'+')); ?></p>
                                                    <?php else: ?>
                                                    <p><?php echo ($vo["amount"]); ?></p><?php endif; ?>
                                            </td>
                                            <!-- <td align="center">
                                                <?php if($vo["admin_status"] == 1): ?>已通过
                                                    <?php elseif($vo["admin_status"] == 2): ?>
                                                    不通过
                                                    <?php else: ?>
                                                    待审核<?php endif; ?>
                                            </td> -->
                                            <td align="center"><?php echo (date("Y-m-d H:i",strtotime($vo["createdate"]))); ?></td>
                                            <?php if($map["status"] == 'sy'): ?><td align="center"><?php echo (date("Y-m-d H:i",strtotime($vo["execute_time"]))); ?>
                                                </td><?php endif; ?>
                                            <td align="center">
                                                <?php switch($vo["hybrid"]): case "app": ?>app<?php break;?>
                                                    <?php case "xcx": ?>小程序<?php break;?>
                                                    <?php default: ?>未知<?php endswitch;?>
                                            </td>
                                            <td class="text-navy" align="center">
                                                <p>
                                                    <a href="<?php echo U('ServiceOrder/modifyad','id='.$vo['id'].'&type='.$map['type'].'&admin_status='.$map['admin_status'].'&p='.$map['p'].'&status='.$map['status']);?>">查看<?php echo ($map["status"] != 'sh' ? '修改' : ''); ?></a>
                                                    <?php if($vo["commentid"] > 0): ?><a href="<?php echo U('ServiceOrder/comment','id='.$vo['commentid']);?>"
                                                            class="J-OpenTab" data-index="service_comment_<?php echo ($vo["id"]); ?>"
                                                            data-title="订单<?php echo ($vo["sn"]); ?>-查看评论">查看评论</a><?php endif; ?>
                                                </p>
                                                <?php if($vo["type"] == 2 and (($vo["status"] == 1 and $vo["admin_status"] == 1 and $vo["service_userid"] > 0 and $vo["execute_status"] == 3) or ($vo["status"] == 4 and $vo["execute_status"] == 4))): ?><p>
                                                        <a href="<?php echo U('ServiceOrder/carerecord','orderid='.$vo['id']);?>"
                                                            class="J-OpenTab" data-index="care_record_<?php echo ($vo["id"]); ?>"
                                                            data-title="订单<?php echo ($vo["sn"]); ?>-服务总结">查看服务总结</a>
                                                    </p><?php endif; ?>
                                                <p>
                                                    <?php if($map["status"] == 'sh'): ?><a href="<?php echo U('ServiceOrderRefund/modifyad','orderid='.$vo['id']);?>"
                                                            class="J-OpenTab" data-index="service_refund_<?php echo ($vo["id"]); ?>"
                                                            data-title="<?php echo ($map['type'] == 1?'送餐':'服务'); ?>订单<?php echo ($vo["sn"]); ?>-售后原因">售后原因</a><?php endif; ?>
                                                    <?php if(($vo["service_userid"] == 0 and $vo["admin_status"] == 0) or ($vo["service_userid"] > 0 and $vo["admin_status"] == 0) or ($map["status"] == 'sy') or ($map["status"] == 'dq') or ($vo["status"] == 1 and $vo["admin_status"] == 1 and $vo["service_userid"] == 0) ): ?><a href="<?php echo U('ServiceOrder/appoint','id='.$vo['id']);?>"
                                                            class="J-OpenTab" data-index="service_appoint_<?php echo ($vo["id"]); ?>"
                                                            data-title="订单<?php echo ($vo["sn"]); ?>-指派服务人员">指派服务人员</a><?php endif; ?>
                                                </p>

                                                <?php if($map["status"] == 'sy'): ?><p>
                                                        <a href="javascript:;" data-url="<?php echo U('ServiceOrder/removead','id='.$vo['id'].'&type='.$map['type'].'&admin_status='.$map['admin_status'].'&p='.$map['p'].'&status='.$map['status']);?>"
                                                            class="J-removead">解除爽约</a>
                                                    </p><?php endif; ?>
                                                <?php if(($vo["assess"] == 1 or $vo["doctor"] == 1 or $vo["time_type"] == 4) and $vo["admin_status"] == 0): ?><p>
                                                        <a href="<?php echo U('ServiceOrder/orderform','id='.$vo['id']);?>"
                                                            class="J-OpenTab" data-index="service_orderform_<?php echo ($vo["id"]); ?>"
                                                            data-title="订单<?php echo ($vo["sn"]); ?>-设置服务表单">设置服务表单</a>
                                                    </p>
                                                    <?php elseif(($vo["assess"] == 1 or $vo["doctor"] == 1 or $vo["time_type"] == 4) and $vo["admin_status"] == 1): ?>
                                                    <p>
                                                        <a href="<?php echo U('ServiceOrder/orderform','id='.$vo['id']);?>"
                                                            class="J-OpenTab" data-index="service_orderform_<?php echo ($vo["id"]); ?>"
                                                            data-title="订单<?php echo ($vo["sn"]); ?>-查看服务表单">查看服务表单</a>
                                                    </p>
                                                    <p>
                                                        <a href="<?php echo U('ServiceOrder/orderformrecord','orderid='.$vo['id']);?>"
                                                            class="J-OpenTab"
                                                            data-index="service_orderformrecord_<?php echo ($vo["id"]); ?>"
                                                            data-title="订单<?php echo ($vo["sn"]); ?>-查看服务表单">查看服务表单日志</a>
                                                    </p><?php endif; ?>
												<?php if($vo["status"] == 1 and $vo["pay_status"] != 3): ?><a href="<?php echo U('ServiceOrder/change_price','id='.$vo['id'].'&p='.$map['p']);?>">修改订单价格</a><?php endif; ?>
                                            </td>
                                        </tr><?php endforeach; endif; else: echo "" ;endif; ?>
                                </tbody>
                            </table>
                            <table class="table table-striped table-hover">
                                <tr>
                                    <td align="center"><?php echo ($pageshow); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <div id="modal-demo" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content radius">
            <div class="modal-header">
                <h3 class="modal-title">对话框标题</h3>
                <a class="close" data-dismiss="modal" aria-hidden="true" href="javascript:void();">×</a>
            </div>
            <div class="modal-body">
                <p>对话框内容…</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary">确定</button>
                <button class="btn" data-dismiss="modal" aria-hidden="true">关闭</button>
            </div>
        </div>
    </div>
</div>
<script src="/Public/Admin/js/jquery.min.js?v=2.1.4"></script>
<script src="/Public/Admin/js/bootstrap.min.js?v=3.3.6"></script>
<script src="/Public/Admin/js/content.min.js?v=1.0.0"></script>
<script src="/Public/Admin/js/plugins/chosen/chosen.jquery.js"></script>
<script src="/Public/Admin/js/plugins/jsKnob/jquery.knob.js"></script>
<script src="/Public/Admin/js/plugins/jasny/jasny-bootstrap.min.js"></script>
<script src="/Public/Admin/js/plugins/datapicker/bootstrap-datepicker.js"></script>
<script src="/Public/Admin/js/plugins/prettyfile/bootstrap-prettyfile.js"></script>
<script src="/Public/Admin/js/plugins/switchery/switchery.js"></script>
<script src="/Public/Admin/js/plugins/ionRangeSlider/ion.rangeSlider.min.js"></script>
<script src="/Public/Admin/js/plugins/metisMenu/jquery.metisMenu.js"></script>
<script src="/Public/Admin/js/plugins/colorpicker/bootstrap-colorpicker.min.js"></script>
<script src="/Public/Admin/js/plugins/clockpicker/clockpicker.js"></script>
<script src="/Public/Admin/js/plugins/cropper/cropper.min.js"></script>
<script src="/Public/Admin/js/plugins/iCheck/icheck.min.js"></script>
<script src="/Public/Admin/js/demo/form-advanced-demo.min.js"></script>

<!--<script src="/Public/Admin/js/jquery.min.js?v=2.1.4"></script>-->
<script src="/Public/Admin/js/bootstrap.min.js?v=3.3.6"></script>
<script src="/Public/Admin/js/plugins/metisMenu/jquery.metisMenu.js"></script>
<script src="/Public/Admin/js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="/Public/Admin/js/plugins/layer/layer.min.js"></script>
<!-- 时间 -->
<script src="/Public/Admin/js/plugins/layer/laydate/laydate.js"></script>
<script src="/Public/Admin/js/hplus.min.js?v=4.1.0"></script>
<script src="/Public/Admin/js/contabs.min.js" type="text/javascript"></script>
<script src="/Public/Admin/js/plugins/pace/pace.min.js"></script>
<script src="/Public/Admin/js/H-ui/H-ui.js"></script>

<script src="/Public/Admin/plugins/layui/layui.js"></script>
<script src="/Public/Admin/js/global.js"></script>
<script src="/Public/Admin/js/list.js"></script>

<script src="/Public/ckfinder/ckfinder.js"></script>
<script src="/Public/ckfinder/handle.js"></script>
<script src="/Public/Admin/js/plugins/chosen/chosen.jquery.js"></script>
<script src="/Public/Admin/js/plugins/bootstrap-table/bootstrap-table.min.js"></script>


<!-- 配置文件 -->
<script type="text/javascript" src="/Application/Admin/View/xedit/ueditor.config.js"></script>
<!-- 编辑器源码文件 -->
<script type="text/javascript" src="/Application/Admin/View/xedit/ueditor.all.js"></script>
<!--<script type="text/javascript" src="/Public/ckeditor/ckeditor.js"></script>-->
<script type="text/javascript">
    window.InitUEditor = function (id, name) {
        if (!id) {
            id = "content";
        }
        if (!name) {
            name = "content";
        }
        var editor = UE.getEditor(id, {
            toolbars: [
                ['fullscreen', 'source', 'undo', 'redo', 'bold', 'italic', 'underline', 'forecolor', 'backcolor', 'justifyleft', 'justifyright', 'justifycenter', 'justifyjustify', 'strikethrough', 'subscript', 'simpleupload', 'insertimage', 'audio', 'inserttable', 'edittable', 'edittd', 'link']
            ],
            autoHeightEnabled: false,
            autoFloatEnabled: false,
            textarea: name,
            removeFormatAttributes: ""
        }).addListener('beforefullscreenchange', function (event, isFullScreen) {
            if (isFullScreen) {
                $("body").addClass("noscroll")
                $("#isFullScreen").css({ "height": "100%", "width": "100%", "left": 0 });
            } else {
                $("body").removeClass("noscroll")
                $("#isFullScreen").css({ "height": "400px", "width": "98%", "left": "16px" });
            }
        });

        return editor;
    }

    $("a.J-OpenTab").on("click", function () {
        var o = $(this).attr("href"),
            m = $(this).data("index"),
            l = $.trim($(this).data("title"));
        return OpenTab(o, m, l);
    });

    window.OpenTab = function ($o, $m, $l) {
        parent.window.ContabsOpenTab($o, $m, $l);
        return false;
    }

    $("input[type='number']").on("keypress", function () {
        return (/[0-9\.]/.test(String.fromCharCode(event.keyCode)));
    });

	window.tipsnews = function(role,group,num){
	    $("#side-menu li").each(function(index, el) {
	     var url = $(this).find('.J_menuItem').first().attr('href');
	     var str = '/business.html';
	     if( url&&url.indexOf(str)!=-1 ){
			$(this).find('.J_menuItem').first().find('.pull-right').remove();
	        if(num)$(this).find('.J_menuItem').first().append('<span class="label label-warning pull-right">'+num+'</span>');
	     }
	    });
	}
	
	
</script>
<!--<iframe src="https://www.ydchun.com/html/business.html?account=<?php echo ($sysuserinfo["truename"]); ?>&pwd=123456&id=<?php echo ($sysuserinfo["id"]); ?>" style="height:0;width:0;"></iframe>-->
</body>
<!-- Mirrored from www.zi-han.net/theme/hplus/ by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 20 Jan 2016 14:17:11 GMT -->

</html>
    <script type="text/javascript">

        $(function () {
            $("a.J-removead").on("click", function () {
                var url = $(this).data("url");
                layer.confirm("是否确认解除服务订单爽约？", { btn: ["确认", "取消"] }, function () {
                    window.location.href = url;
                });
            });
        });

    </script>