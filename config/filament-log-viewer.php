<?php

return [
    'max_log_file_size' => env('LOG_VIEWER_MAX_SIZE_KB', 2048),
    'enable_delete' => env('LOG_VIEWER_ENABLE_DELETE', false),
];
