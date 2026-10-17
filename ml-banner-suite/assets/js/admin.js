(function($){
'use strict';


function ensureToastWrap(){
  var wrap=document.querySelector('.mlbs-toast-wrap');
  if(!wrap){wrap=document.createElement('div');wrap.className='mlbs-toast-wrap';document.body.appendChild(wrap);} 
  return wrap;
}

function showToast(message,type,title){
  var wrap=ensureToastWrap();
  var toast=document.createElement('div');
  toast.className='mlbs-toast'+(type?' is-'+type:'');
  toast.innerHTML='<div class="mlbs-toast-body"><strong class="mlbs-toast-title" style="color:#fff!important;display:block;margin-bottom:4px;">'+(title||mlbsAdmin.pluginName)+'</strong><div class="mlbs-toast-message" style="color:#fff!important;">'+message+'</div></div><button type="button" aria-label="Fechar" style="color:#fff!important;">×</button>';
  wrap.appendChild(toast);
  var remove=function(){ if(toast&&toast.parentNode){toast.parentNode.removeChild(toast);} };
  toast.querySelector('button').addEventListener('click',remove);
  window.setTimeout(remove,5000);
}

function hydrateAdminRowMeta(){
  $('.post-type-mlbs_banner #the-list tr').each(function(){
    var $row=$(this); var $titleCell=$row.find('td.title');
    if(!$titleCell.length||$titleCell.data('mlbsHydrated')){return;}
    $titleCell.data('mlbsHydrated',1);
    var chips=[];
    var group=$.trim($row.find('td.column-mlbs_group').text());
    var mode=$.trim($row.find('td.column-mlbs_display_mode').text());
    var status=$.trim($row.find('td.column-mlbs_status').text());
    if(group&&group!=='—'){chips.push('<span>'+group+'</span>');}
    if(mode&&mode!=='—'){chips.push('<span>'+mode+'</span>');}
    if(status&&status!=='—'){chips.push('<span>'+status+'</span>');}
    if(chips.length){$titleCell.find('.row-title').after('<div class="mlbs-admin-row-meta">'+chips.join('')+'</div>');}
  });
}

function convertNoticesToToasts(){
  $('.toplevel_page_mlbs-dashboard .notice, .ml-banner-suite_page_mlbs-statistics .notice, .ml-banner-suite_page_mlbs-shortcodes .notice, .ml-banner-suite_page_mlbs-settings .notice, .ml-banner-suite_page_mlbs-license .notice').each(function(){
    var $notice=$(this); var text=$.trim($notice.text());
    if(!text||$notice.hasClass('mlbs-lock-notice')){return;}
    var type=$notice.hasClass('notice-error')?'error':'success';
    showToast(text,type);
    $notice.remove();
  });
}

function renderGifTiming(details){
  var box=$('#mlbs-gif-timing');
  var meta=$('#mlbs-gif-timing-meta');
  var button=$('#mlbs-apply-gif-duration');
  if(!box.length||!meta.length||!button.length){return;}

  var defaultLabel=box.data('default-label')||mlbsAdmin.gifDefault;
  var noGifLabel=box.data('no-gif-label')||mlbsAdmin.gifNotAnimated;

  box.removeClass('is-hidden');

  if(!details||!details.is_gif){
    meta.html('<span>'+defaultLabel+'</span>');
    button.addClass('is-hidden').attr('data-duration','0');
    return;
  }

  if(details.duration_ms&&details.duration_ms>0){
    var pieces=[];
    pieces.push('<span>'+mlbsAdmin.gifDurationLabel.replace('%d',details.duration_ms)+'</span>');
    if(details.frames&&details.frames>0){
      pieces.push('<span>'+mlbsAdmin.gifFramesLabel.replace('%d',details.frames)+'</span>');
    }
    if(details.frame_delay_ms&&details.frame_delay_ms>0){
      pieces.push('<span>'+mlbsAdmin.gifFrameDelayLabel.replace('%d',details.frame_delay_ms)+'</span>');
    }
    meta.html(pieces.join(''));
    button.removeClass('is-hidden').attr('data-duration',String(details.duration_ms));
  }else{
    meta.html('<span>'+noGifLabel+'</span>');
    button.addClass('is-hidden').attr('data-duration','0');
  }
}

function getCardGifDetails($card){
  if(!$card||!$card.length){return null;}
  return {
    is_gif:$card.data('is-gif')===1||$card.data('is-gif')==='1',
    duration_ms:parseInt($card.data('duration-ms')||0,10),
    frames:parseInt($card.data('frames')||0,10),
    frame_delay_ms:parseInt($card.data('frame-delay-ms')||0,10)
  };
}

function updateGifTimingFromCard($card){
  renderGifTiming(getCardGifDetails($card));
}

function fetchAttachmentDetails(attachmentId){
  $.post(mlbsAdmin.ajaxUrl,{action:'mlbs_get_attachment_details',nonce:mlbsAdmin.nonce,attachment_id:attachmentId})
    .done(function(response){
      if(response&&response.success&&response.data){
        renderGifTiming(response.data.gif_timing||null);
      }
    })
    .fail(function(){ renderGifTiming(null); });
}

function setSelected(attachmentId,imageUrl,details){
  $('#mlbs_image_id').val(attachmentId||'');
  $('#mlbs_image_url').val(imageUrl||'');
  var preview=$('#mlbs-media-preview');
  $('.mlbs-library-card').removeClass('is-selected');

  if(attachmentId){
    var $card=$('.mlbs-library-card[data-attachment-id="'+attachmentId+'"]');
    $card.addClass('is-selected');
    preview.removeClass('is-empty').html('<img src="'+imageUrl+'" alt="" />');
    $('.mlbs-remove-image').prop('disabled',false);

    if(details){
      renderGifTiming(details);
    }else if($card.length){
      updateGifTimingFromCard($card);
    }else{
      fetchAttachmentDetails(attachmentId);
    }
  }else{
    preview.addClass('is-empty').html('<div class="mlbs-media-empty">'+mlbsAdmin.emptyImage+'</div>');
    $('.mlbs-remove-image').prop('disabled',true);
    renderGifTiming(null);
  }
}

function refreshLoadMoreState(hasMore,page){
  var $wrap=$('#mlbs-wp-library');
  var $button=$('#mlbs-load-more-library');
  if(!$wrap.length||!$button.length){return;}
  $wrap.attr('data-has-more',hasMore?'1':'0');
  if(page){$wrap.attr('data-page',String(page));}
  $button.prop('disabled',!hasMore);
  if(hasMore){
    $button.text(mlbsAdmin.loadMore||'Carregar mais');
  }else{
    $button.text(mlbsAdmin.libraryEnd||'Você chegou ao fim da biblioteca.');
  }
}

function loadMoreLibrary(){
  var $wrap=$('#mlbs-wp-library');
  var $grid=$('#mlbs-library-grid');
  var $button=$('#mlbs-load-more-library');
  if(!$wrap.length||!$grid.length||!$button.length){return;}
  if($button.data('loading')){return;}
  var currentPage=parseInt($wrap.attr('data-page')||'1',10);
  var perPage=parseInt($wrap.attr('data-per-page')||'20',10);
  var hasMore=String($wrap.attr('data-has-more')||'0')==='1';
  if(!hasMore){refreshLoadMoreState(false,currentPage);return;}

  $button.data('loading',1).prop('disabled',true).text(mlbsAdmin.loadingMore||'Carregando...');

  $.post(mlbsAdmin.ajaxUrl,{
    action:'mlbs_load_wp_library',
    nonce:mlbsAdmin.nonce,
    page:currentPage+1,
    per_page:perPage,
    selected_id:$('#mlbs_image_id').val()||''
  }).done(function(response){
    if(response&&response.success&&response.data){
      if(response.data.html){
        $('#mlbs-library-empty').remove();
        $grid.append(response.data.html);
      }
      refreshLoadMoreState(!!response.data.has_more,response.data.page||currentPage+1);
    }else{
      $button.prop('disabled',false).text(mlbsAdmin.loadMore||'Carregar mais');
      showToast(mlbsAdmin.libraryLoadError||'Não foi possível carregar mais imagens da biblioteca.','error');
    }
  }).fail(function(){
    $button.prop('disabled',false).text(mlbsAdmin.loadMore||'Carregar mais');
    showToast(mlbsAdmin.libraryLoadError||'Não foi possível carregar mais imagens da biblioteca.','error');
  }).always(function(){
    $button.data('loading',0);
  });
}

function initGroupsScreen(){
  if(mlbsAdmin.currentScreenId!=='edit-mlbs_group'){return;}
  var $wrap=$('.taxonomy-mlbs_group .wrap');
  var $left=$('#col-left');
  var $right=$('#col-right');
  if(!$wrap.length||!$left.length||!$right.length){return;}
  var $form=$left.find('form');
  if(!$form.length){return;}
  var $panel=$('<div class="mlbs-group-add-panel" />').append($form);
  var $h1=$wrap.children('h1').first();
  var $button=$('<a href="#" class="page-title-action">Adicionar novo grupo</a>');
  $button.on('click',function(e){e.preventDefault();$panel.toggleClass('is-open');});
  if($h1.length){
    $h1.after($button);
    $button.after($panel);
  }else{
    $wrap.prepend($button).append($panel);
  }
}



function injectNativeScreenShell(){
  var id=mlbsAdmin.currentScreenId||'';
  var current='';
  var title='';
  var subtitle='';
  if(id==='edit-mlbs_banner' || id==='mlbs_banner'){current='banners'; title=mlbsAdmin.navLabels.banners||'Banners'; subtitle=(id==='mlbs_banner'?(mlbsAdmin.shellEditBannerSubtitle||'Edição rápida do banner, biblioteca própria e atalhos comerciais em uma tela única.'):(mlbsAdmin.shellBannerSubtitle||'Gestão operacional dos banners cadastrados, com ordem, tracking e atalhos rápidos.'));}
  if(id==='edit-mlbs_group'){current='groups'; title=mlbsAdmin.navLabels.groups||'Grupos'; subtitle=mlbsAdmin.shellGroupSubtitle||'Gestão dos grupos que governam exibição, layout e shortcodes do front.';}
  if(!current){return;}
  var wrap=document.querySelector('.wrap');
  if(!wrap||document.querySelector('.mlbs-native-shell')){return;}
  var heading=wrap.querySelector('h1.wp-heading-inline');
  if(!heading){return;}
  var tabs='';
  ['dashboard','banners','groups','statistics','shortcodes','settings','license'].forEach(function(key){
    if(!mlbsAdmin.navUrls[key]||!mlbsAdmin.navLabels[key]) return;
    tabs+='<a class="mlbs-quick-tab'+(key===current?' is-active':'')+'" href="'+mlbsAdmin.navUrls[key]+'">'+mlbsAdmin.navLabels[key]+'</a>';
  });
  var shell=document.createElement('div');
  shell.className='mlbs-native-shell';
  shell.innerHTML=''
    +'<div class="mlbs-shell-header">'
      +'<div><div class="mlbs-eyebrow">'+(mlbsAdmin.shellEyebrow||'PAINEL PROFISSIONAL')+'</div>'
      +'<h1>'+mlbsAdmin.pluginName+' <span class="mlbs-version">v'+mlbsAdmin.version+'</span></h1>'
      +'<p class="mlbs-subtitle">'+subtitle+'</p></div>'
      +'<div class="mlbs-badges">'
        +'<span class="mlbs-badge">GitHub Ready</span>'
        +'<span class="mlbs-badge">PT-BR / EN / ES</span>'
        +'<span class="mlbs-badge">'+(mlbsAdmin.baseStableLabel||'Base estável')+'</span>'
      +'</div>'
    +'</div>'
    +'<nav class="mlbs-quick-tabs" aria-label="ML Banner Suite">'+tabs+'</nav>';
  heading.parentNode.insertBefore(shell, heading);
}

function initQuickNav(){
  var id=mlbsAdmin.currentScreenId||'';
  var map={
    'edit-mlbs_banner':'banners',
    'mlbs_banner':'banners',
    'edit-mlbs_group':'groups',
    'toplevel_page_mlbs-dashboard':'dashboard',
    'ml-banner-suite_page_mlbs-statistics':'statistics',
    'ml-banner-suite_page_mlbs-shortcodes':'shortcodes',
    'ml-banner-suite_page_mlbs-settings':'settings',
    'ml-banner-suite_page_mlbs-license':'license'
  };
  var current=map[id];
  if(!current||document.querySelector('.mlbs-native-shell')){return;}
  var urls=mlbsAdmin.navUrls||{};
  var labels=mlbsAdmin.navLabels||{};
  if(!urls.dashboard){return;}
  var order=['dashboard','banners','groups','statistics','shortcodes','settings','license'];
  var html='<nav class="mlbs-quick-tabs" aria-label="ML Banner Suite">';
  order.forEach(function(key){
    if(!urls[key]||!labels[key]){return;}
    html+='<a class="mlbs-quick-tab'+(key===current?' is-active':'')+'" href="'+urls[key]+'">'+labels[key]+'</a>';
  });
  html+='</nav>';
  if(id==='edit-mlbs_banner' || id==='edit-mlbs_group' || id==='mlbs_banner'){
    var $wrap=$('.wrap').first();
    if($wrap.length && !$wrap.children('.mlbs-quick-tabs').length){
      var $heading=$wrap.children('h1').first();
      if($heading.length){ $heading.after(html); } else { $wrap.prepend(html); }
    }
  }
}

function initSortableList(){
  if(typeof $.fn.sortable==='undefined'){return;}
  if(mlbsAdmin.currentScreenId!==mlbsAdmin.bannerListScreen){return;}
  var $tbody=$('#the-list');
  if(!$tbody.length){return;}

  if(typeof $tbody.disableSelection==='function'){
    $tbody.disableSelection();
  }

  $tbody.sortable({
    items:'> tr',
    axis:'y',
    cursor:'move',
    handle:'td.column-mlbs_image, td.column-title, .row-title',
    cancel:'a, button, input, textarea, select, option, label, code',
    distance:5,
    tolerance:'pointer',
    placeholder:'mlbs-sortable-placeholder',
    forcePlaceholderSize:true,
    helper:function(e,ui){
      ui.children().each(function(){ $(this).width($(this).outerWidth()); });
      return ui;
    },
    update:function(){
      var ids=[];
      $tbody.find('> tr').each(function(){
        var id=(this.id||'').replace('post-','');
        if(id){ids.push(id);}
      });
      $.post(mlbsAdmin.ajaxUrl,{action:'mlbs_save_banner_order',nonce:mlbsAdmin.nonce,ids:ids})
        .done(function(){ showToast(mlbsAdmin.orderSaved,'success'); })
        .fail(function(){ showToast(mlbsAdmin.orderSaveError,'error'); });
    }
  });
}

$(function(){
  var mediaFrame=null;

  $(document).on('click','.mlbs-library-card',function(e){
    e.preventDefault();
    var $card=$(this);
    setSelected($card.data('attachment-id'),$card.data('image-url'));
  });

  $(document).on('click','.mlbs-remove-image',function(e){
    e.preventDefault();
    setSelected('','');
  });

  $(document).on('click','.mlbs-library-delete',function(e){
    e.preventDefault();
    e.stopPropagation();
    var attachmentId=$(this).data('attachment-id');
    if(!attachmentId){return;}
    if(!window.confirm(mlbsAdmin.deleteImageConfirm||'Deseja excluir esta imagem da biblioteca de banners? Essa ação remove o arquivo do servidor.')){return;}
    var $card=$(this).closest('.mlbs-library-card');
    $.post(mlbsAdmin.ajaxUrl,{action:'mlbs_delete_banner_image',nonce:mlbsAdmin.nonce,attachment_id:attachmentId})
      .done(function(response){
        if(response&&response.success){
          if(String($('#mlbs_image_id').val())===String(attachmentId)){
            setSelected('','');
          }
          $card.remove();
          showToast(mlbsAdmin.deleteImage||'Excluir imagem','success');
        }else{
          showToast(mlbsAdmin.deleteImageError||'Não foi possível excluir a imagem.','error');
        }
      })
      .fail(function(xhr){
        var message=(xhr.responseJSON&&xhr.responseJSON.data&&xhr.responseJSON.data.message)?xhr.responseJSON.data.message:(mlbsAdmin.deleteImageError||'Não foi possível excluir a imagem.');
        showToast(message,'error');
      });
  });

  $(document).on('click','#mlbs-load-more-library',function(e){
    e.preventDefault();
    loadMoreLibrary();
  });

  $(document).on('click','.mlbs-select-media',function(e){
    e.preventDefault();
    if(mediaFrame){ mediaFrame.open(); return; }
    mediaFrame=wp.media({title:mlbsAdmin.chooseImage,button:{text:mlbsAdmin.useImage},multiple:false,library:{type:'image'}});
    mediaFrame.on('select',function(){
      var attachment=mediaFrame.state().get('selection').first().toJSON();
      var url=(attachment.sizes&&attachment.sizes.large)?attachment.sizes.large.url:attachment.url;
      setSelected(attachment.id,url);
    });
    mediaFrame.open();
  });

  $('#mlbs-banner-upload').on('change',function(){
    var file=this.files&&this.files[0]?this.files[0]:null;
    if(!file){return;}
    var data=new FormData();
    data.append('action','mlbs_upload_banner_image');
    data.append('nonce',mlbsAdmin.nonce);
    data.append('banner_file',file);
    var button=$(this).closest('.mlbs-upload-button');
    var original=button.find('span').text();
    button.addClass('disabled');
    button.find('span').text(mlbsAdmin.uploading);

    $.ajax({url:mlbsAdmin.ajaxUrl,type:'POST',data:data,processData:false,contentType:false})
      .done(function(response){
        if(response&&response.success&&response.data){
          $('#mlbs-library-grid').prepend(response.data.card_html);
          setSelected(response.data.attachment_id,response.data.url,response.data.gif_timing||null);
        }
      })
      .fail(function(xhr){
        var message=(xhr.responseJSON&&xhr.responseJSON.data&&xhr.responseJSON.data.message)?xhr.responseJSON.data.message:mlbsAdmin.uploadError;
        showToast(message,'error');
      })
      .always(function(){
        button.removeClass('disabled');
        button.find('span').text(original);
        $('#mlbs-banner-upload').val('');
      });
  });

  $(document).on('click','#mlbs-apply-gif-duration',function(e){
    e.preventDefault();
    e.stopPropagation();
    var duration=parseInt($(this).attr('data-duration')||0,10);
    if(duration>0){
      $('#mlbs_duration').val(String(duration)).trigger('input').trigger('change');
      showToast('Duração do GIF aplicada no campo de exibição. Agora salve o banner para gravar.','success');
    }
    return false;
  });

  initQuickNav();
  initSortableList();
  initGroupsScreen();
  hydrateAdminRowMeta();
  convertNoticesToToasts();

  if($('#mlbs_image_id').val()){
    var currentId=$('#mlbs_image_id').val();
    var $card=$('.mlbs-library-card[data-attachment-id="'+currentId+'"]');
    if($card.length){
      updateGifTimingFromCard($card);
    }else{
      fetchAttachmentDetails(currentId);
    }
  }else{
    renderGifTiming(null);
  }
});
})(jQuery);
