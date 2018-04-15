<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title><?php echo isset($title) ? $title : 'redirect'; ?></title>
    <style type="text/css">
        * {
            padding: 0;
            margin: 0;
        }

        body {
            background: #fff;
            font-family: "Microsoft Yahei", "Helvetica Neue", Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 16px;
        }

        .sysmsg {
            padding: 24px 48px;
        }

        .sysmsg h1 {
            font-size: 100px;
            font-weight: normal;
            line-height: 120px;
            margin-bottom: 12px;
        }

        .sysmsg .jump {
            padding-top: 10px;
        }

        .sysmsg .jump a {
            color: #333;
        }

        .sysmsg .success, .sysmsg .error {
            line-height: 1.8em;
            font-size: 36px;
        }

        .sysmsg .detail {
            font-size: 12px;
            line-height: 20px;
            margin-top: 12px;
            display: none;
        }
    </style>
</head>
<body>
<div class="sysmsg">
    <?php
    if (isset($status)) {
        $message = isset($message) ? strip_tags($message) : '[...]';
        switch (intval($status)) {
            case 1:
                echo "<h1>:)</h1><p class=\"success\">{$message}</p>";
                break;
            case 0:
                echo "<h1>:(</h1><p class=\"error\">{$message}</p>";
                break;
            default:
                echo "<h1>:|</h1><p class=\"success\">{$message}</p>";
        }
    }
    ?>
    <p class="detail"></p>
    <p class="jump">
        页面自动 <a id="href" href="<?php if (empty($url)) {
            die('[未找到跳转地址]');
        } else {
            echo $url;
        }; ?>">跳转</a>
        等待时间： <b id="wait"><?php echo(isset($wait) ? intval($wait) : 0); ?></b>
    </p>
</div>
<script type="text/javascript">
    (function () {
        /* 倒计时 */
        var wait = document.getElementById('wait'),
            href = document.getElementById('href').href;
        var interval = setInterval(function () {
            var time = --wait.innerHTML;
            if (time <= 0) {
                location.href = href;
                clearInterval(interval);
            }
        }, 1000);
    })();
</script>
</body>
</html>