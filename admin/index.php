<?php
session_start();$c=require dirname(__DIR__).'/config.php';date_default_timezone_set($c['timezone']);function h($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}function ld($f){$d=json_decode(@file_get_contents($f),true);return is_array($d)?$d:array();}function sv($f,$d){return file_put_contents($f,json_encode($d,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),LOCK_EX)!==false;}
if(isset($_GET['logout'])){session_destroy();header('Location:index.php');exit;}if(!isset($_SESSION['admin'])){if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['user'],$_POST['pass'])&&$_POST['user']===$c['admin_user']&&$_POST['pass']===$c['admin_password']){$_SESSION['admin']=1;header('Location:index.php');exit;}?><!doctype html><html lang="tr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{font-family:Arial;background:#071426;color:#fff;display:grid;place-items:center;height:100vh}.b{background:#10233e;padding:30px;border-radius:20px;width:320px}input,button{width:100%;box-sizing:border-box;padding:13px;margin:7px 0;border-radius:10px;border:1px solid #345;background:#09192c;color:#fff}button{background:#169b68;border:0}</style><div class="b"><h2>🔐 Pano Yönetimi</h2><form method="post"><input name="user" placeholder="Kullanıcı"><input type="password" name="pass" placeholder="Şifre"><button>Giriş Yap</button></form></div></html><?php exit;}
$d=ld($c['data_file']);$msg='';
if(isset($_POST['lesson_action'])){
 $action=$_POST['lesson_action'];$idx=isset($_POST['lesson_index'])?(int)$_POST['lesson_index']:-1;
 if(!isset($d['DersSaatleri'])||!is_array($d['DersSaatleri']))$d['DersSaatleri']=array();
 if($action==='delete'&&isset($d['DersSaatleri'][$idx])){array_splice($d['DersSaatleri'],$idx,1);$msg=sv($c['data_file'],$d)?'Ders silindi.':'Kaydetme hatası.';}
 elseif($action==='add'||$action==='save'){
  $no=isset($_POST['lesson_no'])?trim($_POST['lesson_no']):'';
  $start=isset($_POST['lesson_start'])?trim($_POST['lesson_start']):'';
  $end=isset($_POST['lesson_end'])?trim($_POST['lesson_end']):'';
  $type=isset($_POST['lesson_type'])?trim($_POST['lesson_type']):'Normal';
  if($no===''||!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/',$start)||!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/',$end)||$end<=$start)$msg='Geçerli ders numarası ve başlangıç/bitiş saatleri girin.';
  else{$item=array('Ders'=>$no,'Başlangıç'=>$start,'Bitiş'=>$end,'Tür'=>$type);if($action==='add')$d['DersSaatleri'][]=$item;elseif(isset($d['DersSaatleri'][$idx]))$d['DersSaatleri'][$idx]=$item;
   usort($d['DersSaatleri'],function($a,$b){return strcmp($a['Başlangıç'],$b['Başlangıç']);});
   $msg=sv($c['data_file'],$d)?'Ders saatleri kaydedildi.':'Kaydetme hatası.';
  }
 }
}

if(isset($_POST['quote_action'])){
 $action=$_POST['quote_action'];if(!isset($d['Sozler'])||!is_array($d['Sozler']))$d['Sozler']=array();
 $i=isset($_POST['quote_index'])?(int)$_POST['quote_index']:-1;
 $quote=isset($_POST['quote_text'])?trim($_POST['quote_text']):'';
 $author=isset($_POST['quote_author'])?trim($_POST['quote_author']):'';
 if($action==='add'||$action==='save'){
  if($quote===''||$author==='')$msg='Söz ve söyleyen kişi boş bırakılamaz.';
  else { $item=array('Metin'=>$quote,'Kisi'=>$author,'Tarih'=>isset($_POST['quote_date'])?trim($_POST['quote_date']):'','Aktif'=>'1');
   if($action==='add')$d['Sozler'][]=$item;
   elseif(isset($d['Sozler'][$i])){$item['Aktif']=isset($d['Sozler'][$i]['Aktif'])?$d['Sozler'][$i]['Aktif']:'1';$d['Sozler'][$i]=$item;}
   $msg=sv($c['data_file'],$d)?'Söz kaydedildi.':'Söz kaydedilemedi.';
  }
 }elseif(isset($d['Sozler'][$i])){
  if($action==='delete')array_splice($d['Sozler'],$i,1);
  elseif($action==='toggle')$d['Sozler'][$i]['Aktif']=(isset($d['Sozler'][$i]['Aktif'])&&(string)$d['Sozler'][$i]['Aktif']==='0')?'1':'0';
  $msg=sv($c['data_file'],$d)?'Söz listesi güncellendi.':'Güncelleme başarısız.';
 }
}


if(isset($_POST['students_import'])){
 try {
  if(!isset($_FILES['student_excel']) || $_FILES['student_excel']['error']!==UPLOAD_ERR_OK)throw new Exception('Dosya yüklenemedi. PHP upload_max_filesize ve post_max_size ayarlarını kontrol edin.');
  require_once dirname(__DIR__).'/xlsx.php';
  $book=xlsx_rows($_FILES['student_excel']['tmp_name']);$new=array();$seen=array();$skipped=0;
  foreach($book as $sheet=>$rows){
   $header=-1;$keys=array();
   foreach($rows as $i=>$row){$names=array_map('trim',$row);if(in_array('Öğrenci Tam Adı',$names)&&in_array('Öğrenci Doğum Tarihi',$names)){$header=$i;$keys=$names;break;}}
   if($header<0)continue;
   foreach(array_slice($rows,$header+1) as $row){
    $r=array();foreach($keys as $i=>$key)if($key!=='')$r[$key]=isset($row[$i])?$row[$i]:'';
    $name=trim(isset($r['Öğrenci Tam Adı'])?$r['Öğrenci Tam Adı']:'');
    $cls=trim(isset($r['Öğrenci Şube'])?$r['Öğrenci Şube']:'');
    $birth=excel_date(isset($r['Öğrenci Doğum Tarihi'])?$r['Öğrenci Doğum Tarihi']:'');
    if($name===''||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$birth)){$skipped++;continue;}
    $key=strtolower($name).'|'.$cls.'|'.$birth;
    if(isset($seen[$key])){$skipped++;continue;}
    $seen[$key]=true;$new[]=array('Ad Soyad'=>$name,'Sınıf'=>$cls,'Doğum Tarihi'=>$birth);
   }
  }
  if(!count($new))throw new Exception('Öğrenci listesi okunamadı. Sayfa ve başlıklar algılanamadı; PHP ZipArchive/SimpleXML eklentilerini kontrol edin.');
  $mode=isset($_POST['import_mode'])?$_POST['import_mode']:'merge';
  $existing=isset($d['DogumGunleri'])&&is_array($d['DogumGunleri'])?$d['DogumGunleri']:array();
  if($mode==='replace')$existing=array();
  $unique=array();foreach($existing as $r){$k=strtolower(trim($r['Ad Soyad'])).'|'.trim($r['Sınıf']).'|'.trim($r['Doğum Tarihi']);$unique[$k]=$r;}
  $added=0;foreach($new as $r){$k=strtolower($r['Ad Soyad']).'|'.$r['Sınıf'].'|'.$r['Doğum Tarihi'];if(!isset($unique[$k]))$added++;$unique[$k]=$r;}
  $d['DogumGunleri']=array_values($unique);
  if(file_put_contents($c['data_file'],json_encode($d,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),LOCK_EX)===false)throw new Exception('Veri dosyasına yazılamadı.');
  $msg='Excel okundu: '.count($new).' geçerli kayıt; '.$added.' yeni; '.$skipped.' atlanan; toplam '.count($d['DogumGunleri']).' öğrenci.';
 }catch(Exception $e){$msg='Excel aktarım hatası: '.$e->getMessage();}
}
if(isset($_POST['bulk_delete_birthdays'])){
 $selected=isset($_POST['birthday_ids'])&&is_array($_POST['birthday_ids'])?$_POST['birthday_ids']:array();
 $all=isset($d['DogumGunleri'])?$d['DogumGunleri']:array();$ids=array();
 foreach($selected as $id)if(ctype_digit((string)$id))$ids[(int)$id]=true;
 if(count($ids)){
  $d['DogumGunleri']=array();foreach($all as $i=>$r)if(!isset($ids[$i]))$d['DogumGunleri'][]=$r;
  if(file_put_contents($c['data_file'],json_encode($d,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),LOCK_EX)===false)$msg='Silme başarısız: veri dosyası yazılamıyor.';
  else $msg=count($all)-count($d['DogumGunleri']).' öğrenci silindi.';
 }else $msg='Silmek için öğrenci seçin.';
}
if(isset($_POST['announcement_action'])){
 $act=$_POST['announcement_action'];$idx=isset($_POST['announcement_index'])?(int)$_POST['announcement_index']:-1;
 if(!isset($d['Duyurular'])||!is_array($d['Duyurular']))$d['Duyurular']=array();
 if(isset($d['Duyurular'][$idx])){
  if($act==='delete'){array_splice($d['Duyurular'],$idx,1);$msg='Duyuru silindi.';}
  elseif($act==='save'){
   $start=isset($_POST['start'])?trim($_POST['start']):'';
   $end=isset($_POST['end'])?trim($_POST['end']):'';
   $body=isset($_POST['body'])?trim($_POST['body']):'';
   if($body==='')$msg='Duyuru metni boş olamaz.';
   elseif($start!==''&&$end!==''&&$start>$end)$msg='Bitiş tarihi başlangıçtan önce olamaz.';
   else{$d['Duyurular'][$idx]['Başlangıç']=$start;$d['Duyurular'][$idx]['Bitiş']=$end;$d['Duyurular'][$idx]['Metin']=$body;$d['Duyurular'][$idx]['Emoji']=isset($_POST['announcement_emoji'])?trim($_POST['announcement_emoji']):'';$msg='Duyuru güncellendi.';}
  }elseif($act==='toggle'){$old=isset($d['Duyurular'][$idx]['Aktif'])?$d['Duyurular'][$idx]['Aktif']:'1';$d['Duyurular'][$idx]['Aktif']=((string)$old==='0'?'1':'0');$msg='Duyuru durumu değiştirildi.';}
  if($msg!==''&&sv($c['data_file'],$d)===false)$msg='Kaydedilemedi: veri dosyasının yazma izinlerini kontrol edin.';
 }else $msg='Duyuru kaydı bulunamadı.';
}
if(isset($_POST['announcement_add'])){
 $start=isset($_POST['announcement_start'])?trim($_POST['announcement_start']):'';
 $end=isset($_POST['announcement_end'])?trim($_POST['announcement_end']):'';
 $text=isset($_POST['announcement_text'])?trim($_POST['announcement_text']):'';
 if($text===''){$msg='Duyuru metni boş bırakılamaz.';}
 else{
  if(!isset($d['Duyurular'])||!is_array($d['Duyurular']))$d['Duyurular']=array();
  $d['Duyurular'][]=array('Başlangıç'=>$start,'Bitiş'=>$end,'Metin'=>$text,'Emoji'=>isset($_POST['announcement_emoji'])?trim($_POST['announcement_emoji']):'','Aktif'=>'1');
  if(sv($c['data_file'],$d)){header('Location:index.php?announcement_saved=1#announcements');exit;}
  else{$msg='Duyuru kaydedilemedi.';}
 }
}
if(isset($_GET['announcement_saved']))$msg='Duyuru başarıyla eklendi.';

if(isset($_POST['meal_excel_import'])){
 try{
  if(!isset($_FILES['meal_excel'])||$_FILES['meal_excel']['error']!==UPLOAD_ERR_OK)throw new Exception('Dosya yüklenemedi. Sunucunun yükleme limitini kontrol edin.');
  $ext=strtolower(pathinfo($_FILES['meal_excel']['name'],PATHINFO_EXTENSION));
  if($ext!=='xlsx'&&$ext!=='csv')throw new Exception('Yalnızca XLSX veya CSV yükleyin.');
  $all=array();
  if($ext==='xlsx'){
   require_once dirname(__DIR__).'/xlsx.php';
   $all=xlsx_rows($_FILES['meal_excel']['tmp_name']);
  }else{
   $fh=fopen($_FILES['meal_excel']['tmp_name'],'r');
   if(!$fh)throw new Exception('CSV okunamadı.');
   $first=fgets($fh);rewind($fh);$sep=substr_count($first,';')>substr_count($first,',')?';':',';
   $rows=array();while(($r=fgetcsv($fh,0,$sep))!==false)$rows[]=$r;fclose($fh);
   $all=array('Yemek'=>$rows);
  }
  $parsed=array();$skipped=0;$found=false;
  foreach($all as $sheet=>$rows){
   $header=-1;$cols=array();
   foreach($rows as $i=>$row){
    $normalized=array();foreach($row as $v)$normalized[]=trim((string)$v);
    if(in_array('Tarih',$normalized)&&in_array('Kahvaltı',$normalized)&&in_array('Öğle',$normalized)&&in_array('İkindi',$normalized)){$header=$i;$cols=$normalized;$found=true;break;}
   }
   if($header<0)continue;
   foreach(array_slice($rows,$header+1) as $row){
    $r=array();foreach($cols as $i=>$key)if($key!=='')$r[$key]=isset($row[$i])?trim((string)$row[$i]):'';
    $date=isset($r['Tarih'])?$r['Tarih']:'';
    if(is_numeric($date)&&(float)$date>20000)$date=gmdate('Y-m-d',(int)(((float)$date-25569)*86400));
    elseif(preg_match('/^(\d{2})[.\/](\d{2})[.\/](\d{4})$/',$date,$m))$date=$m[3].'-'.$m[2].'-'.$m[1];
    if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||!checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4))){$skipped++;continue;}
    $breakfast=isset($r['Kahvaltı'])?$r['Kahvaltı']:'';
    $lunch=isset($r['Öğle'])?$r['Öğle']:'';
    $snack=isset($r['İkindi'])?$r['İkindi']:'';
    if($breakfast===''&&$lunch===''&&$snack===''){$skipped++;continue;}
    $parsed[$date]=array('Tarih'=>$date,'Kahvaltı'=>$breakfast,'Öğle'=>$lunch,'İkindi'=>$snack);
   }
  }
  if(!$found)throw new Exception('Başlıklar bulunamadı: Tarih, Kahvaltı, Öğle, İkindi. Şablonu kullanın.');
  if(!count($parsed))throw new Exception('Geçerli tarihli yemek kaydı bulunamadı.');
  $mode=isset($_POST['meal_import_mode'])?$_POST['meal_import_mode']:'merge';
  $current=isset($d['Yemek'])&&is_array($d['Yemek'])?$d['Yemek']:array();
  $merged=array();
  if($mode!=='replace')foreach($current as $r)if(isset($r['Tarih']))$merged[$r['Tarih']]=$r;
  foreach($parsed as $date=>$r)$merged[$date]=$r;
  ksort($merged);$d['Yemek']=array_values($merged);
  if(!sv($c['data_file'],$d))throw new Exception('Kaydedilemedi: data/veriler.json yazma izni.');
  $msg='Yemek Excel aktarımı başarılı: '.count($parsed).' gün alındı, '.$skipped.' satır atlandı. Toplam '.count($d['Yemek']).' günlük menü.';
 }catch(Exception $e){$msg='Yemek Excel aktarım hatası: '.$e->getMessage();}
}
if(isset($_POST['duty_excel_import'])){
 try{
  if(!isset($_FILES['duty_excel'])||$_FILES['duty_excel']['error']!==UPLOAD_ERR_OK)throw new Exception('Dosya yüklenemedi.');
  $ext=strtolower(pathinfo($_FILES['duty_excel']['name'],PATHINFO_EXTENSION));
  $rows=array();
  if($ext==='xlsx'){
   require_once dirname(__DIR__).'/xlsx.php';
   $sheets=xlsx_rows($_FILES['duty_excel']['tmp_name']);
   foreach($sheets as $sheet=>$rr){foreach($rr as $r)$rows[]=$r;}
  }elseif($ext==='csv'){
   $fh=fopen($_FILES['duty_excel']['tmp_name'],'r');
   $sample=fgets($fh);rewind($fh);$sep=substr_count($sample,';')>substr_count($sample,',')?';':',';
   while(($r=fgetcsv($fh,0,$sep))!==false)$rows[]=$r;
   fclose($fh);
  }else throw new Exception('Yalnızca XLSX veya CSV yükleyin.');
  $header=-1;$cols=array();
  foreach($rows as $i=>$r){
   $norm=array();foreach($r as $v)$norm[]=trim((string)$v);
   if(in_array('Tarih',$norm)&&in_array('Görev Yeri',$norm)&&in_array('Öğretmen',$norm)){$header=$i;$cols=$norm;break;}
  }
  if($header<0)throw new Exception('Sütunlar bulunamadı. Başlıklar: Tarih, Görev Yeri, Öğretmen.');
  $added=array();$bad=0;
  foreach(array_slice($rows,$header+1) as $r){
   $rec=array();foreach($cols as $i=>$key)if($key!=='')$rec[$key]=isset($r[$i])?trim((string)$r[$i]):'';
   $date=isset($rec['Tarih'])?$rec['Tarih']:'';
   if(is_numeric($date)&&$date>20000)$date=gmdate('Y-m-d',((int)$date-25569)*86400);
   elseif(preg_match('/^(\d{2})[.\/](\d{2})[.\/](\d{4})$/',$date,$m))$date=$m[3].'-'.$m[2].'-'.$m[1];
   $place=isset($rec['Görev Yeri'])?$rec['Görev Yeri']:'';
   $teacher=isset($rec['Öğretmen'])?$rec['Öğretmen']:'';
   if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||!checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4))||$place===''||$teacher===''){$bad++;continue;}
   $added[]=array('Tarih'=>$date,'Görev Yeri'=>$place,'Öğretmen'=>$teacher);
  }
  if(!count($added))throw new Exception('Geçerli nöbet kaydı bulunamadı. Tarih biçimi YYYY-MM-DD olmalı.');
  $mode=isset($_POST['duty_import_mode'])?$_POST['duty_import_mode']:'merge';
  $existing=$mode==='replace'?array():(isset($d['Nobet'])?$d['Nobet']:array());
  $unique=array();
  foreach(array_merge($existing,$added) as $r){
   $key=$r['Tarih'].'|'.$r['Görev Yeri'].'|'.$r['Öğretmen'];
   $unique[$key]=$r;
  }
  $d['Nobet']=array_values($unique);
  if(!sv($c['data_file'],$d))throw new Exception('Veriler kaydedilemedi; dosya izinlerini kontrol edin.');
  $msg=count($added).' nöbet satırı okundu, '.$bad.' satır atlandı. Toplam '.count($d['Nobet']).' kayıt.';
 }catch(Exception $e){$msg='Nöbet Excel aktarım hatası: '.$e->getMessage();}
}
if(isset($_POST['bulk_delete_meals'])){
 $selected=isset($_POST['meal_ids'])&&is_array($_POST['meal_ids'])?$_POST['meal_ids']:array();
 $indices=array();foreach($selected as $id){if(ctype_digit((string)$id))$indices[(int)$id]=true;}
 $records=isset($d['Yemek'])&&is_array($d['Yemek'])?$d['Yemek']:array();
 $remaining=array();$removed=0;
 foreach($records as $i=>$record){if(isset($indices[$i]))$removed++;else $remaining[]=$record;}
 if($removed===0)$msg='Silmek için en az bir yemek kaydı seçin.';
 else{
  $d['Yemek']=$remaining;
  if(sv($c['data_file'],$d))$msg=$removed.' günlük yemek kaydı silindi.';
  else{$d['Yemek']=$records;$msg='Silme başarısız: veri dosyasına yazılamadı.';}
 }
}
if(isset($_POST['bulk_delete_duty'])){
 $selected=isset($_POST['duty_ids'])&&is_array($_POST['duty_ids'])?$_POST['duty_ids']:array();
 $indices=array();foreach($selected as $id){if(ctype_digit((string)$id))$indices[(int)$id]=true;}
 $records=isset($d['Nobet'])&&is_array($d['Nobet'])?$d['Nobet']:array();
 $remaining=array();$removed=0;
 foreach($records as $i=>$record){if(isset($indices[$i]))$removed++;else $remaining[]=$record;}
 if($removed===0)$msg='Silmek için en az bir nöbet kaydı seçin.';
 else{
  $d['Nobet']=$remaining;
  if(sv($c['data_file'],$d))$msg=$removed.' nöbet kaydı silindi.';
  else{$d['Nobet']=$records;$msg='Silme başarısız: veri dosyasına yazılamadı.';}
 }
}
if(isset($_POST['duty_add'])){
 $date=isset($_POST['duty_date'])?trim($_POST['duty_date']):'';
 $place=isset($_POST['duty_place'])?trim($_POST['duty_place']):'';
 $teacher=isset($_POST['duty_teacher'])?trim($_POST['duty_teacher']):'';
 if($date===''||$place===''||$teacher===''){$msg='Nöbet kaydı için Tarih, Görev Yeri ve Öğretmen zorunludur.';}
 else{
  if(!isset($d['Nobet'])||!is_array($d['Nobet']))$d['Nobet']=array();
  $d['Nobet'][]=array('Tarih'=>$date,'Görev Yeri'=>$place,'Öğretmen'=>$teacher);
  if(sv($c['data_file'],$d)){header('Location:index.php?duty_saved=1#duty');exit;}
  else{$msg='Nöbet kaydı kaydedilemedi. data/veriler.json yazma iznini kontrol edin.';}
 }
}
if(isset($_GET['duty_saved']))$msg='Nöbetçi öğretmen başarıyla eklendi.';

if(isset($_POST['birthday_add'])){
 $name=isset($_POST['birthday_name'])?trim($_POST['birthday_name']):'';
 $class=isset($_POST['birthday_class'])?trim($_POST['birthday_class']):'';
 $date=isset($_POST['birthday_date'])?trim($_POST['birthday_date']):'';
 if($name===''||$date===''){$msg='Ad Soyad ve Doğum Tarihi zorunludur.';}
 else{
  if(!isset($d['DogumGunleri'])||!is_array($d['DogumGunleri']))$d['DogumGunleri']=array();
  $d['DogumGunleri'][]=array('Ad Soyad'=>$name,'Sınıf'=>$class,'Doğum Tarihi'=>$date);
  if(sv($c['data_file'],$d)){header('Location:index.php?birthday_saved=1#birthdays');exit;}
  else{$msg='Doğum günü kaydedilemedi. data/veriler.json yazma iznini kontrol edin.';}
 }
}
if(isset($_GET['birthday_saved']))$msg='Doğum günü başarıyla eklendi.';

if(empty($_SESSION['logo_token'])) $_SESSION['logo_token']=bin2hex(random_bytes(24));
if(isset($_POST['logo_action'])){
 try{
  if(!isset($_POST['logo_token']) || !hash_equals($_SESSION['logo_token'],$_POST['logo_token'])) throw new Exception('İşlem doğrulanamadı. Sayfayı yenileyin.');
  $newLogo='';$dest=null;
  if($_POST['logo_action']==='upload'){
   if(!isset($_FILES['school_logo']) || $_FILES['school_logo']['error']!==UPLOAD_ERR_OK) throw new Exception('Logo yüklenemedi. Dosyayı ve sunucu yükleme sınırını kontrol edin.');
   $f=$_FILES['school_logo'];
   if($f['size']>2*1024*1024) throw new Exception('Logo en fazla 2 MB olabilir.');
   $info=@getimagesize($f['tmp_name']);
   $allowed=array('image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp');
   if(!$info || !isset($allowed[$info['mime']]) || $info[0]>4096 || $info[1]>4096) throw new Exception('En fazla 4096 × 4096 piksel PNG, JPG veya WEBP seçin.');
   $dir=dirname(__DIR__).'/assets/uploads';
   if(!is_dir($dir) && !mkdir($dir,0755,true)) throw new Exception('Yükleme klasörü oluşturulamadı.');
   $newLogo='assets/uploads/logo_'.bin2hex(random_bytes(12)).'.'.$allowed[$info['mime']];$dest=dirname(__DIR__).'/'.$newLogo;
   if(!move_uploaded_file($f['tmp_name'],$dest)) throw new Exception('Logo kaydedilemedi. Klasör yazma iznini kontrol edin.');
  }elseif($_POST['logo_action']!=='remove') throw new Exception('Geçersiz işlem.');
  $oldLogo=isset($d['Ayarlar']['Logo'])?$d['Ayarlar']['Logo']:'';
  $d['Ayarlar']['Logo']=$newLogo;
  if(!sv($c['data_file'],$d)){if($dest)@unlink($dest);$d['Ayarlar']['Logo']=$oldLogo;throw new Exception('Logo ayarı kaydedilemedi.');}
  if(preg_match('~^assets/uploads/logo_[a-f0-9]{24}\.(png|jpg|webp)$~D',$oldLogo)) @unlink(dirname(__DIR__).'/'.$oldLogo);
  $msg=$newLogo?'Logo yüklendi. Pano otomatik güncellenecek.':'Logo kaldırıldı.';
 }catch(Exception $e){$msg=$e->getMessage();}
}

if(isset($_POST['save_school_name'])){
 $newName=isset($_POST['school_name'])?trim($_POST['school_name']):'';
 if($newName!==''){
   if(!isset($d['Ayarlar']) || !is_array($d['Ayarlar'])) $d['Ayarlar']=array();
   $d['Ayarlar']['Okul Adı']=$newName;
   if(sv($c['data_file'],$d)) $msg='Okul adı başarıyla değiştirildi.';
   else $msg='Okul adı kaydedilemedi. data/veriler.json yazma iznini kontrol edin.';
 }
}

if(isset($_POST['save_general_settings'])){
 if(!isset($d['Ayarlar']) || !is_array($d['Ayarlar'])) $d['Ayarlar']=array();
 $keys=array('Okul Adı','Alt Başlık','Günün Mesajı','Kayan Yazı','Footer','Nöbetçi Başlığı','Menü Başlığı','Doğum Günü Başlığı','Önemli Gün Başlığı','Duyuru Başlığı','Önemli Gün Gösterim Süresi','Yenileme Süresi');
 foreach($keys as $k){
   if(isset($_POST[$k])) $d['Ayarlar'][$k]=trim($_POST[$k]);
 }
 if((int)$d['Ayarlar']['Önemli Gün Gösterim Süresi']<1) $d['Ayarlar']['Önemli Gün Gösterim Süresi']='10';
 if((int)$d['Ayarlar']['Yenileme Süresi']<5) $d['Ayarlar']['Yenileme Süresi']='15';
 if(sv($c['data_file'],$d)) $msg='Genel pano ayarları kaydedildi.';
 else $msg='Ayarlar kaydedilemedi. data/veriler.json yazma iznini kontrol edin.';
}
$types=array('Nobet'=>'👨‍🏫 Nöbetçi Öğretmen','Yemek'=>'🍽️ Günün Menüsü','DogumGunleri'=>'🎂 Doğum Günleri','OnemliGunler'=>'📅 Önemli Günler','Slider'=>'🖼️ Slider Görselleri');$fields=array('Nobet'=>array('Tarih','Görev Yeri','Öğretmen'),'Yemek'=>array('Tarih','Kahvaltı','Öğle','İkindi'),'DogumGunleri'=>array('Ad Soyad','Sınıf','Doğum Tarihi'),'OnemliGunler'=>array('Tarih','Başlık','Açıklama'),'Slider'=>array('Başlık','Alt Yazı','Görsel','Süre','Aktif'));
if(isset($_POST['slider_upload'])&&isset($_FILES['slider_image'])&&$_FILES['slider_image']['error']===UPLOAD_ERR_OK){
 $ext=strtolower(pathinfo($_FILES['slider_image']['name'],PATHINFO_EXTENSION));$ok=array('jpg','jpeg','png','webp');
 if(in_array($ext,$ok)){ $name='slider_'.date('Ymd_His').'_'.mt_rand(100,999).'.'.$ext;$dest=dirname(__DIR__).'/assets/uploads/'.$name;
  if(move_uploaded_file($_FILES['slider_image']['tmp_name'],$dest)){if(!isset($d['Slider']))$d['Slider']=array();$d['Slider'][]=array('Başlık'=>isset($_POST['slider_title'])?trim($_POST['slider_title']):'','Alt Yazı'=>isset($_POST['slider_sub'])?trim($_POST['slider_sub']):'','Görsel'=>'assets/uploads/'.$name,'Süre'=>isset($_POST['slider_duration'])?trim($_POST['slider_duration']):'10','Aktif'=>'1');sv($c['data_file'],$d);$msg='Slider görseli yüklendi.';}else{$msg='Görsel sunucuya kaydedilemedi. uploads klasörü yazılabilir olmalı.';}
 }else{$msg='Sadece JPG, PNG veya WEBP yükleyebilirsiniz.';}
}
if(isset($_POST['action'],$_POST['type'])&&isset($types[$_POST['type']])){$t=$_POST['type'];if(!isset($d[$t]))$d[$t]=array();if($_POST['action']==='add'){
$r=array();foreach($fields[$t]as$f)$r[$f]=isset($_POST[$f])?trim($_POST[$f]):'';
if($t==='DogumGunleri'&&($r['Ad Soyad']===''||$r['Doğum Tarihi']==='')){$msg='Doğum günü için Ad Soyad ve Doğum Tarihi zorunludur.';}
else{$d[$t][]=$r;$msg='Kayıt eklendi.';}
}elseif($_POST['action']==='edit'){
$i=(int)$_POST['i'];$r=array();foreach($fields[$t]as$f)$r[$f]=isset($_POST[$f])?trim($_POST[$f]):'';
if($t==='DogumGunleri'&&($r['Ad Soyad']===''||$r['Doğum Tarihi']==='')){$msg='Ad Soyad ve Doğum Tarihi boş bırakılamaz.';}
elseif(isset($d[$t][$i])){$d[$t][$i]=$r;$msg='Kayıt güncellendi.';}
}elseif($_POST['action']==='delete'){$i=(int)$_POST['i'];if(isset($d[$t][$i]))array_splice($d[$t],$i,1);$msg='Kayıt silindi.';}sv($c['data_file'],$d);}
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pano Yönetimi</title><style>*{box-sizing:border-box}body{margin:0;font-family:Arial;background:#071426;color:#eaf2ff}.top{padding:18px 4%;background:#0d1d33;display:flex;justify-content:space-between}.wrap{width:94%;max-width:1300px;margin:25px auto}.card{background:#0e213a;border:1px solid #203b5d;border-radius:18px;padding:20px;margin:18px 0}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px}input,button{padding:11px;border-radius:9px;border:1px solid #34516f;background:#09192c;color:#fff;width:100%}button{background:#169b68;border:0;font-weight:bold}.danger{background:#b63b4c}.edit{background:#246bb2}.msg{padding:12px;background:#123d32;border-radius:10px}.row{display:grid;grid-template-columns:repeat(6,1fr);gap:8px;padding:9px 0;border-bottom:1px solid #ffffff12}a{color:#8fe0bd}label{display:grid;gap:7px;color:#b9c9dc;font-size:13px}@media(max-width:800px){.row{grid-template-columns:1fr}.top{flex-direction:column;gap:10px}}</style><style>
body{background:#071426!important}.wrap{margin:18px 2% 24px 274px!important;width:auto!important;max-width:none!important}.v48-nav{position:fixed;top:62px;bottom:0;left:0;width:250px;overflow-y:auto;background:#0b1c33;border-right:1px solid #274260;padding:18px 12px;z-index:20}.v48-nav button{display:flex;gap:10px;align-items:center;text-align:left;width:100%;padding:12px;margin:3px 0;border-radius:10px;background:transparent;color:#bdcde1;cursor:pointer}.v48-nav button.selected{background:#23548a;color:#fff}.v48-nav h3{font-size:13px;letter-spacing:1px;color:#8fa9c8;padding-left:10px}.v48-group{display:none}.v48-group.selected{display:block}.v48-group>.card{margin:12px 0}.v48-heading{font-size:25px;margin:12px 0 18px}.v48-note{color:#9db5cf;font-size:13px;margin:0 0 14px}@media(max-width:900px){.v48-nav{position:relative;top:auto;bottom:auto;width:auto;display:flex;overflow-x:auto;white-space:nowrap;padding:8px}.v48-nav h3{display:none}.v48-nav button{width:auto;min-width:max-content}.wrap{margin:12px!important;width:auto!important;padding:0 12px}.top{position:relative}}
</style></head><body><div class="top"><b>🏫 Okul • Dijital Pano Yönetimi</b><div><a href="../" target="_blank">Panoyu Aç</a> &nbsp; <a href="?logout=1" style="color:#ff9aa8">Çıkış</a></div></div><div class="wrap"><div class="card" id="lesson-manager"><h2>🕒 Ders Saatleri Yönetimi</h2>
<p class="hint">Mevcut ders saatlerini burada görebilir, değiştirebilir veya yeni ders ekleyebilirsiniz. Kaydedilen bilgiler panoya otomatik yansır.</p>
<form method="post" class="grid">
<input name="lesson_no" placeholder="Ders no (ör. 1)" required>
<input type="time" name="lesson_start" required><input type="time" name="lesson_end" required>
<select name="lesson_type"><option value="Normal">Normal</option><option value="DYK">DYK</option></select>
<button class="save" name="lesson_action" value="add">+ Ders Ekle</button></form>
<h3>Kayıtlı Ders Saatleri</h3>
<?php foreach(isset($d['DersSaatleri'])?$d['DersSaatleri']:array() as $i=>$lesson){ ?>
<form method="post" class="grid" style="margin:10px 0;border-top:1px solid #34516f;padding-top:10px">
<input type="hidden" name="lesson_index" value="<?php echo (int)$i;?>">
<input name="lesson_no" value="<?php echo h(isset($lesson['Ders'])?$lesson['Ders']:'');?>" required>
<input type="time" name="lesson_start" value="<?php echo h(isset($lesson['Başlangıç'])?$lesson['Başlangıç']:'');?>" required>
<input type="time" name="lesson_end" value="<?php echo h(isset($lesson['Bitiş'])?$lesson['Bitiş']:'');?>" required>
<select name="lesson_type"><option value="Normal" <?php echo !isset($lesson['Tür'])||$lesson['Tür']!=='DYK'?'selected':'';?>>Normal</option><option value="DYK" <?php echo isset($lesson['Tür'])&&$lesson['Tür']==='DYK'?'selected':'';?>>DYK</option></select>
<button name="lesson_action" value="save">Kaydet</button>
<button class="danger" name="lesson_action" value="delete" formnovalidate onclick="return confirm('Ders kaydı silinsin mi?')">Sil</button></form>
<?php } ?></div>
<div class="card" id="quotes" style="border:2px solid #25a7e8">
<h2>📜 Günün Mesajı – Önemli Kişilerin Sözleri</h2><p class="hint">Söz ve söyleyen kişinin adını girin. Tarih seçerek her gün için ayrı söz belirleyebilirsiniz. Tarihi boş olan sözler, o güne özel söz bulunmadığında dönüşümlü gösterilir.</p>
<form method="post" action="index.php#quotes" class="grid"><input name="quote_text" placeholder="Söz / alıntı" required><input name="quote_author" placeholder="Söyleyen kişi" required><input type="date" name="quote_date" title="Gösterilecek gün (isteğe bağlı)"><button type="submit" name="quote_action" value="add">+ SÖZ EKLE</button></form>
<h3>Kayıtlı Sözler</h3>
<?php foreach(isset($d['Sozler'])?$d['Sozler']:array() as $i=>$soz){$on=!isset($soz['Aktif'])||(string)$soz['Aktif']!=='0'; ?>
<form method="post" action="index.php#quotes" class="grid" style="padding:9px 0;border-top:1px solid #34516f">
<input type="hidden" name="quote_index" value="<?php echo $i;?>">
<input name="quote_text" value="<?php echo h(isset($soz['Metin'])?$soz['Metin']:'');?>" required>
<input name="quote_author" value="<?php echo h(isset($soz['Kisi'])?$soz['Kisi']:'');?>" required><input type="date" name="quote_date" value="<?php echo h(isset($soz['Tarih'])?$soz['Tarih']:'');?>" title="Gösterilecek gün">
<button name="quote_action" value="save">Kaydet</button><button name="quote_action" value="toggle" formnovalidate><?php echo $on?'Pasif Yap':'Aktif Yap';?></button>
<button class="danger" name="quote_action" value="delete" formnovalidate onclick="return confirm('Söz silinsin mi?')">Sil</button>
</form><?php } ?>
</div><div class="card" id="students-upload"><h2>📊 Öğrenci Excel Aktarımı</h2><p class="hint">Öğrenci Listesi Sihirbazı XLSX dosyasını yükleyin. Aktarım sonucu kayıt sayıları gösterilir.</p>
<form method="post" enctype="multipart/form-data" class="grid"><input type="file" name="student_excel" accept=".xlsx" required>
<select name="import_mode" style="padding:11px;background:#09192c;color:white;border:1px solid #34516f;border-radius:9px"><option value="merge">Mevcut listeye ekle / güncelle</option><option value="replace">Mevcut öğrenci listesini tamamen değiştir</option></select>
<button type="submit" name="students_import" value="1">Excel'den Aktar</button></form></div>
<div class="card" id="bulk-birthdays"><h2>🗑️ Öğrencileri Toplu Sil</h2><p class="hint">Silmek istediğiniz öğrencileri işaretleyin. İşlem yalnızca doğum günü listesini etkiler.</p>
<form method="post" onsubmit="return confirm('Seçili öğrenciler kalıcı olarak silinsin mi?');">
<label style="display:flex;align-items:center;gap:8px"><input type="checkbox" id="select-all-birthdays" style="width:auto"> Tümünü seç</label>
<div style="max-height:360px;overflow:auto;margin:12px 0">
<?php foreach(isset($d['DogumGunleri'])?$d['DogumGunleri']:array() as $i=>$student){ ?>
<label style="display:flex;gap:10px;align-items:center;padding:6px;border-bottom:1px solid #ffffff22"><input class="birthday-select" type="checkbox" style="width:auto" name="birthday_ids[]" value="<?php echo $i;?>"><span><?php echo h($student['Ad Soyad']);?> — <?php echo h($student['Sınıf']);?> — <?php echo h($student['Doğum Tarihi']);?></span></label>
<?php } ?></div><button type="submit" class="danger" name="bulk_delete_birthdays" value="1">Seçilen Öğrencileri Sil</button></form>
<script>document.getElementById('select-all-birthdays').addEventListener('change',function(){document.querySelectorAll('.birthday-select').forEach(function(e){e.checked=document.getElementById('select-all-birthdays').checked})});</script>
</div>
<div class="card" id="announcements" style="border:2px solid #25a7e8">
<h2>📢 Duyuru Ekle</h2>
<p class="hint">Başlangıç ve bitiş tarihi arasında duyuru panonun kayan yazı bölümünde otomatik görünür.</p>
<form method="post" action="index.php#announcements" class="grid">
<input type="date" name="announcement_start" aria-label="Başlangıç tarihi">
<input type="date" name="announcement_end" aria-label="Bitiş tarihi">
<input type="text" name="announcement_emoji" placeholder="Emoji (ör. 📢 🎉 🇹🇷)" maxlength="16"><input type="text" name="announcement_text" placeholder="Duyuru metni" required>
<button class="save" type="submit" name="announcement_add" value="1">+ DUYURU EKLE</button>
</form>
<h3 style="margin-top:24px">Kayıtlı Duyurular</h3>
<?php if(empty($d['Duyurular'])){ ?><p class="hint">Henüz kayıtlı duyuru yok.</p><?php }else{foreach($d['Duyurular'] as $i=>$du){
$active=!isset($du['Aktif'])||(string)$du['Aktif']!=='0'; ?>
<form method="post" action="index.php#announcements" style="padding:12px 0;border-top:1px solid #38506d">
<input type="hidden" name="announcement_index" value="<?php echo (int)$i; ?>">
<div class="grid">
<label>Başlangıç<input type="date" name="start" value="<?php echo h(isset($du['Başlangıç'])?$du['Başlangıç']:''); ?>"></label>
<label>Bitiş<input type="date" name="end" value="<?php echo h(isset($du['Bitiş'])?$du['Bitiş']:''); ?>"></label>
<label>Emoji<input type="text" name="announcement_emoji" maxlength="16" value="<?php echo h(isset($du['Emoji'])?$du['Emoji']:''); ?>" placeholder="🎉"></label><label>Metin<input type="text" name="body" value="<?php echo h(isset($du['Metin'])?$du['Metin']:''); ?>" required></label>
<button type="submit" name="announcement_action" value="save">Kaydet</button>
<button type="submit" name="announcement_action" value="toggle" formnovalidate><?php echo $active?'Pasif Yap':'Aktif Yap'; ?></button>
<button type="submit" name="announcement_action" value="delete" formnovalidate style="background:#b43a51" onclick="return confirm('Duyuru silinsin mi?')">Sil</button>
</div><small><?php echo $active?'Aktif':'Pasif'; ?></small>
</form><?php }} ?>
</div>
<div class="card" id="meal-excel-import" style="border:2px solid #22b7aa"><h2>🍽️ Yemek Listesi Excel Yükle</h2>
<p class="hint">Excel veya CSV sütunları: <b>Tarih | Kahvaltı | Öğle | İkindi</b>. Tarih 2026-10-08 veya 08.10.2026 olabilir. Excel'deki diğer sayfalar da taranır.</p>
<form method="post" enctype="multipart/form-data" class="grid">
<input type="file" name="meal_excel" accept=".xlsx,.csv" required>
<select name="meal_import_mode"><option value="merge">Mevcut menüleri koru; aynı tarihleri güncelle</option><option value="replace">Tüm yemek listesini yeni dosyayla değiştir</option></select>
<button type="submit" class="save" name="meal_excel_import" value="1">Yemek Excel Dosyasını Aktar</button></form>
<p class="hint">Görsel JPG dosyası doğrudan Excel değildir. Menüleri şablona yazıp yükleyin.</p></div>
<div class="card" id="meal-bulk" style="border:2px solid #22b7aa">
<h2>☑️ Yemek Listesi — Toplu Seçim ve Silme</h2>
<p class="hint">Seçtiğiniz tarihlerdeki kahvaltı, öğle ve ikindi menüleri birlikte silinir. Öğrenci, nöbet ve duyuru kayıtları korunur.</p>
<form method="post" action="index.php#meal-bulk" onsubmit="var n=document.querySelectorAll('.meal-bulk-check:checked').length;if(!n){alert('Önce yemek kaydı seçin.');return false;}return confirm(n+' günlük yemek kaydı kalıcı olarak silinecek. Devam edilsin mi?');">
<div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
<label style="display:flex;align-items:center;gap:8px"><input id="meal-select-all" type="checkbox" style="width:auto"> Tümünü seç / seçimi kaldır</label>
<span id="meal-selected-count" class="hint">0 kayıt seçildi</span>
<button type="submit" name="bulk_delete_meals" value="1" class="danger" style="width:auto;padding:10px 20px">Seçilen Yemekleri Sil</button>
</div>
<div style="max-height:420px;overflow:auto;border:1px solid #25436b;border-radius:10px">
<?php foreach(isset($d['Yemek'])&&is_array($d['Yemek'])?$d['Yemek']:array() as $i=>$rec){ ?>
<label style="display:flex;gap:12px;align-items:center;padding:9px 12px;border-bottom:1px solid #ffffff16">
<input class="meal-bulk-check" type="checkbox" name="meal_ids[]" value="<?php echo (int)$i;?>" style="width:auto;flex:none">
<span><b><?php echo h(isset($rec['Tarih'])?$rec['Tarih']:'');?></b> &nbsp; | &nbsp; Kahvaltı: <?php echo h(isset($rec['Kahvaltı'])?$rec['Kahvaltı']:'');?> &nbsp; | &nbsp; Öğle: <?php echo h(isset($rec['Öğle'])?$rec['Öğle']:'');?> &nbsp; | &nbsp; İkindi: <?php echo h(isset($rec['İkindi'])?$rec['İkindi']:'');?></span>
</label>
<?php } ?>
</div></form>
<script>(function(){var all=document.getElementById('meal-select-all'),checks=document.querySelectorAll('.meal-bulk-check'),count=document.getElementById('meal-selected-count');function update(){var n=0;for(var i=0;i<checks.length;i++)if(checks[i].checked)n++;count.textContent=n+' kayıt seçildi';all.checked=checks.length>0&&n===checks.length;all.indeterminate=n>0&&n<checks.length;}all.addEventListener('change',function(){for(var i=0;i<checks.length;i++)checks[i].checked=all.checked;update()});for(var i=0;i<checks.length;i++)checks[i].addEventListener('change',update);update()})();</script>
</div>
<div class="card" id="duty-import"><h2>📊 Nöbetçi Öğretmen Excel Yükle</h2>
<p class="hint">XLSX veya CSV yükleyin. Başlıklar: <b>Tarih | Görev Yeri | Öğretmen</b>. Her öğretmen ayrı satırda olmalı. Tarih: 2026-10-08.</p>
<form method="post" enctype="multipart/form-data" class="grid">
<input type="file" name="duty_excel" accept=".xlsx,.csv" required>
<select name="duty_import_mode"><option value="merge">Mevcut nöbetlere ekle (tekrarları atla)</option><option value="replace">Tüm nöbet listesini yenisiyle değiştir</option></select>
<button class="save" type="submit" name="duty_excel_import" value="1">Nöbet Excel Dosyasını Aktar</button></form></div>
<div class="card" id="duty-bulk" style="border:2px solid #2a779d">
<h2>☑️ Nöbetçi Öğretmenler — Toplu Seçim ve Silme</h2>
<p class="hint">Yalnızca seçtiğiniz nöbet kayıtları silinir. Öğrenciler, yemekler, duyurular ve diğer bilgiler etkilenmez.</p>
<form method="post" action="index.php#duty-bulk" onsubmit="var n=document.querySelectorAll('.duty-bulk-check:checked').length;if(!n){alert('Önce kayıt seçin.');return false;}return confirm(n+' nöbet kaydı kalıcı olarak silinecek. Devam edilsin mi?');">
<div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
<label style="display:flex;align-items:center;gap:8px"><input id="duty-select-all" type="checkbox" style="width:auto"> Tümünü seç / seçimi kaldır</label>
<span id="duty-selected-count" class="hint">0 kayıt seçildi</span>
<button type="submit" name="bulk_delete_duty" value="1" class="danger" style="width:auto;padding:10px 20px">Seçilen Nöbetleri Sil</button>
</div>
<div style="max-height:420px;overflow:auto;border:1px solid #25436b;border-radius:10px">
<?php foreach(isset($d['Nobet'])&&is_array($d['Nobet'])?$d['Nobet']:array() as $i=>$rec){ ?>
<label style="display:flex;gap:12px;align-items:center;padding:9px 12px;border-bottom:1px solid #ffffff16">
<input class="duty-bulk-check" type="checkbox" name="duty_ids[]" value="<?php echo (int)$i;?>" style="width:auto;flex:none">
<span><?php echo h(isset($rec['Tarih'])?$rec['Tarih']:'');?> &nbsp; | &nbsp; <?php echo h(isset($rec['Görev Yeri'])?$rec['Görev Yeri']:'');?> &nbsp; | &nbsp; <b><?php echo h(isset($rec['Öğretmen'])?$rec['Öğretmen']:'');?></b></span>
</label>
<?php } ?>
</div></form>
<script>(function(){var all=document.getElementById('duty-select-all'),checks=document.querySelectorAll('.duty-bulk-check'),count=document.getElementById('duty-selected-count');function update(){var n=0;for(var i=0;i<checks.length;i++)if(checks[i].checked)n++;count.textContent=n+' kayıt seçildi';all.checked=checks.length>0&&n===checks.length;all.indeterminate=n>0&&n<checks.length;}all.addEventListener('change',function(){for(var i=0;i<checks.length;i++)checks[i].checked=all.checked;update()});for(var i=0;i<checks.length;i++)checks[i].addEventListener('change',update);update()})();</script>
</div>
<div class="card" id="duty" style="border:2px solid #25a7e8">
<h2>👩‍🏫 Nöbetçi Öğretmen Ekle</h2>
<p class="hint">Tarih, görev yeri ve öğretmeni seçip ekleyin. Üç alan da zorunludur.</p>
<form method="post" action="index.php#duty" class="grid">
<input type="date" name="duty_date" required>
<input type="text" name="duty_place" placeholder="Görev Yerini Yazın (örn. Arka Bahçe)" required>
<input type="text" name="duty_teacher" placeholder="Öğretmen Adı Soyadı" required>
<button class="save" type="submit" name="duty_add" value="1">+ NÖBETÇİ ÖĞRETMEN EKLE</button>
</form></div>
<div class="card" id="birthdays" style="border:2px solid #25a7e8">
<h2>🎂 Doğum Günü Ekle</h2>
<p class="hint">Öğrencinin adını, sınıfını ve doğum tarihini girip EKLE'ye basın.</p>
<form method="post" action="index.php#birthdays" class="grid">
<input type="text" name="birthday_name" placeholder="Ad Soyad" required>
<input type="text" name="birthday_class" placeholder="Sınıf (örn. 8/F)">
<input type="date" name="birthday_date" required>
<button class="save" type="submit" name="birthday_add" value="1">+ DOĞUM GÜNÜ EKLE</button>
</form></div>

<div class="card" id="logo-manager"><h2>Okul Logosu</h2>
<p>PNG, JPG veya WEBP · En fazla 2 MB ve 4096 × 4096 piksel. Şeffaf arka planlı PNG önerilir.</p>
<?php $logo=isset($d['Ayarlar']['Logo'])?$d['Ayarlar']['Logo']:''; if($logo){ ?>
<img src="../<?php echo h($logo);?>" alt="Mevcut okul logosu" style="width:110px;height:110px;object-fit:contain">
<?php }else{ ?><p>Henüz logo yüklenmedi.</p><?php } ?>
<form method="post" enctype="multipart/form-data" class="grid">
<input type="hidden" name="logo_token" value="<?php echo h($_SESSION['logo_token']);?>">
<input type="file" name="school_logo" accept="image/png,image/jpeg,image/webp" required aria-label="Okul logosu">
<button name="logo_action" value="upload">Logo Yükle / Değiştir</button></form>
<?php if($logo){ ?><form method="post" style="margin-top:12px">
<input type="hidden" name="logo_token" value="<?php echo h($_SESSION['logo_token']);?>">
<button class="danger" name="logo_action" value="remove">Logoyu Kaldır</button></form><?php } ?></div>
<div class="card" style="border:2px solid #2ea6ff;background:#102b4d">
<h2 style="margin-top:0">⚙️ GENEL PANO AYARLARI</h2>
<p class="hint">Bu bölümden okul adını doğrudan değiştirebilirsiniz.</p>
<form method="post">
<label style="display:block;font-weight:700;margin:10px 0 6px">Okul Adı</label>
<input style="font-size:17px" type="text" name="school_name" value="<?php echo h(isset($d['Ayarlar']['Okul Adı'])?$d['Ayarlar']['Okul Adı']:'');?>" placeholder="Okul adını yazın" required>
<button style="margin-top:12px;background:#15976a" name="save_school_name" value="1">OKUL ADINI KAYDET</button>
</form>
<p class="hint" style="margin-bottom:0">Kaydettikten sonra açık pano yeni adı otomatik alır.</p>
</div>
<?php if($msg)echo'<div class="msg">'.h($msg).'</div>';?>
<div class="card"><h2>🖼️ Yeni Slider Görseli Yükle</h2><form method="post" enctype="multipart/form-data" class="grid"><input type="file" name="slider_image" accept="image/jpeg,image/png,image/webp" required><input name="slider_title" placeholder="Başlık (isteğe bağlı)"><input name="slider_sub" placeholder="Alt yazı (isteğe bağlı)"><input type="number" min="3" max="120" name="slider_duration" value="10" placeholder="Süre (sn)"><button name="slider_upload" value="1">Görseli Yükle</button></form></div>
<?php foreach($types as$t=>$label){?><div class="card"><h2><?php echo$label;?></h2><?php if($t==='DogumGunleri'){?><p class="hint">Ad Soyad + Sınıf + Doğum Tarihi girin. Kişi, doğum gününe 10 gün kala ana panoda otomatik görünür.</p><?php }?><form method="post" class="grid"><input type="hidden" name="action" value="add"><input type="hidden" name="type" value="<?php echo h($t);?>"><?php foreach($fields[$t]as$f){$tp=(strpos($f,'Tarih')!==false)?'date':'text';?><input type="<?php echo$tp;?>" name="<?php echo h($f);?>" placeholder="<?php echo h($f);?>" required><?php }?><button>+ Ekle</button></form><div style="margin-top:15px"><?php foreach(isset($d[$t])?$d[$t]:array()as$i=>$r){?><form method="post" class="row"><input type="hidden" name="type" value="<?php echo h($t);?>"><input type="hidden" name="i" value="<?php echo$i;?>"><?php foreach($fields[$t]as$f){$et=($t==='DogumGunleri'&&$f==='Doğum Tarihi')?'date':'text';$rq=($t==='DogumGunleri'&&($f==='Ad Soyad'||$f==='Doğum Tarihi'))?' required':'';?><input type="<?php echo $et;?>" name="<?php echo h($f);?>" value="<?php echo h(isset($r[$f])?$r[$f]:'');?>"<?php echo $rq;?>><?php }?><button class="edit" name="action" value="edit">Kaydet</button><button class="danger" name="action" value="delete" onclick="return confirm('Silinsin mi?')">Sil</button></form><?php }?></div></div><?php }?></div><script>
(function(){
var wrap=document.querySelector('.wrap');if(!wrap)return;
var cards=Array.prototype.slice.call(wrap.children).filter(function(el){return el.classList&&el.classList.contains('card')});
var definitions=[
['sozler','Günün Sözü','📜'],['ogrenciler','Öğrenciler / Doğum Günleri','🎂'],['duyurular','Duyurular','📢'],['yemek','Yemek Listesi / Günün Menüsü','🍽️'],['nobet','Nöbetçi Öğretmenler','👩‍🏫'],['ayarlar','Genel Ayarlar','⚙️'],['slider','Slider','🖼️'],['onemli','Önemli Günler','📅'],['ders','Ders Saatleri','🕒']
];
var nav=document.createElement('nav');nav.className='v48-nav';nav.setAttribute('aria-label','Yönetim bölümleri');nav.innerHTML='<h3>YÖNETİM MENÜSÜ</h3>';document.querySelector('.top').insertAdjacentElement('afterend',nav);
var groups={};definitions.forEach(function(x){var div=document.createElement('section');div.className='v48-group';div.id='v48-'+x[0];var h=document.createElement('h2');h.className='v48-heading';h.textContent=x[2]+' '+x[1];div.appendChild(h);groups[x[0]]=div;wrap.appendChild(div);var btn=document.createElement('button');btn.type='button';btn.textContent=x[2]+' '+x[1];btn.dataset.tab=x[0];btn.addEventListener('click',function(){show(x[0])});nav.appendChild(btn)});
var map={'quotes':'sozler','students-upload':'ogrenciler','bulk-birthdays':'ogrenciler','birthdays':'ogrenciler','announcements':'duyurular','meal-excel-import':'yemek','meal-bulk':'yemek','meal-manager':'yemek','duty-import':'nobet','duty-bulk':'nobet','duty':'nobet','lesson-manager':'ders'};
cards.forEach(function(card){var dest=map[card.id];if(!dest){var heading=card.querySelector('h2');var label=heading?heading.textContent.toLowerCase():'';if(label.indexOf('ayar')>=0||label.indexOf('okul adı')>=0)dest='ayarlar';else if(label.indexOf('slider')>=0)dest='slider';else if(label.indexOf('nöbet')>=0)dest='nobet';else if(label.indexOf('yemek')>=0||label.indexOf('günün menüsü')>=0||label.indexOf('menü')>=0)dest='yemek';else if(label.indexOf('doğum')>=0)dest='ogrenciler';else if(label.indexOf('duyuru')>=0)dest='duyurular';else if(label.indexOf('ders')>=0)dest='ders';else if(label.indexOf('önemli')>=0)dest='onemli';else dest='ayarlar'}groups[dest].appendChild(card)});
function show(id){definitions.forEach(function(x){groups[x[0]].classList.toggle('selected',x[0]===id)});Array.prototype.forEach.call(nav.querySelectorAll('button'),function(b){b.classList.toggle('selected',b.dataset.tab===id)});try{history.replaceState(null,'','#'+id)}catch(e){}}
var tab=(location.hash||'').replace('#','');var aliases={'quotes':'sozler','announcements':'duyurular','duty':'nobet','duty-import':'nobet','birthdays':'ogrenciler','students-upload':'ogrenciler','meal-excel-import':'yemek','meal-bulk':'yemek'};tab=aliases[tab]||tab;if(!groups[tab])tab='ayarlar';show(tab);
})();
</script></body></html>
