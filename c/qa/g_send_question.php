<?php
require_once '../config.php';
require_once '../lib/fun.php';

$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

$state = $_GET['state'];
//检查用户是否已经登录
if (!isset($_SESSION['user']->openid)) {
    //未登录 微信登录
    $_SESSION['user'] = wx_userinfo($appid, $secret, $redirect_uri, $state);
}

//检查用户并更新用户SESSION信息
$_SESSION['user'] = check_user($_SESSION['user']);


$sql = "select * from sc_user where id = " . $_REQUEST['id'] . " limit 1";
$res = $db->query($sql);
$user = $res->fetch();

//var_dump($_SESSION['user']);
$sql = "select * from sc_user where id = " . $_REQUEST['id'] . " limit 1";
$res = $db->query($sql);
$user = $res->fetch();


$sql = "SELECT sum(money) as c FROM `sc_pay_log` WHERE `get_user_id` = " . $_REQUEST['id'];
$res = $db->query($sql);
$re = $res->fetch();
$money_count = $re['c']/100;

$sql = "SELECT count(*) as c FROM `sc_question` WHERE answer_content is not null and `answer_user_id` = " . $_REQUEST['id'];
$res = $db->query($sql);
$re = $res->fetch();
$answer_count = $re['c'];


$sql = "SELECT sum(play_num) as c FROM `sc_question` WHERE answer_content is not null and `answer_user_id` = " . $_REQUEST['id'];
$res = $db->query($sql);
$re = $res->fetch();
$play_count = $re['c'] ? $re['c'] : 0;
//    var_dump($play_count);

$sql = "SELECT sum(up_num) as c FROM `sc_question` WHERE answer_content is not null and `answer_user_id` = " . $_REQUEST['id'];
$res = $db->query($sql);
$re = $res->fetch();
$up_num_count = $re['c'] ? $re['c'] : 0;

require_once "../lib/WxPay.Api.php";
require_once "WxPay.JsApiPay.php";

//require_once 'log.php';
//初始化日志
//$logHandler= new CLogFileHandler("../logs/".date('Y-m-d').'.log');
//$log = Log::Init($logHandler, 15);
//打印输出数组信息

function printf_info($data)
{
    foreach ($data as $key => $value) {
        echo "<font color='#00ff55;'>$key</font> : $value <br/>";
    }
}

//①、获取用户openid
$tools = new JsApiPay();
//$openId = $tools->GetOpenid();
$openId = $_SESSION['user']->openid;
//②、统一下单
$input = new WxPayUnifiedOrder();
$input->SetBody("提问费用");
$input->SetAttach("attach");
$input->SetOut_trade_no(WxPayConfig::MCHID . date("YmdHis"));
$input->SetTotal_fee($price_money);
$input->SetTime_start(date("YmdHis"));
$input->SetTime_expire(date("YmdHis", time() + 600));
$input->SetGoods_tag("goods_tag");
$input->SetNotify_url("http://qjs.isqgame.com/WxpayAPI_php_v3/example/notify.php");
$input->SetTrade_type("JSAPI");
$input->SetOpenid($openId);
$order = WxPayApi::unifiedOrder($input);
//echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
//printf_info($order);
$jsApiParameters = $tools->GetJsApiParameters($order);

//获取共享收货地址js函数参数
//$editAddress = $tools->GetEditAddressParameters();
//var_dump($user);
?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>提问</title>
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
    <link rel="stylesheet" href="../public/style/weui.css"/>
    <link rel="stylesheet" href="../public/style/weui2.css"/>
    <link rel="stylesheet" href="../public/style/weui3.css?1"/>
    <script src="../public/zepto.min.js"></script>
    <script>

        $(function () {
            $.showLoading();

            setTimeout(function () {
                $.hideLoading();
            }, 300)

            var max = $('#count_max').text();
            $('#content').on('input', function () {
                var text = $(this).val();
                var len = text.length;
                $('#count').text(len);
                if (len > max) {
                    $(this).closest('.weui_cell').addClass('weui_cell_warn');
                    $(this).val($(this).val().substring(0, max));
                    $(this).focus();
                    $('#count').text(len - 1);
                } else {
                    $(this).closest('.weui_cell').removeClass('weui_cell_warn');
                }
            });

            $("#formSubmitBtn").on("click", function () {
                if ($("#content").val() == "") {
                    $.toast("内容不能为空", "forbidden");
                } else {
//                    var d = $('#sendMsg').serializeArray();
//
//                    $.ajax({
//                        type: 'POST',
//                        data: d,
//                        url: '../api/qa.php?a=send_question',
//                        dataType: 'json',
//                        success: function (data) {
//                            console.log(data);
//                            $.toast("提问成功");
////                            location.href = "g_my_question.php";
////                                window.history.back(-1);
//                        }
//                    });

                    callpay();
                }
            });
        })


        //调用微信JS api 支付
        function jsApiCall() {
            WeixinJSBridge.invoke(
                'getBrandWCPayRequest',<?php echo $jsApiParameters; ?>,
                function (res) {
                    WeixinJSBridge.log(res.err_msg);
                    //				alert(res.err_code+res.err_desc+res.err_msg);

                    if (res.err_msg == "get_brand_wcpay_request:ok") {

                        var d = $('#sendMsg').serializeArray();

                        $.ajax({
                            type: 'POST',
                            data: d,
                            url: '../api/qa.php?a=send_question',
                            dataType: 'json',
                            success: function (data) {
                                console.log(data);
                                $.toast("提问成功");
//                                location.href = "g_my_question.php";
                                window.history.back(-1);
                            }
                        });

                    } else if (res.err_msg == "get_brand_wcpay_request:cancel") {
                        alert("支付取消，请重新支付");
                    } else if (res.err_msg == "get_brand_wcpay_request:fail") {
                        alert("支付失败，请重新支付");
                    }
                }
            );
        }

        function callpay() {
            if (typeof WeixinJSBridge == "undefined") {
                if (document.addEventListener) {
                    document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
                } else if (document.attachEvent) {
                    document.attachEvent('WeixinJSBridgeReady', jsApiCall);
                    document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
                }
            } else {
                jsApiCall();
            }
        }
    </script>

    <style>
        .weui_grids:before{
            border: none!important;
        }
        .grid:after {
            border-bottom: none!important;
        }
        .grids-small .grid{
            padding: 0px!important;
            margin-bottom: 10px!important;
        }
    </style>
</head>

<body ontouchstart style="background-color: #f8f8f8;">


<div style="text-align: center; padding: 10px;">
    <img class="" src="<?= $user['headimgurl'] ?>" style="width: 60px; padding: 6px; border-radius: 80px;">
    <div><span style="font-size: 14px;"><?= $user['username'] ?></span><br><span class="weui-label-s"
                                                                             style="font-size: 8px; margin-left: 8px;"><?= $user['small_memo'] ? $user['small_memo'] : "特约专家" ?></span>
    </div>
    <p class="weui_media_desc" style="padding-top: 4px;font-size:12px;overflow: hidden;
    text-overflow: ellipsis;display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 4;"><?= $user['memo'] ? $user['memo'] : "还没有填写备注" ?></p>
</div>

<div class="weui_grids grids-small" >
    <a href="javascript:;" class="grid">
        <div class="weui_grid_icon2">
            ¥ <?= $money_count  ?>
        </div>
        <p class="weui_grid_label">
            收入
        </p>
    </a>
    <a href="javascript:;" class="grid">
        <div class="weui_grid_icon2">
            <?= $answer_count ?>
        </div>
        <p class="weui_grid_label">
            回答
        </p>
    </a>
    <a href="javascript:;" class="grid">
        <div class="weui_grid_icon2">
            <?= $play_count ?>
        </div>
        <p class="weui_grid_label">
            听取
        </p>
    </a>
    <a href="javascript:;" class="grid">
        <div class="weui_grid_icon2">
            <?= $up_num_count ?>
        </div>
        <p class="weui_grid_label">
            点赞
        </p>
    </a>
</div>
<div class="weui_cells " style="margin-top: 2px !important; ">
    <form id="sendMsg">
        <input type="hidden" name="question_user_id" value="<?= $_SESSION['user']->id ?>">
        <input type="hidden" name="answer_user_id" value="<?= $user['id'] ?>">
        <div class="weui_cell">
            <div class="weui_cell_bd weui_cell_primary">
                <textarea id="content" name="content" class="weui_textarea" placeholder="提问，是一种认可..." rows="3"></textarea>
                <div class="weui_textarea_counter"><span id='count'>0</span>/<span id='count_max'>200</span></div>
            </div>
        </div>
    </form>
</div>
<div class="weui_btn_area">
    <a class="weui_btn weui_btn_primary" href="javascript:;" id="formSubmitBtn">付费提问 <?= $price_money/100?>元</a>

</div>
</body>
</html>
