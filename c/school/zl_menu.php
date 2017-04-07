<?php
require_once '../config.php';
require_once '../lib/fun.php';
require_once "../lib/jssdk.php";
check_login();

$sql = "select * from sc_user where openid = '" . $_SESSION['user']->openid . "'";
$res = $db->query($sql);
$res->setFetchMode(PDO::FETCH_OBJ);
$rs = $res->fetch();
if (isset($rs)) {
    $_SESSION["user"] = $rs;
}

?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title></title>
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
    <link rel="stylesheet" href="../public/style/weui.css"/>
    <link rel="stylesheet" href="../public/style/weui2.css"/>
    <link rel="stylesheet" href="../public/style/weui3.css"/>
    <script src="../public/zepto.min.js"></script>
    <script>
        $(function () {
            var $form = $("#form");
            $form.form();
            $("#btn").click(function (e) {
                $form.validate(function (error) {
                    if (error) {
                    } else {
                        $.showLoading("更新中");
                        $.ajax({
                            type: 'POST',
                            url: '../api/school.php?a=update_user_info',
                            dataType: 'json',
                            data: $form.serialize(),
                            success: function (data) {
                                $.hideLoading();
                                if (data.msg == "success") {
                                    $.alert("更新成功,稍后将重新登录", "系统消息", function () {
                                        location.href = "index.php?state=<?=$_SESSION['state']?>";
                                    });
                                } else {
                                    alert(data.msg);
                                }


                            },
                            error: function (xhr, type) {
                                $.hideLoading();
                                console.log('Ajax error!');
                            }
                        });
                    }
                });
            })
        });
    </script>
    <style>
        .weui_cell_hd .icon {
            font-size: 24px;
            line-height: 40px;
            margin: 4px;
            color: #18b4ed;
            -webkit-transition: font-size 0.25s ease-out 0s;
            -moz-transition: font-size 0.25s ease-out 0s;
            transition: font-size 0.25s ease-out 0s;
        }
    </style>
</head>

<body ontouchstart style="background-color: #f8f8f8;">

<div class="weui_cells_title">个人</div>
<div class="weui_cells weui_cells_access">
    <a class="weui_cell " href="zl_my_info.php">
        <div class="weui_cell_bd weui_cell_primary">
            <p>个人信息</p>
        </div>
        <div class="weui_cell_ft"></div>
    </a>
</div>

</body>

</html>