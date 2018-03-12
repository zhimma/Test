<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:61:"D:\www\Test\public/../application/index\view\index\index.html";i:1520824556;}*/ ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- 上述3个meta标签*必须*放在最前面，任何其他内容都*必须*跟随其后！ -->
    <title>UserData</title>

    <!-- 最新版本的 Bootstrap 核心 CSS 文件 -->
    <link rel="stylesheet" href="https://cdn.bootcss.com/bootstrap/3.3.7/css/bootstrap.min.css"
          integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://cdn.bootcss.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://cdn.bootcss.com/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
<div class="container">
    <div class="row" style="margin-top: 60px">
        <div class="col-md-6 center-block">
            <button class="btn btn-default" id="picker" data-url="<?php echo url('index/index/upload'); ?>">选择文件</button>
            <button class="btn btn-primary" id="start-upload">上传文件</button>
        </div>
    </div>
</div>

<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="https://cdn.bootcss.com/jquery/1.12.4/jquery.min.js"></script>
<script src="/static/plupload-2.3.6/js/plupload.full.min.js"></script>

<!-- 最新的 Bootstrap 核心 JavaScript 文件 -->
<script src="https://cdn.bootcss.com/bootstrap/3.3.7/js/bootstrap.min.js"
        integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa"
        crossorigin="anonymous"></script>
<script>
    var static = '/static';
    var uploader = new plupload.Uploader({
        runtimes: 'html5,flash,silverlight,html4',
        browse_button: 'picker', // you can pass an id...
        url: $("#picker").data('url'),
        file_data_name: 'file',
        flash_swf_url: static + '/js/Moxie.swf',
        silverlight_xap_url: static + '/js/Moxie.xap',
        filters: {
            max_file_size: '120mb'
            /*mime_types: [
                {title : "Image files", extensions : "jpg,gif,png"},
                {title : "Zip files", extensions : "zip"}
            ]*/
        },

        init: {
            PostInit: function () {
                $('#start-upload').on('click', function () {
                    uploader.start();
                    return false;
                })
            },
            FilesAdded: function (up, files) {
                console.log('文件已添加');
            },

            UploadProgress: function (up, file) {
                console.log('上传中');
            },
            FileUploaded: function (uploader, file, responseObject) {
                var response = JSON.parse(responseObject.response);
                console.log(response);
                if (response.status == 1) {
                    alert(response.msg);
                } else {
                    alert(response.msg);
                }
            },
            UploadComplete: function (up, file) {
                console.log('上传完成');
            },
            Error: function (up, err) {
                console.log(err.msg);
            }
        }
    });

    uploader.init();
</script>
</body>

</html>