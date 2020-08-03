$(function() {
  $(".verifyimg").click(function() {
    var verifyimg = $(".verifyimg").attr("src");
    if (verifyimg.indexOf("?") > 0) {
      $(".verifyimg").attr("src", verifyimg + "&random=" + Math.random());
    } else {
      $(".verifyimg").attr(
        "src",
        verifyimg.replace(/\?.*$/, "") + "?" + Math.random()
      );
    }
  });
  $(".checkDel").click(function() {
    $("#modal-demo .modal-title").html("删除");
    $("#modal-demo .modal-body p").html("确认要删除么？");
    url = $(this).attr("data-url");
    console.log(url);
    $("#modal-demo").modal("show");
    $("#modal-demo .btn-primary").click(function() {
		alert('删除成功')
      location.href = url;
    });
  });
});

function modalalertdemo() {
  $.Huimodalalert("我是消息框，2秒后我自动滚蛋！", 2000);
}

function modaldemo() {
  $("#modal-demo").modal("show");
}

function exSubinfo(str) {
  dis = $(str).css("display");
  if (dis == "none") {
    $(str).fadeIn();
  } else {
    $(str).fadeOut();
  }
}

function preview(file, preview, isfile = 0) {
  var prevDiv = $(preview);
  if (file.files && file.files[0]) {
    var reader = new FileReader();
    reader.onload = function(evt) {
      if (isfile == 0) {
        prevDiv.html(
          '<img src="' +
            evt.target.result +
            '"  style="max-height:100px;max-width:100%;" />'
        );
      }
    };
    reader.readAsDataURL(file.files[0]);
  } else {
    if (isfile == 0) {
      //IE
      prevDiv.html(
        '<div class="img" style="filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale,src=\'' +
          file.value +
          "'  style=max-height:100px;max-width:100%;\"></div>"
      );
    }
  }
  $(file)
    .parent()
    .find(".delete")
    .show();
}

$.fn.serializeObject = function() {
  var o = {};
  var a = this.serializeArray();
  $.each(a, function() {
    if (o[this.name] !== undefined) {
      if (!o[this.name].push) {
        o[this.name] = [o[this.name]];
      }
      o[this.name].push(this.value || "");
    } else {
      o[this.name] = this.value || "";
    }
  });
  return o;
};
