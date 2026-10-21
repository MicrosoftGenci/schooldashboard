/* V56: Eski karbon kutusunun yerini ve boyutunu degistirmeden hava kalitesi. */
(function(){
  function init(){
    var card=document.querySelector('.carbon-card');
    if(!card)return;
    var leaf=card.querySelector('.carbon-leaf');
    var value=document.getElementById('carbonValue');
    var info=document.getElementById('carbonPeriod');
    if(leaf)leaf.textContent='🌿';
    card.setAttribute('aria-label','Ankara Hava Kalitesi İndeksi, model tahmini');
    card.style.cursor='pointer';
    card.title='Open-Meteo/CAMS tahmini. Bakanlık ölçümü değildir. Resmi istasyon verileri için tıklayın.';
    card.setAttribute('role','link');card.setAttribute('tabindex','0');
    function open(){window.open('https://sim.csb.gov.tr/Services/AirQuality','_blank','noopener');}
    card.addEventListener('click',open);
    card.addEventListener('keydown',function(e){if(e.key==='Enter'||e.key===' '){e.preventDefault();open();}});
    function refresh(){
      var req=new XMLHttpRequest();
      req.open('GET','api/carbon.php?ts='+Date.now(),true);
      req.onreadystatechange=function(){
        if(req.readyState!==4)return;
        if(req.status!==200){if(value)value.textContent='—';if(info)info.textContent='Hava Kalitesi İndeksi · Veri yok';return;}
        try{
          var d=JSON.parse(req.responseText);
          if(d.available===true&&typeof d.value==='number'){
            if(value)value.textContent=d.value+' · '+d.label;
            if(info){
              var stamp='';
              if(d.period && /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(d.period))stamp=d.period.slice(11,16);
              info.textContent='HKİ · Tahmin'+(stamp?' · '+stamp:'');
              info.title='Open-Meteo/CAMS tahmini'+(d.period?' · Veri zamanı: '+d.period:'');
            }
            card.title='Kaynak: '+d.source+'; son veri: '+(d.period||'belirtilmedi')+'; Bakanlık ölçümü değildir. Resmi veri için tıklayın.';
          }else{if(value)value.textContent='—';if(info)info.textContent='Hava Kalitesi İndeksi · Veri yok';}
        }catch(e){if(value)value.textContent='—';if(info)info.textContent='Hava Kalitesi İndeksi · Hata';}
      };
      req.send();
    }
    refresh();setInterval(refresh,600000);
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
