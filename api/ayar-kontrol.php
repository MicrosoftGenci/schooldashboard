<?php
header('Content-Type: application/json; charset=utf-8');
$c=require dirname(__DIR__).'/config.php';
$d=json_decode(@file_get_contents($c['data_file']),true);
echo json_encode(array(
 'ok'=>is_array($d),
 'okul_adi'=>isset($d['Ayarlar']['Okul Adı'])?$d['Ayarlar']['Okul Adı']:'',
 'data_file'=>$c['data_file'],
 'writable'=>is_writable($c['data_file'])
),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
