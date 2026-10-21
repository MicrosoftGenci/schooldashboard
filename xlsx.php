<?php
function xlsx_rows($file) {
 if (!class_exists('ZipArchive') || !function_exists('simplexml_load_string')) throw new Exception('ZipArchive veya SimpleXML eklentisi eksik.');
 $z=new ZipArchive(); if($z->open($file)!==true) throw new Exception('Excel dosyası açılamadı.');
 $shared=array(); $xml=$z->getFromName('xl/sharedStrings.xml');
 if($xml!==false){$sx=simplexml_load_string($xml);foreach($sx->xpath('//*[local-name()="si"]') as $si){$t='';foreach($si->xpath('.//*[local-name()="t"]') as $n)$t.=(string)$n;$shared[]=$t;}}
 $wb=simplexml_load_string($z->getFromName('xl/workbook.xml'));
 $rels=simplexml_load_string($z->getFromName('xl/_rels/workbook.xml.rels'));
 $relmap=array();foreach($rels->xpath('//*[local-name()="Relationship"]') as $r)$relmap[(string)$r['Id']]=(string)$r['Target'];
 $ns=$wb->getNamespaces(true);$rns=isset($ns['r'])?$ns['r']:'';$out=array();
 foreach($wb->xpath('//*[local-name()="sheet"]') as $sh){
  $name=(string)$sh['name'];$a=$sh->attributes($rns);$id=(string)$a['id'];$target=isset($relmap[$id])?$relmap[$id]:'';
  $path=substr($target,0,1)==='/'?ltrim($target,'/'):'xl/'.ltrim(str_replace('../','',$target),'/');
  $raw=$z->getFromName($path);if($raw===false)continue;$sx=simplexml_load_string($raw);$rows=array();
  foreach($sx->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') as $row){$arr=array();foreach($row->xpath('./*[local-name()="c"]') as $c){
   $ref=(string)$c['r'];if(!preg_match('/([A-Z]+)/',$ref,$m))continue;
   $col=0;foreach(str_split($m[1]) as $ch)$col=$col*26+(ord($ch)-64);$col--;
   $type=(string)$c['t'];$vv=$c->xpath('./*[local-name()="v"]');$v=count($vv)?(string)$vv[0]:'';
   if($type==='s')$v=isset($shared[(int)$v])?$shared[(int)$v]:'';
   elseif($type==='inlineStr'){$v='';foreach($c->xpath('.//*[local-name()="t"]') as $n)$v.=(string)$n;}
   $arr[$col]=$v;
  }if(count($arr)){ $max=max(array_keys($arr));$line=array();for($i=0;$i<=$max;$i++)$line[]=isset($arr[$i])?$arr[$i]:'';$rows[]=$line;}}
  $out[$name]=$rows;
 }
 $z->close();return $out;
}
function excel_date($v){if($v==='')return '';if(is_numeric($v)&&(float)$v>20000)return gmdate('Y-m-d',(int)(((float)$v-25569)*86400));$t=strtotime((string)$v);return $t?date('Y-m-d',$t):(string)$v;}
function rows_assoc($rows){if(!$rows)return array();$h=array_shift($rows);$o=array();foreach($rows as $r){$has=false;foreach($r as $v)if($v!==''){$has=true;break;}if(!$has)continue;$x=array();foreach($h as $i=>$k)$x[trim((string)$k)]=isset($r[$i])?$r[$i]:'';$o[]=$x;}return $o;}
