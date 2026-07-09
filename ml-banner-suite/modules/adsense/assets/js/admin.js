(function($){
'use strict';

function showToast(message){
  var $toast=$('<div class="mlbs-adsense-toast" role="status" />').text(message);
  $('body').append($toast);
  window.setTimeout(function(){$toast.fadeOut(180,function(){$toast.remove();});},2600);
}

function slugify(value){
  return String(value||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9_-]+/g,'-').replace(/^-+|-+$/g,'').substring(0,80);
}

function shortcodeFor($unit){
  var id=slugify($unit.find('.mlbs-adsense-unit-id').val());
  return '[mlbs_adsense unit="'+id+'"]';
}

function refreshUnit($unit){
  var name=$.trim($unit.find('.mlbs-adsense-unit-name').val())||mlbsAdsenseAdmin.unitName;
  var shortcode=shortcodeFor($unit);
  $unit.find('.mlbs-adsense-unit-title').text(name);
  $unit.find('.mlbs-adsense-shortcode-preview').text(shortcode);
  $unit.find('.mlbs-adsense-copy-shortcode').attr('data-shortcode',shortcode);
}

function refreshEmptyState(){
  var hasUnits=$('.mlbs-adsense-units .mlbs-adsense-unit').length>0;
  $('.mlbs-adsense-empty-state').toggleClass('is-hidden',hasUnits);
}

function appendAdsenseTab(){
  $('.mlbs-tabs,.mlbs-quick-tabs').each(function(){
    var $nav=$(this);
    if($nav.find('a[href*="page=mlbs-adsense"]').length){return;}
    var className=$nav.hasClass('mlbs-quick-tabs')?'mlbs-quick-tab':'mlbs-tab';
    var $link=$('<a />',{href:mlbsAdsenseAdmin.pageUrl,text:mlbsAdsenseAdmin.label,'class':className+' mlbs-adsense-tab-added'});
    var $settings=$nav.find('a[href*="page=mlbs-settings"]').first();
    if($settings.length){$link.insertBefore($settings);}else{$nav.append($link);}
  });
}

function toggleParagraphField(){
  var show=$('.mlbs-adsense-content-position').val()==='after_paragraph';
  $('.mlbs-adsense-paragraph-field').toggle(show);
}

$(function(){
  appendAdsenseTab();
  toggleParagraphField();

  $(document).on('change','.mlbs-adsense-content-position',toggleParagraphField);

  $(document).on('click','.mlbs-adsense-add-unit',function(){
    var $container=$('.mlbs-adsense-units');
    var index=parseInt($container.attr('data-next-index')||'0',10);
    var timestamp=Date.now().toString(36);
    var template=$('#mlbs-adsense-unit-template').html().replace(/__INDEX__/g,String(index)).replace(/__TIMESTAMP__/g,timestamp);
    $container.append(template);
    $container.attr('data-next-index',String(index+1));
    var $unit=$container.children('.mlbs-adsense-unit').last();
    refreshUnit($unit);
    refreshEmptyState();
    $unit.get(0).scrollIntoView({behavior:'smooth',block:'center'});
  });

  $(document).on('click','.mlbs-adsense-remove-unit',function(){
    if(!window.confirm(mlbsAdsenseAdmin.removeConfirm)){return;}
    $(this).closest('.mlbs-adsense-unit').remove();
    refreshEmptyState();
  });

  $(document).on('input','.mlbs-adsense-unit-name',function(){refreshUnit($(this).closest('.mlbs-adsense-unit'));});
  $(document).on('input change','.mlbs-adsense-unit-id',function(){
    var clean=slugify($(this).val());
    $(this).val(clean);
    refreshUnit($(this).closest('.mlbs-adsense-unit'));
  });

  $(document).on('click','.mlbs-adsense-copy-shortcode',function(){
    var text=$(this).attr('data-shortcode')||'';
    if(navigator.clipboard&&window.isSecureContext){
      navigator.clipboard.writeText(text).then(function(){showToast(mlbsAdsenseAdmin.copySuccess);}).catch(function(){showToast(mlbsAdsenseAdmin.copyError);});
      return;
    }
    var $temp=$('<textarea />').val(text).css({position:'fixed',opacity:0}).appendTo('body').select();
    try{document.execCommand('copy');showToast(mlbsAdsenseAdmin.copySuccess);}catch(e){showToast(mlbsAdsenseAdmin.copyError);}
    $temp.remove();
  });

  $('.mlbs-adsense-unit').each(function(){refreshUnit($(this));});
  refreshEmptyState();
});
})(jQuery);
