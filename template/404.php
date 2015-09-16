<html>
<head>
    <meta charset="utf-8">
    <title>404 - 没能找到你要的</title>

    <style>
        #wrapper{width: 450px;margin: 60px auto;color: #714840;}
        #wrapper h1{font-size: 37px;}
        #wrapper p{margin: 5px 0;}
        #wrapper p a{color: #714840; text-decoration: underline}

        #footer{color: #aaa;font-size: 10px;margin-top: 10px;padding: 8px 0;border-top: 1px #EEE solid;}
        #footer a{color: #aaa; margin: 4px 5px; opacity: .7}
        #footer a:hover{opacity: 1;}
        #footer img{margin-bottom: -3px;}

        .debug {
            font-size: 9px;
        }
    </style>
</head>

<body>


<div id="wrapper">
    <h1>没找到页面</h1>
    <p>看看是不是地址写错了？ 或者<a href="<?=$index?>">返回首页</a></p>

    <small>404: Not found</small>
    <div class="debug"><?=$msg?></div>

    <div id="footer">
        Akari Framework <?=\Akari\akari::getVersion()?>
    </div>
</div>

</body></html>