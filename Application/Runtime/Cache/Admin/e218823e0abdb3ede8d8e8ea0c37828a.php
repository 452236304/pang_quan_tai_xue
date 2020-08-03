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

<body class="gray-bg">
    <form id="form1" name="form1" method="post" action="<?php echo U('Column/sortad','p='.$map['p'].'&type='.$map['type']);?>">
        <div class="wrapper wrapper-content animated fadeInRight">
            <div class="row">
                <div class="col-sm-12">
                    <div class="ibox float-e-margins">
                        <div class="ibox-content">
                            <button class="btn btn-primary" type="button" onclick="location.href='<?php echo U('Column/modifyad', 'p='.$map['p']);?>'">＋增加</button>
                            <button type="submit" class="btn btn-primary" />重新排序</button>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="ibox float-e-margins">
                        <div class="ibox-content">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th width="100" class="TC">编号</th>
										<th width="100" class="TC">审核</th>
                                        <th width="100" class="TC">排序</th>
                                        <th width="200" class="TC">栏目名称</th>
                                        <th width="200" class="TC">栏目图片</th>
                                        <th width="100" class="TC">发布日期</th>
                                        <th width="100" class="TC">操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(is_array($data)): $n = 0; $__LIST__ = $data;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($n % 2 );++$n;?><tr>
                                            <td align="center"><?php echo ($n); ?><input type="hidden" name="id[]" value="<?php echo ($vo["id"]); ?>" /></td>
											<td align="center">
											    <?php if($vo["status"] == 1): ?><i class="fa fa-check"></i>
											        <?php else: ?>
											        <i class="fa fa-close"></i><?php endif; ?>
											</td>
                                            <td align="center">
                                                <input type="text" style="text-align:center; width:50px;" class="form-control"
                                                    name="ordernum[]" value="<?php echo ($vo["ordernum"]); ?>" />
                                            </td>
                                            <td align="center"><?php echo ($vo["name"]); ?></td>
                                            <td align="center">
                                                <img src="<?php echo ($vo["thumb"]); ?>" width="100" height="100" alt="" class="img-rounded">
                                            </td>
                                            <td align="center"><?php echo (date("Y-m-d",strtotime($vo["createdate"]))); ?></td>
                                            <td class="text-navy" align="center">
												<a href="<?php echo U('Column/modifyad','id='.$vo['id'].'&p='.$map['p']);?>">修改</a>
                                                <a href="javascript:;" data-url="<?php echo U('Column/delad','id='.$vo['id'].'&p='.$map['p']);?>" class="checkDel">删除</a>
                                            </td>
                                        </tr><?php endforeach; endif; else: echo "" ;endif; ?>
                                </tbody>
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