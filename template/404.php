
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>404 Not Found 找不到呀就是找不到</title>

    <link rel="stylesheet" href="http://kdays.net/images/error/404.css" />
</head>

<body>

<div id="wrap">
    <div id="inner">
        <h1>404 页面存在感丢失</h1>
        <p>没有找到要访问的文件，可以选择：
        <ul>
            <li><a href="<?=$index?>">应用首页</a></li>
            <li><a href="http://kdays.net/">返回KDays</a></li>
            <div style="clear: both;"></div>
        </ul>
        </p>
    </div>

    <div id="footer">
        <p>KDays Team / <span style="color: #888">Akari Framework <?=\Akari\akari::getVersion()?></span></p>
    </div>

</div>
<!-- <?=$msg?> -->

</body></html>