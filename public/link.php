<?php

$target = __DIR__ . '/../storage/app/public';
$link   = __DIR__ . '/storage';

if (function_exists('symlink')) {
    if (@symlink($target, $link)) {
        echo 'SYMLINK BERHASIL';
    } else {
        echo 'SYMLINK GAGAL';
    }
} else {
    echo 'FUNGSI SYMLINK TIDAK TERSEDIA';
}