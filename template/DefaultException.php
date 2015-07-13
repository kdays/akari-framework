<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8" />
    <title>Akari Framework Error</title>


    <style>
        body, ul, h1, h2{ margin: 0; padding: 0;}
        body{
            background: #FFF; color: #333; font-size: 12px;
        }
        #wrapper{margin: 0;}

        ul{list-style: none; }
        li{padding: 5px 7px; }
        li:hover .func{background: #333; color: #fff;}

        .func{display: block; margin: 8px 0; color: #666; padding: 3px 20px;}
        #trace{padding: 4px 10px; margin: 14px 0;}
        #message {padding: 10px 16px; }
        #errorMessage {font-size: 15px;  margin: 10px 0 5px;  line-height: 1.5; }
        #errorMessage #fileName {color: #999; font-size: 13px;}

        #header{font-size: 12px; color: #fff; background: darkred; padding: 12px 12px;}
        #header p{
            padding: 0; font-size: 18px;
            margin: 15px 0 0;
        }
        #header span{font-size: 12px; }
        h2{font-size: 14px;margin: 6px 0;}
        h2 span{font-size: 12px; color: #666;}
        .gray{color: #888;}

        #version{ padding-right: 20px; color: #888; margin-bottom: 15px; text-align: center; }
        #version span.ver{color: #69c;}
        #version a{ color: #888; text-decoration: none; margin-left: 2px;}

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
        <div id="header">
           <h1>发生一个错误 <span><?=$className?></span></h1>
        </div>

        <div id="message">
            <p id="errorMessage">
                <?=$message?>
                <span id="fileName"><?=$file?>:<?=$line?></span>
            </p>
            <p class="gray">如果你无法理解，请将这些信息发给网站相关的负责人。</p>
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
            <h2>跟踪堆栈</h2>
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
            / By <a href="http://kdays.net/">KDays Team</a>
        </div>
    </div>
</body>

</html>