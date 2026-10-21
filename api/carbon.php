<?php
/* V56: Ankara hava kalitesi - Open-Meteo CAMS model tahmini.
 * Bakanlik API'si degildir; resmi Bakanlik sayfasina baglanti JS'dedir.
 * Mevcut veri dosyalarina yazmaz.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
date_default_timezone_set('Europe/Istanbul');
function result_json($data) { echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
$url='https://air-quality-api.open-meteo.com/v1/air-quality?latitude=39.9334&longitude=32.8597&current=european_aqi,pm2_5,pm10&timezone=Europe%2FIstanbul';
if (!function_exists('curl_init')) result_json(array('available'=>false,'reason'=>'PHP cURL etkin degil','source'=>'Open-Meteo / CAMS'));
$c=curl_init($url);
curl_setopt($c,CURLOPT_RETURNTRANSFER,true);
curl_setopt($c,CURLOPT_CONNECTTIMEOUT,7);
curl_setopt($c,CURLOPT_TIMEOUT,15);
curl_setopt($c,CURLOPT_SSL_VERIFYPEER,true);
curl_setopt($c,CURLOPT_HTTPHEADER,array('Accept: application/json'));
$raw=curl_exec($c); $code=(int)curl_getinfo($c,CURLINFO_HTTP_CODE); $error=curl_error($c); curl_close($c);
if ($raw===false || $code!==200) result_json(array('available'=>false,'reason'=>'Veri servisine erisilemiyor (HTTP '.$code.')','source'=>'Open-Meteo / CAMS'));
$d=json_decode($raw,true);
if (!is_array($d) || !isset($d['current']) || !isset($d['current']['european_aqi']) || !is_numeric($d['current']['european_aqi'])) result_json(array('available'=>false,'reason'=>'Gecerli AQI verisi donmedi','source'=>'Open-Meteo / CAMS'));
$cur=$d['current']; $aqi=(int)round((float)$cur['european_aqi']);
if ($aqi<=20)$label='İyi'; elseif ($aqi<=40)$label='Makul'; elseif ($aqi<=60)$label='Orta'; elseif ($aqi<=80)$label='Kötü'; elseif ($aqi<=100)$label='Çok kötü'; else $label='Son derece kötü';
result_json(array('available'=>true,'value'=>$aqi,'unit'=>'Avrupa HKİ','label'=>$label,'period'=>isset($cur['time'])?$cur['time']:null,'source'=>'Open-Meteo / CAMS (model tahmini)','pm25'=>isset($cur['pm2_5'])?$cur['pm2_5']:null,'pm10'=>isset($cur['pm10'])?$cur['pm10']:null,'measured'=>false));
