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


</head>

<body class="gray-bg">
    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="row">
            <div class="col-sm-12">
                <div class="ibox float-e-margins">
                    <div class="ibox-content">
<style>
    .col-sm-10{
        padding-top: 7px;
    }
</style>

<form method="post" class="form-horizontal" enctype="multipart/form-data"
      action="<?php echo U('Invoice/modify','doinfo=modify&id='.$data['id']);?>" onsubmit='return check()'>
    <div class="tab-content">
        <div class="form-group">
            <label class="col-sm-2 control-label">发票管理类型<span style="color:red;margin:5px;">*</span>：</label>
            <div class="col-sm-10">
                <?php switch($data["invoice_type"]): case "0": ?>电子普票<?php break;?>
                    <?php case "1": ?>纸质普票<?php break;?>
                    <?php case "2": ?>纸质专票<?php break; endswitch;?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">发票类型<span style="color:red;margin:5px;">*</span>：</label>
            <div class="col-sm-10">
                <?php switch($data["type"]): case "0": ?>企业<?php break;?>
                    <?php case "1": ?>个人或事业单位<?php break; endswitch;?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">发票金额<span style="color:red;margin:5px;">*</span>：</label>
            <div class="col-sm-10">
                <?php echo ($data['amount']); ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">发票抬头<span style="color:red;margin:5px;">*</span>：</label>
            <div class="col-sm-10">
                <?php echo ($data['head']); ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">税号<span style="color:red;margin:5px;">*</span>：</label>
            <div class="col-sm-10">
                <?php echo ($data['number']); ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">开户银行<span style="color:red;margin:5px;">*</span>：</label>
            <div class="col-sm-10">
                <?php echo ($data['bank_name']); ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">银行账号<span style="color:red;margin:5px;">*</span>：</label>
            <div class="col-sm-10">
                <?php echo ($data['bank_account']); ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">公司电话<span style="color:red;margin:5px;">*</span>：</label>
            <div class="col-sm-10">
                <?php echo ($data['company_phone']); ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">企业地址<span style="color:red;margin:5px;">*</span>：</label>
            <div class="col-sm-10">
                <?php echo ($data['company_addr']); ?>
            </div>
        </div>
        <div class="hr-line-dashed"></div>
        <div class="form-group">
            <label class="col-sm-2 control-label">申请的商品订单流水号<span style="color:red;margin:5px;">*</span>：</label>
            <div class="col-sm-10">
                <?php if(is_array($order['product_order'])): foreach($order['product_order'] as $key=>$v): ?><p><a href="<?php echo U('product_order/listad','type=0');?>?keyword=<?php echo ($v['sn']); ?>"><?php echo ($v['sn']); ?></a></p><?php endforeach; endif; ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">申请的服务订单流水号<span style="color:red;margin:5px;">*</span>：</label>
            <div class="col-sm-10">
                <?php if(is_array($order['service_order'])): foreach($order['service_order'] as $key=>$v): ?><p><a href="<?php echo U('service_order/listad','type=2');?>?keyword=<?php echo ($v['sn']); ?>"><?php echo ($v['sn']); ?></a></p><?php endforeach; endif; ?>
            </div>
        </div>
        <div class="hr-line-dashed"></div>
        <div class="form-group">
            <label class="col-sm-2 control-label">申请人电话<span style="color:red;margin:5px;">*</span>：</label>
            <div class="col-sm-10">
                <a href="<?php echo U('user/listad','role=1');?>?keyword=<?php echo ($data['mobile']); ?>"><?php echo ($data['mobile']); ?></a>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">收票人姓名<span style="color:red;margin:5px;">*</span>：</label>
            <div class="col-sm-10">
                <?php echo ($data['user_name']); ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">收票人电话<span style="color:red;margin:5px;">*</span>：</label>
            <div class="col-sm-10">
                <?php echo ($data['user_phone']); ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">收票人地址<span style="color:red;margin:5px;">*</span>：</label>
            <div class="col-sm-10">
                <?php echo ($data['user_addr']); ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">收票人邮箱<span style="color:red;margin:5px;">*</span>：</label>
            <div class="col-sm-10">
                <?php echo ($data['user_mail']); ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">申请时间<span style="color:red;margin:5px;">*</span>：</label>
            <div class="col-sm-10">
                <?php echo (date("Y-m-d H-i-s",date($data["add_time"]))); ?>
            </div>
        </div>
        <div class="hr-line-dashed"></div>
        <div class="form-group">
            <label class="col-sm-2 control-label">审核：</label>
            <div class="col-sm-10 radio i-checks">
                <label><input type="radio" name="status" value="0"
                              <?php echo (getSelect(radio,$data["status"],0)); ?>><i></i>未审核</label>
                <label><input type="radio" name="status" value="1"
                              <?php echo (getSelect(radio,$data["status"],1)); ?>><i></i>审核成功</label>
                <label><input type="radio" name="status" value="2"
                              <?php echo (getSelect(radio,$data["status"],2)); ?>><i></i>审核失败</label>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">发票图片：</label>
            <div class="col-sm-10">
                <input name="invoice_url" id="invoice_url" type="text" class="form-control" value="<?php echo ($data["invoice_url"]); ?>"
                       /><br>
                <div>
                    <a onclick="BrowseServer('Images:/', 'invoice_url');" href="javascript:void(0);" class="btn btn-white"
                       data-options="iconCls:'icon-redo'" style="width: 60px">选择</a>
                    <a id="invoice_url_img" title="点击预览大图,点击大图关闭预览" href="<?php echo ($data["invoice_url"]); ?>" target="_blank">
                        <?php if($data["invoice_url"] != ''): ?><img src="<?php echo ($data["invoice_url"]); ?>" style="max-height:100px;max-width: 100%"/><?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label">审核时间：</label>
            <div class="col-sm-10">
                <?php echo (date("Y-m-d H-i-s",date($data["status_time"]))); ?>
            </div>
        </div>
    </div>
    <div class="hr-line-dashed"></div>
    <div class="form-group">
        <div class="col-sm-4 col-sm-offset-2">
            <button class="btn btn-primary" type="submit">保存内容</button>
            <button class="btn btn-white" type="button"
                    onclick="location.href='<?php echo U('Invoice/lists');?>'">返回</button>
        </div>
    </div>
</form>
</div>
</div>
</div>
</div>
</div>
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

<script src="/Public/Admin/js/global.js"></script>

<script src="/Public/ckfinder/ckfinder.js"></script>
<script src="/Public/ckfinder/handle.js"></script>
<script src="/Public/Admin/plugins/layui/layui.js"></script>


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
</script>

</body>


<!-- Mirrored from www.zi-han.net/theme/hplus/ by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 20 Jan 2016 14:17:11 GMT -->

</html>