<?php

return [

    'upload_err.' . UPLOAD_ERR_INI_SIZE => "文件大小超过系统配置",
    'upload_err.' . UPLOAD_ERR_FORM_SIZE => '文件大小超过表单配置',
    'upload_err.' . UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
    'upload_err.' . UPLOAD_ERR_NO_FILE => '没有文件被上传',
    'upload_err.' . UPLOAD_ERR_NO_TMP_DIR => '系统错误,没有找到临时文件夹',
    'upload_err.' . UPLOAD_ERR_CANT_WRITE => '系统错误,文件写入失败',

    'csrf_verify_error' => <<<'EOT'
表单验证失败，请返回上一页刷新重试。如果多次失败可以尝试更换游览器再行提交。 (CSRF Token Verify Failed)
EOT
    ,

    'df.now' => '刚刚',
    'df.d' => '%d%天前',
    'df.h' => '%d%小时前',
    'df.m' => '%d%月前',
    'df.s' => '%d%秒前',
    'df.i' => '%d%分钟前'

];
