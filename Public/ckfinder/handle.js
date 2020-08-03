

function MakeMultiImagesHtml($id, $data) {
    if ($data) {
        var $ul = $('#' + $id + '_MulitiImageSelect');
        var mulitiImage = "";
        var mulitiImages = $data.replace(new RegExp(/(\r\n)/g), '|').split('|');
        for (var i in mulitiImages) {
            var s = mulitiImages[i];
            if (s != '') {
                mulitiImage += "<li id='" + $id + "_" + i + "'><a href=\"" + s + "\" target=\"_blank\"><img src=\"" + s + "\" width=\"80\" height=\"80\"><br>预览</a>";
                mulitiImage += "<a class=\"del-btn fa fa-remove\" data-id=\"" + i + "\" href=\"javascript:MulitiDelFileField(" + i + ", '" + s + "', '" + $id + "');\"></a></li>";
            }
        }
        $ul.append(mulitiImage);
    }
};

function BrowseServer(startupPath, clientId) {
    var inputValue = $("#" + clientId).val();
    if (inputValue) {
        var arr = inputValue.split("/");
        delete arr[arr.length - 1];
        var dir = arr.join("/");
        startupPath = decodeURI(dir); //decodeURIComponent(dir);
    }
    startupPath = startupPath.replace("/upload/userfiles/files/", "Files:/");
    startupPath = startupPath.replace("/upload/userfiles/images/", "Images:/");

    CKFinder.popup({
        chooseFiles: true,
        rememberLastFolder: true,
        startupPath: startupPath,
        onInit: function (finder) {
            finder.on("files:choose", function (evt) {
                var file = evt.data.files.first();
                var fileUrl = file.getUrl();
                SetFileField(fileUrl, clientId);
            });
        }
    });
}

// This is a sample function which is called when a file is selected in CKFinder.
function SetFileField(fileUrl, clientId) {
    try {
        var imgHtml = "<img src='" + fileUrl + "' style='height:50px;width:auto;' />";
        $("#" + clientId + "_Img").attr("href", fileUrl).html(imgHtml);
        $('#' + clientId).val(fileUrl);
    } catch (e) {

    }
}

function MulitiBrowseServer(startupPath, clientId) {
    var inputValue = $("#" + clientId).val();
    if (inputValue) {
        var lenArr = inputValue.split("\n");
        if (lenArr.length > 2) {
            var arr = lenArr[lenArr.length - 2].split("/");
            delete arr[arr.length - 1];
            var dir = arr.join("/");
            startupPath = decodeURI(dir);
        } else {
            var arr = lenArr[lenArr.length - 1].split("/");
            delete arr[arr.length - 0];
            var dir = arr.join("/");
            startupPath = decodeURI(dir);
        }
    }
    startupPath = startupPath.replace("/upload/userfiles/files/", "Files:/");
    startupPath = startupPath.replace("/upload/userfiles/images/", "Images:/");

    CKFinder.popup({
        chooseFiles: true,
        rememberLastFolder: true,
        startupPath: startupPath,
        onInit: function (finder) {
            finder.on("files:choose", function (evt) {
                var files = [];
                var models = evt.data.files.models;
                for (var k in models) {
                    var file = models[k];
                    var url = file.getUrl();
                    files.push(url);
                }
                MulitiSetFileField(files, clientId);
            });
        }
    });
}

function MulitiSetFileField(files, clientId) {
    try {
        var fileUrlObj = $('#' + clientId);
        var fileUrl = "";
        var container = $('#' + clientId + '_MulitiImageSelect');
        var fileHtml = container.html();
        var liCount = container.find('li').last().data("id");
        if (!(liCount > 0)) liCount = -1;
        for (var k in files) {
            var url = files[k];
            liCount++;
            fileUrl += url + "\r\n";
            fileHtml += "<li id='" + clientId + '_' + liCount + "' data-id='" + liCount + "'><a href='" + url + "' target='_blank'><img src='" + url + "' width='80' height='80' />" + "<br />预览</a>";
            fileHtml += '<a class="del-btn fa fa-remove" data-id="' + liCount + '" href="javascript:MulitiDelFileField(' + liCount + ', \'' + url + '\', \'' + clientId + '\');"></a></li>';
        }
        fileUrlObj.val(fileUrlObj.val() + fileUrl);
        container.html(fileHtml);
    } catch (e) {

    }
}

function MulitiDelFileField(id, fileUrl, clientId) {
    var obj = $('#' + clientId + '_' + id);
    var index = $('#' + clientId + '_MulitiImageSelect li').index(obj);
    obj.remove();
    console.log(obj);
    var fileUrlObj = $('#' + clientId);
    var fileUrl = fileUrlObj.val();

    var newFileUrl = "";
    var fileArr = fileUrl.split("\n");
    for (var i = 0; i < fileArr.length; i++) {
        if (fileArr[i] == "") continue;

        if (i != index) {
            newFileUrl += fileArr[i] + "\n";
        }
    }
    fileUrlObj.val(newFileUrl);
}

function InitMulitiImages(clientId) {
    var fileUrl = $('#' + clientId).val();
    if (!fileUrl) {
        return;
    }
    var fileHtml = "";
    var liCount = -1;
	fileUrl=fileUrl.replace(/\,/g,"\n")
	fileUrl=fileUrl.replace(',',"\n")
    var files = fileUrl.split("\n");
    for (var k in files) {
        var url = files[k];
        if (url) {
            liCount++;
            fileHtml += "<li id='" + clientId + '_' + liCount + "' data-id='" + liCount + "'><a href='" + url + "' target='_blank'><img src='" + url + "' width='80' height='80' />" + "<br />预览</a>";
            fileHtml += '<a class="del-btn fa fa-remove" data-id="' + liCount + '" href="javascript:MulitiDelFileField(' + liCount + ', \'' + url + '\', \'' + clientId + '\');"></a></li>';
        }
    }
    $('#' + clientId + '_MulitiImageSelect').html(fileHtml);
}