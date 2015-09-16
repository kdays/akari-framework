<html>
<head>
    <meta charset="utf-8">
    <title>500 - 内部服务器错误</title>

    <style>
        #wrapper{width: 450px;margin: 60px auto;color: #714840;}
        #wrapper h1{font-size: 37px;}
        #wrapper p{margin: 5px 0;}

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
    <h1>程序掉进坑里了!</h1>
    <p>可能是程序员偷懒忘记把坑填上；或者是服务器娘迷路了。</p>
    <p>你可以稍等片刻，然后刷新再试试。</p>

    <small>500: Internal server error</small>
    <div class="debug"><?=$message?> on <?=$file?></div>

    <div id="footer">
        Akari Framework <?=\Akari\akari::getVersion()?>
    </div>
</div>

</body>
</html>