<!-- Akari 性能展现 -->
<style>
#benchmarkResult {
    position: fixed;
    height: 120px;
    overflow: auto;
    background: #fff;
    border-top: 1px #ccc solid;
    bottom: 0;
    left: 0;
    width: 100%;
    padding-top: 8px;
    padding-left: 20px;
    font-size: 12px;
}

#benchmarkResult h3{
    font-size: 16px;
    margin: 6px 0;
}

#benchmarkResult h4{
    font-size: 12px;
    margin: 3px 0;
}

#benchmarkResult ul {
    list-style: none;
    margin: 0;
    margin-left: 3px;
    padding: 0;
}

#benchmarkResult li b{
    display: inline-block;
    width: 120px;
    color: #666;
}

#benchmarkResult li b.sql_block{
    color: #666;
    display: inline;
    margin-left: 5px;
    width: 80%;
}

#benchmarkResult li {
    margin-bottom: 2px;
}

#benchmarkResult.close {
    height: 5px;
}

#benchmarkResult.close .bClose {
    bottom: 14px;
}

    #benchmarkResult .bClose {
        position: fixed;
        bottom: 130px;
        right: 20px;
    }
</style>



<div id="benchmarkResult">
    <a class="bClose" href="javascript:;" onclick="closeBenchmark()">&times;</a>
    <h3>Application</h3>
    <ul>
        <li>
            <b>CoreVer</b>
            <?=\Akari\akari::getVersion()?>
        </li>
        <li>
            <b>EntryPath</b>
            <?=\Akari\Context::$appEntryPath?>
        </li>

        <li>
            <b>EntryName</b>
            <?=\Akari\Context::$appEntryName?>
        </li>

        <li>
            <b>AppMode</b>
            <?=\Akari\Context::$mode ? \Akari\Context::$mode : '(Non-mode)'?>
        </li>

        <li>
            <b>Memory</b>
            <?=round(memory_get_usage()/1024, 2)?> KB
        </li>
    </ul>

    <h3>Count</h3>
    <ul>
    <?php foreach(\Akari\utility\Benchmark::$counter as $key => $value): ?>
        <li><b><?=$key?></b> <?=$value?></li>
    <?php endforeach; ?>
    </ul>

    <?php if (isset(\Akari\utility\Benchmark::$params['DB.QUERY'])): ?>
    <h3>Database</h3>
    <ul>
        <?php foreach(\Akari\utility\Benchmark::$params['DB.QUERY'] as $value): ?>
            <li>
                <b><?=round(($value['time']) * 1000, 2)?> ms</b> <?=$value['sql']?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <h3>Parameter</h3>
    <?php foreach(\Akari\utility\Benchmark::$params as $key => $value): ?>
        <?php if($key == 'DB.QUERY') continue; ?>
        <h4><?=$key?></h4>
        <ul>
            <?php foreach ($value as $val): ?>
                <?php foreach ($val as $k => $v): ?>
                <li><b><?=$k?></b> <?=$v?></li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>

    <h3>Session</h3>
    <ul>
    <?php foreach($_COOKIE as $k => $v): ?>
        <li><b>[C]<?=$k?></b> <?=$v?></li>
    <?php endforeach; ?>

    <?php if(isset($_SESSION)): ?>    
    <?php foreach($_SESSION as $k => $v): ?>
        <li><b>[S]<?=$k?></b> <?=$v?></li>
    <?php endforeach; ?>
    </ul>
    <?php endif ?>

    <h3>Class <small>(<?=count(\Akari\Context::$classes)?>)</small></h3>
    <ul>
        <?php foreach(\Akari\Context::$classes as $className => $_): ?>
        <li><?=str_replace(dirname(AKARI_PATH), '', $className)?></li>
        <?php endforeach; ?>
    </ul>

</div>

<script>
    var Bh_isClose = false;
    function closeBenchmark() {
        Bh_isClose = !Bh_isClose;
        document.getElementById("benchmarkResult").className = Bh_isClose ? 'close' : 'open';
    }
</script>
<!-- 结束，如果需要关闭请修改DISPLAY_BENCHMARK为FALSE -->