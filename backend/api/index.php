<?php

try {
    require __DIR__ . '/../public/index.php';
} catch (\Throwable $e) {
    error_log(sprintf(
        '[VERCEL_UNCAUGHT] %s: %s at %s:%d',
        get_class($e),
        str_replace(
            ["\r", "\n"],
            ' ',
            $e->getMessage()
        ),
        basename($e->getFile()),
        $e->getLine()
    ));

    http_response_code(500);
    echo 'Server Error';
}