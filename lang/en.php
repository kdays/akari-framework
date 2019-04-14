<?php

return [

    'upload_err.' . UPLOAD_ERR_INI_SIZE => "File size exceeds system configuration",
    'upload_err.' . UPLOAD_ERR_FORM_SIZE => 'File size exceeds form configuration',
    'upload_err.' . UPLOAD_ERR_PARTIAL => 'File upload incomplete',
    'upload_err.' . UPLOAD_ERR_NO_FILE => 'No files uploaded',
    'upload_err.' . UPLOAD_ERR_NO_TMP_DIR => 'System error, Temporary files do not exist',
    'upload_err.' . UPLOAD_ERR_CANT_WRITE => 'System Error, File Writing Failed',

    'csrf_verify_error' => <<<'EOT'
Form validation failed. 
Please go back to the previous page to refresh and retry. 
EOT
    ,

    'df.now' => 'now',
    'df.d' => '%d% days ago',
    'df.h' => '%d% hours ago',
    'df.m' => '%d% months ago',
    'df.s' => '%d% secs ago',
    'df.i' => '%d% minutes ago'

];
