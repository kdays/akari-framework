<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8" />
    <title>Akari Framework Error</title>


    <style>
        body, ul, h1, h2{ margin: 0; padding: 0;}
        body{
            background: #fff; color: #333; font-size: 12px;
        }
        #wrapper{margin: 0 auto;}
        #inner{margin: 5px 0; padding: 5px 20px;}

        ul{list-style: none; color: fff;}
        li{padding: 5px 7px; }
        li:hover .func{background: #333; color: #fff;}

        .func{display: block; margin: 8px 0; color: #666; padding: 3px 20px;}
        #trace{padding: 4px 10px; margin: 20px 0;}

        h1{font-size: 22px; color: #fff; background: darkred; padding: 10px 20px;}
        h1 span{font-size: 12px; color: pink;}
        h2{font-size: 14px;margin: 6px 0;}
        h2 span{font-size: 12px; color: #666;}
        .gray{color: #888;}
        #version{text-align: center; margin: 10px auto; width: 400px;}
        #version span.ver{color: #69c; margin-left: 3px;}
        #version #site{
            display: block; margin-top: 6px;
            color: #DDD; padding: 2px 5px;
        }
        #version #site a{color: #DDD; text-decoration: none; margin-left: 2px;}

        /* 文件详细提示 */
        #where{border: 1px #ccc solid;}
        #where ul{padding: 3px 10px;}
        #where b{color: #999; padding: 5px 0; margin-right: 10px;}

        #where li{border-bottom: 1px #eee solid;display: block;}
        #where li.current{background: lightyellow;}
        #where li:last-child{border-bottom: none;}
        #where li:hover{color: #fff;background: #888;}
        #where li:hover b{color: #fff;}

        .w-block{width: 12px;display: inline-block;}
    </style>
</head>

<body>
    <div id="wrapper">
        <h1>程序错误 <span>System Error</span></h1>
        <div id="inner">
            <p><?=$message?> <span class="gray">on <?=$file?> (<?=$line?>)</span></p>
            <p class="gray">如果您不了解这个消息，可以将其消息发给网站管理员处理</p>
        </div>

        <?php if (is_array($fileLine)): ?>
        <div id="where">
            <ul>
            <?php foreach ($fileLine as $key => $value): ?>
                <?php if ($key == $line - 1): ?>
                    <li class="current"><?=$value?></li>
                <?php else: ?>
                    <li><?=$value?></li>
                <?php endif; ?>
            <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div id="trace">
            <h2>调试日志 <span>Trace</span></h2>
            <?php if (empty($trace)): ?>
            <p>这是一个致命错误，无法生成跟踪日志</p>
            <?php else: ?>
            <ul id="trace_log">
                <?php foreach ($trace as $line): ?>
                    <li><?=$line?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

        <div id="version">
            Akari Framework <span class="ver"><?=$version?></span>
            <div id="site">
                <b>Code By</b> <a href="http://kdays.cn/">KDays Team</a> (Build: <?=$build?>)
            </div>
        </div>
    </div>
</body>

</html>