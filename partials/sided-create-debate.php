<?php 

include 'includes/sided-authenticate-apikey.php';

if($status_aat !== 'Valid Token'){
  echo '<script>
          window.location = "'.get_site_url().'/wp-admin/admin.php?page=settings";
        </script>';
  exit;
}

if(isset($_POST['thesis'])){
 //echo '<pre>'; print_r($_POST); echo '</pre>'; die();
  $url = SIDED_API_URL.'/debate/create';
  $args = array( 'timeout' => 50,
            'method' => 'POST',
            'headers' => array( 'Content-Type' => 'application/json',
                                'x-source-type' => 'wp-plugin',
                               'x-private-access-token'=> esc_js($sided_private_access_token) ) ,
            'body' => json_encode($_POST, JSON_UNESCAPED_SLASHES),
             );
  $response = wp_remote_post( $url, $args);
  if( !is_wp_error( $response ) ) {
    $response_arr = json_decode($response['body']);
    if($response_arr->status == 'success'){
      echo esc_html($response_arr->message);
      $next_page = sanitize_text_field($_POST['type']) == 'draft' ? 'edit-draft' : 'edit-debate';
      echo '<script>window.location.href="admin.php?page=dashboard&debate='.esc_js($response_arr->data->id).'&action='.esc_js($next_page).'"</script>';
      die;
    } else {
      echo '<h3 style="color: red !important;">There is an error is submitting poll data. Please try again later.</h3>';
    }
  } else {
    echo $response->get_error_message();
  }
}

include 'includes/sided-common-header.php'; // including header after redirection

/* fetch debate authors start */
$debate_authors = '';
$url_fda = SIDED_API_URL.'/user/list?type=DEBATE_AUTHORS&clientId='.$selected_network;
$header_fda = array( 'timeout' => 50,
          'headers' => array( 'x-source-type' => 'wp-plugin',
                             'x-private-access-token'=> $sided_private_access_token ) 
           );
$response_fda = wp_remote_get( $url_fda, $header_fda);
if( !is_wp_error( $response_fda ) ) {
  $response_fda_arr = json_decode($response_fda['body']);
  $debate_authors = $response_fda_arr->data;
  //echo '<pre>'; print_r($debate_authors); echo '</pre>';
} else {
  echo $response_fda->get_error_message();
}

/* fetch debate authors end */

/* fetch Current network's cats start */
$debate_cats = '';
$url_fcnc = SIDED_API_URL.'/categories?type=START_DEBATE&clientId='.$selected_network;
$header_fcnc = array( 'timeout' => 50,
          'headers' => array( 'x-source-type' => 'wp-plugin',
                             'x-private-access-token'=> $sided_private_access_token ) 
           );
$response_fcnc = wp_remote_get( $url_fcnc, $header_fcnc);
if( !is_wp_error( $response_fcnc ) ) {
  $response_fcnc_arr = json_decode($response_fcnc['body']);
  $debate_cats = $response_fcnc_arr->data;
} else {
  echo $response_fcnc->get_error_message();
}
/* fetch Current network's cats end */

/*fetch curret user details start*/
$current_user = '';
$url_fcu = SIDED_API_URL.'/user/'.$response_aat_arr->data->userId;
$header_fcu = array( 'timeout' => 50,
          'headers' => array( 'x-source-type' => 'wp-plugin',
                             'x-private-access-token'=> $sided_private_access_token ) 
           );
$response_fcu = wp_remote_get( $url_fcu, $header_fcu);
if( !is_wp_error( $response_fcu ) ) {
  $response_fcu_arr = json_decode($response_fcu['body']);
  $current_user = $response_fcu_arr->status == 'success' ? $response_fcu_arr->data : '';
} else {
  echo $response_fcu->get_error_message();
}
/*fetch curret user details end*/

/*fetch brand account details start*/
$brand_account = '';
$url_fba = SIDED_API_URL.'/admin/brandAccount/'.$selected_network;
$header_fba = array( 'timeout' => 50,
          'headers' => array( 'x-source-type' => 'wp-plugin',
                             'x-private-access-token'=> $sided_private_access_token ) 
           );
$response_fba = wp_remote_get( $url_fba, $header_fba);
if( !is_wp_error( $response_fba ) ) {
  $response_fba_arr = json_decode($response_fba['body']);
  $brand_account = $response_fba_arr->status == 'success' ? $response_fba_arr->data : ''; 
  if($brand_account) {
    $current_user = $brand_account;
  }
} else {
  echo $response_fba->get_error_message();
}
/*fetch brand account details end*/

?>
  
<script type="text/javascript">
(function ($) {  // Wait for the DOM to be ready
  //var $ = jQuery;
  $(function() {
    // Initialize form validation on the registration form.
    // It has the name attribute "registration"

function validImage(val) {
  var regex = new RegExp("(.*?)\.(jpg|jpeg|png)$");
  return regex.test(val);
}

$(document).on('change', '.debate-image-upload', function(){
  var input = $(this);
  if(!this.files || this.files.length < 1) {
    return ;
  }
  if(this.files[0].size/1024/1024 > 20){
      alert("The max image file size is 20MB.")
      $(this).val('');
  } else if(!validImage($(this).val().toLowerCase())) {
    alert("Image is invalid. Please upload only PNG or JPG image.");
    $(this).val('');
  } else {
    const [file] = this.files
    window.deb_image = $('input[name="backgroundImageTemp"]')[0].files;
    if (file) {
      debImagePreview.src = URL.createObjectURL(file);
      $('#debImagePreview , .field-backgroundImage .close').fadeIn();
      $('.img-field-placeholder').hide();
    }
  }
});


$('body').on('click', '.field-backgroundImage .close', function(){
  debImagePreview.src = '';
    $('#debImagePreview , .field-backgroundImage .close').hide();
    $('.img-field-placeholder').show();
    $('#debImage').val('');
});

    $("#createDebate").validate({
      // Specify validation rules
      rules: {
        // The key name on the left side is the name attribute
        // of an input field. Validation rules are defined
        // on the right side
        thesis: {
          required: true,
          maxlength: 140,
          normalizer: function(value) {
            return $.trim(value);
          }
        },
        "sides[0][text]":{
          required: true,
          maxlength: 140,
          normalizer: function(value) {
            return $.trim(value);
          }
        },
        "sides[1][text]":{
            required: true,
            maxlength: 140,
            normalizer: function(value) {
              return $.trim(value);
            }
          },
        /*categoryId:"required"*/
      },
      // Specify validation error messages
      messages: {
        thesis: {
          required: "The Statement or Question field is required.",
          maxlength: "Please do not enter more than 140 characters."
        },
        "sides[0][text]":{
          required: "The side field is required.",
          maxlength: "The maximum number of characters is 140."
        },
        "sides[1][text]":{
          required: "The side field is required.",
          maxlength: "The maximum number of characters is 140."
        },
        /*categoryId: "The category field is required."*/
      },
      // Make sure the form is submitted to the destination defined
      // in the "action" attribute of the form when valid
      submitHandler: function(form) {
        $('.buttons *').prop('disabled', true);
        $('.buttons *').css('opacity', '0.3');
        //var deb_image = $('input[name="backgroundImageTemp"]')[0].files;
        var deb_img_code = $('input[name="backgroundImage"]').val();
        if(deb_image.length > 0 && deb_img_code == ''){
          uploadDebateImage(deb_image);
        } else {
          form.submit();
          //submitForm(form);
        }
      }
    });

function uploadDebateImage(deb_image){
  var fd = new FormData();
   fd.append('backgroundImage',deb_image[0]);
   $.ajax({
      url: '<?php echo esc_js(SIDED_API_URL); ?>/debate/imageUpload?type=DEBATES&clientId=<?php echo esc_js($selected_network); ?>',
      type: 'post',
      beforeSend: function(request) {
        request.setRequestHeader("x-private-access-token", "<?php echo esc_js($sided_private_access_token); ?>");
      },
      data: fd,
      contentType: false,
      processData: false,
      success: function(response){
         if(response != 0){
            $('input[name="backgroundImage"]').val(response.data);
            $('#createDebate')[0].submit(); 
            //submitForm($('#createDebate')[0]);
         }else{
            alert('file not uploaded');
         }
      },
   });
}

    endedAt('');

$('#startedAt').val(moment().add(0, 'hours'));
$('#endedAt').val(app.sn_default_debate_duration != '' ? moment().add(app.sn_default_debate_duration, 'days') : '');

    $('input[name="startedAtField"]').daterangepicker({
       timePicker: true,
      singleDatePicker: true,
      showDropdowns: true,
      minDate: new Date(),
      startDate: moment().add(0, 'hours'),
      locale: {
        format: 'MM/DD/YY @ hh:mmA'
      }
    }, function(start, end, label) {
      endedAt(start);
      $('#startedAt').val(start);
    });

    function endedAt(minDate){
      $('input[name="endedAtField"]').daterangepicker({
        timePicker: true,
        singleDatePicker: true,
        showDropdowns: true,
        minDate: minDate != '' ? minDate : new Date(),
        startDate: app.sn_default_debate_duration != '' ? moment().add(app.sn_default_debate_duration, 'days') : '',
        //maxDate: app.sn_max_debate_duration != '' ? moment().add(app.sn_max_debate_duration, 'days') : '',
        locale: {
          format: 'MM/DD/YY @ hh:mmA'
        }
      }, function(start, end, label) {
        $('#endedAt').val(start);
      });
    }

    $('#addSide').click(function(){
      if($('.side').length > 5){ alert('Maximum 6 Sides are allowed'); return false; } 
      $nwSide = $('.side').first().clone();
      $nwSide.find('label.error').remove();
      $nwSide.find('input').val('');
      $nwSide.find('.row-2').html('');
      $nwSide.find('.add-link-tag-wrapper').hide();
      $nwSide.find('.side-link').removeAttr('disabled');
      $('.side-container').append($nwSide);
      if($('.side').length == 6){ $('#addSide').hide(); }
      if($('.side').length > 2){ $('.delete-side').show(); }
      sideReIndex();
    });

    $('body').on('click' , '.delete-side' , function(){
      $(this).closest('.side').remove();
      if($('.side').length < 3){ $('.delete-side').hide(); }
      if($('.side').length < 6){ $('#addSide').show(); }
      sideReIndex();
    });

    function sideReIndex(){
      $(".side-container").find(".side").each(function(index){
        $(this).attr('data-sideindex', index);
        $(this).find("span.lightColorSpan").html('Side '+(index+1));
        $(this).find(".row-1 input[name^='sides']").attr('name','sides['+(index)+'][text]');
      });
    }

    $('#noEndDate').click(function(){
      $('#endedAt').parents('.datePickerWrapper').toggleClass('disabled');
    });

$('body').on('click', '#generate-ai-polls',function(){
  var SPC_keyword = $('input[name="SPC_keyword"]');
  if(SPC_keyword.val() === '' || (SPC_keyword.val() !== "" && !(/^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-zA-Z0-9]+([\-\.]{1}[a-zA-Z0-9]+)*\.[a-zA-Z]{2,5}(:[0-9]{1,5})?(\/.*)?$/i.test(SPC_keyword.val())))){
        alert("Please enter valid page URL");
        SPC_keyword.focus();
  } else { 
    $(this).parents('.smartPollWrapper').addClass('loadingPollPreview');
    $.ajax({
        url: ajaxurl,
        type: 'post',
        data: {
          action: 'wpa_sided_generate_smart_poll',
          SPC_keyword_val: SPC_keyword.val(),
        },
        success: function (data) {
          $('.loadingPollPreview').removeClass('loadingPollPreview');
          console.log(data)
          window.dataArray = $.parseJSON(data);
          $.each(dataArray , function(key, value){
            var sidesHTML = '';
            $.each(value['sides'] , function(skey, svalue){
              sidesHTML = sidesHTML+'<div class=""><label>'+svalue+'</label></div>';
            });
            $('#generate-ai-polls').hide();
            $('#reset-ai-polls').show();
            $('.smartPollContent').append('<div class="debatePreviewInner"><div class="debatePreviewHeader"><div class="d-flex"><div class="author"><a target="_blank" rel="noreferrer" class="small-dp user-color-green" href="/<?php echo $current_user->username; ?>" tabindex="0"><img data-src="" src="<?php echo esc_attr($current_user->avatarObject->thumb->location); ?>" alt="Superadmin" class="sidedLazyLoad img-circle avatar" style="border-color: rgb(219, 219, 219);"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle-check" class="svg-inline--fa fa-circle-check procheckmark" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="color: rgb(31, 170, 115);"><path fill="currentColor" d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"></path></svg></a><div class="copy-wrap"><a target="_blank" rel="noreferrer" class="authorname" href="/<?php echo $current_user->username; ?>" tabindex="0"><h6><?php echo $current_user->name; ?></h6></a><a rel="noreferrer" target="_blank" href="/<?php echo $current_user->username; ?>" class="authorhandle"><span><?php echo $current_user->username; ?></span></a></div></div><button type="button" class="selectPollBtn btn btn-primary" data-index="'+key+'">Select Poll</button></div></div><div class="debatePreviewTitle mt-3"><h6>'+value['thesis']+'</h6></div><div class="debatePreviewSides mt-2">'+sidesHTML+'</div></div>').show();
          });
        },
        error: function (data) {
        }
      })
  }
})

$('body').on('click', '.selectPollBtn',function(){
  var pollIndex = $(this).data('index');
  console.log(dataArray[pollIndex]);
  $('#thesis').val(dataArray[pollIndex]['thesis']); 
  $('.side:gt(1)').remove();
  
  for(var i = 0 ; i< dataArray[pollIndex]['sides'].length ; i++){
    if($('.side').length < dataArray[pollIndex]['sides'].length && i > 1){
      $nwSide = $('.side').first().clone();
      $nwSide.find('label.error').remove();
      $('.side-container').append($nwSide);
    }
    sideReIndex();
    $('input[name="sides['+i+'][text]"]').val(dataArray[pollIndex]['sides'][i]);
  }
  if(dataArray[pollIndex]['sides'].length > 2){
    $('.delete-side').show();
  }
  if(dataArray[pollIndex]['sides'].length == 6){
    $('#addSide').hide();
  } else{
    $('#addSide').show();
  }
  
  $('.selectPollBtn').text('Select Poll').addClass('btn-primary').removeClass('selected disabled btn-outline-primary');
  $(this).text('Selected').removeClass('btn-primary').addClass('selected disabled btn-outline-primary');
})

$('#reset-ai-polls').click(function(){
  $('.smartPollContent').html('');
  $('#generate-ai-polls').show();
  $('#reset-ai-polls, .smartPollContent').hide();
  $('input[name="SPC_keyword"]').val('');
  $('input[name="SPC_keyword"]').focus();
})
$('body').on('click' , 'button.side-link , button.side-tag' , function(){
//$('button.side-link , button.side-tag').click(function(){
  var clickedBtn = $(this).data('type');
  $(this).parents('.side').find('.add-link-tag-wrapper').slideDown('500');
  $('.add-link-tag-wrapper').find('input[type="text"]').attr('name',clickedBtn+'-field').val('');
  if(clickedBtn == 'tag'){
    $(this).parents('.side').find('.add-link-tag-wrapper svg.fa-link').hide();
    $(this).parents('.side').find('.add-link-tag-wrapper svg.fa-tag').show();
  } else{
    $(this).parents('.side').find('.add-link-tag-wrapper svg.fa-link').show(); 
    $(this).parents('.side').find('.add-link-tag-wrapper svg.fa-tag').hide();
  }
})

$('body').on('focus', 'input[name="tag-field"] , input[name="link-field"]' , function(){
 $(this).parents('.add-link-tag-wrapper').find('svg').hide();
}).on('blur', 'input[name="tag-field"] , input[name="link-field"]' , function(){
  if($(this).val() == ''){
    if($(this).attr('name') == 'tag-field') {
      $(this).parents('.add-link-tag-wrapper').find('svg.fa-tag').show();
    } else{
      $(this).parents('.add-link-tag-wrapper').find('svg.fa-link').show();
    }
  }
})

$('body').on('focus', '.closeLinkTagWrapper' , function(){
  $(this).parents('.add-link-tag-wrapper').slideUp('500');
})

$('body').on('click', '.submitLinkTag', function(){
var clickedBtn = $(this).parents('.add-link-tag-wrapper').find('input');
if(clickedBtn.attr('name') == 'link-field'){
  if(clickedBtn.val().trim() == "" || !(/^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-zA-Z0-9]+([\-\.]{1}[a-zA-Z0-9]+)*\.[a-zA-Z]{2,5}(:[0-9]{1,5})?(\/.*)?$/i.test(clickedBtn.val().trim()))){
    alert('Please enter valid URL.');
    return false;
  } else{
    var sideIndex = $(this).parents('.side').data('sideindex');
    console.log(sideIndex);
    var linkHtml = '<span class="sideLink"><input type="hidden" name="sides['+sideIndex+'][link]" value="'+clickedBtn.val().trim()+'" readonly><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="link" class="svg-inline--fa fa-link procheckmark" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M579.8 267.7c56.5-56.5 56.5-148 0-204.5c-50-50-128.8-56.5-186.3-15.4l-1.6 1.1c-14.4 10.3-17.7 30.3-7.4 44.6s30.3 17.7 44.6 7.4l1.6-1.1c32.1-22.9 76-19.3 103.8 8.6c31.5 31.5 31.5 82.5 0 114L422.3 334.8c-31.5 31.5-82.5 31.5-114 0c-27.9-27.9-31.5-71.8-8.6-103.8l1.1-1.6c10.3-14.4 6.9-34.4-7.4-44.6s-34.4-6.9-44.6 7.4l-1.1 1.6C206.5 251.2 213 330 263 380c56.5 56.5 148 56.5 204.5 0L579.8 267.7zM60.2 244.3c-56.5 56.5-56.5 148 0 204.5c50 50 128.8 56.5 186.3 15.4l1.6-1.1c14.4-10.3 17.7-30.3 7.4-44.6s-30.3-17.7-44.6-7.4l-1.6 1.1c-32.1 22.9-76 19.3-103.8-8.6C74 372 74 321 105.5 289.5L217.7 177.2c31.5-31.5 82.5-31.5 114 0c27.9 27.9 31.5 71.8 8.6 103.9l-1.1 1.6c-10.3 14.4-6.9 34.4 7.4 44.6s34.4 6.9 44.6-7.4l1.1-1.6C433.5 260.8 427 182 377 132c-56.5-56.5-148-56.5-204.5 0L60.2 244.3z"></path></svg><a class="d-inline" rel="noreferrer" href="'+clickedBtn.val().trim()+'" target="_blank">'+clickedBtn.val().trim()+'</a><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle-xmark" class="svg-inline--fa fa-circle-xmark procheckmark flip" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"></path></svg></span>';
    $(this).parents('.add-link-tag-wrapper').prev('.row-2').prepend(linkHtml);
    $(this).parents('.add-link-tag-wrapper').hide();
    $(this).parents('.side').find('button.side-link').attr('disabled','disabled');
  }
} else{
  if(clickedBtn.val().trim() == ""){
    alert('Blank tag is not allowed.');
    return false
  } else{
  var sideIndex = $(this).parents('.side').data('sideindex');
    console.log(sideIndex);
    var tagIndex = $(this).parents('.add-link-tag-wrapper').prev('.row-2').find('.sideTags').length;
    console.log(tagIndex);
    var tagHtml = '<span class="sideTags"><input type="hidden" name="sides['+sideIndex+'][tags]['+tagIndex+'][text]" value="'+clickedBtn.val().trim()+'" readonly><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="tag" class="svg-inline--fa fa-tag procheckmark flip" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M0 80V229.5c0 17 6.7 33.3 18.7 45.3l176 176c25 25 65.5 25 90.5 0L418.7 317.3c25-25 25-65.5 0-90.5l-176-176c-12-12-28.3-18.7-45.3-18.7H48C21.5 32 0 53.5 0 80zm112 32a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"></path></svg>'+clickedBtn.val().trim()+'<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle-xmark" class="svg-inline--fa fa-circle-xmark procheckmark flip" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"></path></svg></span>';
    $(this).parents('.add-link-tag-wrapper').prev('.row-2').append(tagHtml);
    $(this).parents('.add-link-tag-wrapper').find('input[name="tag-field"]').val('').focus();
  }
}
})

$('body').on('click', '.sideLink .fa-circle-xmark', function(){
  console.log($(this).parents('.row-2').prev('.row-1').find('button.side-link'));
  $(this).parents('.row-2').prev('.row-1').find('button.side-link').removeAttr('disabled');
  $(this).parents('span.sideLink').remove();
});
$('body').on('click', '.sideTags .fa-circle-xmark', function(){
  console.log($(this).parents('.row-2').find('.sideTags').eq());

  $(this).parents('span.sideTags').remove();
});

});
}(jQuery));

</script>
<style type="text/css">
  .side:nth-child(2) input{
    border-color: #43a5f5 !important;
  }
  .side:nth-child(3) input{
    border-color: #d91414 !important;
  }
  .side:nth-child(4) input{
    border-color: #f4900c !important;
  }
  .side:nth-child(5) input{
    border-color: #ab47bd !important;
  }
  .side:nth-child(6) input{
    border-color: #2aa974 !important;
  }
  .side:nth-child(7) input{
    border-color: #4f87b1 !important;
  }

</style>
<div class="content-wrapper1">
  <div class="content">
   <form id="createDebate" class="startDebate common-form row" action="" method="POST" enctype="multipart/form-data">
    <div class="pageHeader inline-content justify-content-between">
      <h3>New Poll</h3>
      <div class="buttons mb-4 d-flex justify-content-end">
            <button type="submit" class="mt-0 me-0 btn btn-secondary" onclick="jQuery('#typeField').val('draft');">Save Draft</button>
            <button type="submit" class="mt-0 me-0 btn btn-primary" onclick="jQuery('#typeField').val('create');">Publish</button>
      </div>
    </div>
    <input type="hidden" name="clientId" value="<?php echo esc_attr($selected_network); ?>">
    <input id="typeField" type="hidden" name="type" value="create">
      <div class="col-md-6">
          <div class="smartPollWrapper">
            <label class="form-label">AI Poll Generator</label>
            <p class="lightColorSpan">Paste a url below and our system will generate 4 polls to choose from.</p>
            <div class="inline-content pt-0 mb-2 form-group">
              <input data-required="true" name="SPC_keyword" placeholder="Type or copy paste page url " type="text" class="form-control" value="">
              <button type="button" id="generate-ai-polls" class="customBtn ms-2 btn btn-outline-primary">Generate Polls</button>
              <button style="display: none;" type="button" id="reset-ai-polls" class="customBtn ms-2 btn btn-outline-primary">Reset</button>
            </div>
            <div class="preloader-dot-loading mt-2">
              <p class="m-0 textLeft">Generating polls ... please keep this page open</p>
              <div class="cssload-loading mt-2">
                <i></i><i></i><i></i><i></i>
              </div>
            </div>


            <div class="smartPollSection p-0">
              <div class="smartPollContent pb-3 position-relative" style="display: none;">
                
              </div>
            </div>
          </div>
         <div class="form-group pt-0"><label class="form-label" for="thesis">Poll Title</label><input data-required="true" name="thesis" placeholder="Type statement or questions here..." type="text" id="thesis" class="form-control" value=""></div>
         <div class="form-group side-container">
            <div class="inline-content justify-content-between">
              <label class="form-label">Your Sides</label>
              <span id="addSide" role="button" class="customAnchor">+ Add Side</span>
            </div>
            <div class="side" data-sideindex='0'>
               <div class="message position-relative">
                  <!-- <span role="button" class="delete-side delete-button" style="display: none;">
                      <svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M11 1.10786L9.89214 0L5.5 4.39214L1.10786 0L0 1.10786L4.39214 5.5L0 9.89214L1.10786 11L5.5 6.60786L9.89214 11L11 9.89214L6.60786 5.5L11 1.10786Z" fill="#9B9B9B"/>
                      </svg>
                  </span> -->
                  <div class="row-1 d-flex align-items-center">
                    <div class="position-relative w-100 ">
                      <input data-required="true" data-index="1" placeholder="Type your side here" name="sides[0][text]" type="text" class="removeBtnOn form-control" value="">
                      <div class="side-inline-items d-flex align-items-center">
                        <span class="lightColorSpan">Side 1</span>
                        <button type="button" class="delete-side delete-button ms-2 btn btn-secondary-light btn-sm" style="display: none;">Remove</button></div>
                    </div>
                    <button type="button" data-type="link" class="side-link ms-2 btn btn-secondary-light btn-sm"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="link" class="svg-inline--fa fa-link procheckmark" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M579.8 267.7c56.5-56.5 56.5-148 0-204.5c-50-50-128.8-56.5-186.3-15.4l-1.6 1.1c-14.4 10.3-17.7 30.3-7.4 44.6s30.3 17.7 44.6 7.4l1.6-1.1c32.1-22.9 76-19.3 103.8 8.6c31.5 31.5 31.5 82.5 0 114L422.3 334.8c-31.5 31.5-82.5 31.5-114 0c-27.9-27.9-31.5-71.8-8.6-103.8l1.1-1.6c10.3-14.4 6.9-34.4-7.4-44.6s-34.4-6.9-44.6 7.4l-1.1 1.6C206.5 251.2 213 330 263 380c56.5 56.5 148 56.5 204.5 0L579.8 267.7zM60.2 244.3c-56.5 56.5-56.5 148 0 204.5c50 50 128.8 56.5 186.3 15.4l1.6-1.1c14.4-10.3 17.7-30.3 7.4-44.6s-30.3-17.7-44.6-7.4l-1.6 1.1c-32.1 22.9-76 19.3-103.8-8.6C74 372 74 321 105.5 289.5L217.7 177.2c31.5-31.5 82.5-31.5 114 0c27.9 27.9 31.5 71.8 8.6 103.9l-1.1 1.6c-10.3 14.4-6.9 34.4 7.4 44.6s34.4 6.9 44.6-7.4l1.1-1.6C433.5 260.8 427 182 377 132c-56.5-56.5-148-56.5-204.5 0L60.2 244.3z"></path></svg></button>
                    <button type="button" data-type="tag" class="side-tag ms-2 btn btn-secondary-light btn-sm"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="tag" class="svg-inline--fa fa-tag procheckmark flip" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M0 80V229.5c0 17 6.7 33.3 18.7 45.3l176 176c25 25 65.5 25 90.5 0L418.7 317.3c25-25 25-65.5 0-90.5l-176-176c-12-12-28.3-18.7-45.3-18.7H48C21.5 32 0 53.5 0 80zm112 32a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"></path></svg>+</button>
                  </div>

                  <div class="row-2 mt-2">
                  </div>

                  <div class="add-link-tag-wrapper row-3 mt-2" test="0" style="display: none;">
                    <div class="position-relative">
                      <input name="" type="text" class="form-control" value="">
                      <div class="sideElementsButtons">
                        <button type="button" class="me-1 btn btn-secondary-light btn-sm closeLinkTagWrapper">Cancel</button>
                        <button data-index="1" type="button" class="btn btn-primary btn-sm submitLinkTag">Add</button>
                      </div>
                      <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="link" class="svg-inline--fa fa-link procheckmark" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M579.8 267.7c56.5-56.5 56.5-148 0-204.5c-50-50-128.8-56.5-186.3-15.4l-1.6 1.1c-14.4 10.3-17.7 30.3-7.4 44.6s30.3 17.7 44.6 7.4l1.6-1.1c32.1-22.9 76-19.3 103.8 8.6c31.5 31.5 31.5 82.5 0 114L422.3 334.8c-31.5 31.5-82.5 31.5-114 0c-27.9-27.9-31.5-71.8-8.6-103.8l1.1-1.6c10.3-14.4 6.9-34.4-7.4-44.6s-34.4-6.9-44.6 7.4l-1.1 1.6C206.5 251.2 213 330 263 380c56.5 56.5 148 56.5 204.5 0L579.8 267.7zM60.2 244.3c-56.5 56.5-56.5 148 0 204.5c50 50 128.8 56.5 186.3 15.4l1.6-1.1c14.4-10.3 17.7-30.3 7.4-44.6s-30.3-17.7-44.6-7.4l-1.6 1.1c-32.1 22.9-76 19.3-103.8-8.6C74 372 74 321 105.5 289.5L217.7 177.2c31.5-31.5 82.5-31.5 114 0c27.9 27.9 31.5 71.8 8.6 103.9l-1.1 1.6c-10.3 14.4-6.9 34.4 7.4 44.6s34.4 6.9 44.6-7.4l1.1-1.6C433.5 260.8 427 182 377 132c-56.5-56.5-148-56.5-204.5 0L60.2 244.3z"></path></svg>
                      <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="tag" class="svg-inline--fa fa-tag flip procheckmark" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M0 80V229.5c0 17 6.7 33.3 18.7 45.3l176 176c25 25 65.5 25 90.5 0L418.7 317.3c25-25 25-65.5 0-90.5l-176-176c-12-12-28.3-18.7-45.3-18.7H48C21.5 32 0 53.5 0 80zm112 32a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"></path></svg>
                    </div>
                  </div>
               </div>
            </div>
            <div class="side" data-sideindex='1'>
               <div class="message position-relative">
                  <div class="row-1 d-flex align-items-center">
                    <div class="position-relative w-100 ">
                      <input data-required="true" data-index="1" placeholder="Type your side here" name="sides[1][text]" type="text" class="removeBtnOn form-control" value="">
                      <div class="side-inline-items d-flex align-items-center">
                        <span class="lightColorSpan">Side 2</span>
                        <button type="button" class="delete-side delete-button ms-2 btn btn-secondary-light btn-sm" style="display: none;">Remove</button></div>
                    </div>
                  
                    <button type="button" data-type="link" class="side-link ms-2 btn btn-secondary-light btn-sm"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="link" class="svg-inline--fa fa-link procheckmark" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M579.8 267.7c56.5-56.5 56.5-148 0-204.5c-50-50-128.8-56.5-186.3-15.4l-1.6 1.1c-14.4 10.3-17.7 30.3-7.4 44.6s30.3 17.7 44.6 7.4l1.6-1.1c32.1-22.9 76-19.3 103.8 8.6c31.5 31.5 31.5 82.5 0 114L422.3 334.8c-31.5 31.5-82.5 31.5-114 0c-27.9-27.9-31.5-71.8-8.6-103.8l1.1-1.6c10.3-14.4 6.9-34.4-7.4-44.6s-34.4-6.9-44.6 7.4l-1.1 1.6C206.5 251.2 213 330 263 380c56.5 56.5 148 56.5 204.5 0L579.8 267.7zM60.2 244.3c-56.5 56.5-56.5 148 0 204.5c50 50 128.8 56.5 186.3 15.4l1.6-1.1c14.4-10.3 17.7-30.3 7.4-44.6s-30.3-17.7-44.6-7.4l-1.6 1.1c-32.1 22.9-76 19.3-103.8-8.6C74 372 74 321 105.5 289.5L217.7 177.2c31.5-31.5 82.5-31.5 114 0c27.9 27.9 31.5 71.8 8.6 103.9l-1.1 1.6c-10.3 14.4-6.9 34.4 7.4 44.6s34.4 6.9 44.6-7.4l1.1-1.6C433.5 260.8 427 182 377 132c-56.5-56.5-148-56.5-204.5 0L60.2 244.3z"></path></svg></button>
                    <button type="button" data-type="tag" class="side-tag ms-2 btn btn-secondary-light btn-sm"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="tag" class="svg-inline--fa fa-tag procheckmark flip" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M0 80V229.5c0 17 6.7 33.3 18.7 45.3l176 176c25 25 65.5 25 90.5 0L418.7 317.3c25-25 25-65.5 0-90.5l-176-176c-12-12-28.3-18.7-45.3-18.7H48C21.5 32 0 53.5 0 80zm112 32a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"></path></svg>+</button>
                  </div>

                  <div class="row-2 mt-2">
                  </div>

                  <div class="add-link-tag-wrapper row-3 mt-2" test="0" style="display: none;">
                    <div class="position-relative">
                      <input name="" type="text" class="form-control" value="">
                      <div class="sideElementsButtons">
                        <button type="button" class="me-1 btn btn-secondary-light btn-sm closeLinkTagWrapper">Cancel</button>
                        <button data-index="1" type="button" class="btn btn-primary btn-sm submitLinkTag">Add</button>
                      </div>
                      <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="link" class="svg-inline--fa fa-link procheckmark" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M579.8 267.7c56.5-56.5 56.5-148 0-204.5c-50-50-128.8-56.5-186.3-15.4l-1.6 1.1c-14.4 10.3-17.7 30.3-7.4 44.6s30.3 17.7 44.6 7.4l1.6-1.1c32.1-22.9 76-19.3 103.8 8.6c31.5 31.5 31.5 82.5 0 114L422.3 334.8c-31.5 31.5-82.5 31.5-114 0c-27.9-27.9-31.5-71.8-8.6-103.8l1.1-1.6c10.3-14.4 6.9-34.4-7.4-44.6s-34.4-6.9-44.6 7.4l-1.1 1.6C206.5 251.2 213 330 263 380c56.5 56.5 148 56.5 204.5 0L579.8 267.7zM60.2 244.3c-56.5 56.5-56.5 148 0 204.5c50 50 128.8 56.5 186.3 15.4l1.6-1.1c14.4-10.3 17.7-30.3 7.4-44.6s-30.3-17.7-44.6-7.4l-1.6 1.1c-32.1 22.9-76 19.3-103.8-8.6C74 372 74 321 105.5 289.5L217.7 177.2c31.5-31.5 82.5-31.5 114 0c27.9 27.9 31.5 71.8 8.6 103.9l-1.1 1.6c-10.3 14.4-6.9 34.4 7.4 44.6s34.4 6.9 44.6-7.4l1.1-1.6C433.5 260.8 427 182 377 132c-56.5-56.5-148-56.5-204.5 0L60.2 244.3z"></path></svg>
                      <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="tag" class="svg-inline--fa fa-tag flip procheckmark" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M0 80V229.5c0 17 6.7 33.3 18.7 45.3l176 176c25 25 65.5 25 90.5 0L418.7 317.3c25-25 25-65.5 0-90.5l-176-176c-12-12-28.3-18.7-45.3-18.7H48C21.5 32 0 53.5 0 80zm112 32a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"></path></svg>
                    </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="form-group">
            <label class="form-label">Poll Topic</label>
            <select data-required="true" name="categoryId" class="categoryId">
               <option value="" data-id="">Select a category</option>
               <?php
                foreach($debate_cats as $debate_cat){
                  echo '<option value="'.esc_attr($debate_cat->id).'" data-id="'.esc_attr($debate_cat->id).'">'.esc_html($debate_cat->text).'</option>';
                }
               ?>
            </select>
            <textarea class=" mt-3 form-control" name="background" placeholder="Share any information that might help people choose a side"></textarea>
         </div>
         <div class="form-group">
            <div class="inline-content justify-content-between"><label class="form-label">Poll Image</label><span class="fieldInfo">Recommended 300 x 150 px</span></div>
            <div class="field-backgroundImage img-field-wrap">
              <span class="close" style="display: none;">                 
                <svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M11 1.10786L9.89214 0L5.5 4.39214L1.10786 0L0 1.10786L4.39214 5.5L0 9.89214L1.10786 11L5.5 6.60786L9.89214 11L11 9.89214L6.60786 5.5L11 1.10786Z" fill="#9B9B9B"/>
                </svg>
              </span>
              <img id="debImagePreview" src="" alt="" style="display: none;" />
               <div class="img-field-placeholder">
                  <svg class="upload-label" width="24" height="21" viewBox="0 0 24 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M9.03323 0H15.1551C15.9355 0 16.6318 0.473616 16.9028 1.19554L17.5711 2.94286H21.6835C22.9219 2.94286 23.9266 3.93147 23.9266 5.15V18.3929C23.9266 19.6114 22.9219 20.6 21.6835 20.6H2.24312C1.00473 20.6 0 19.6114 0 18.3929V5.15C0 3.93147 1.00473 2.94286 2.24312 2.94286H6.3555L6.93498 1.43004C7.2621 0.570179 8.09859 0 9.03323 0ZM15.5048 1.7139C15.4487 1.57136 15.3085 1.47479 15.1543 1.47479H9.03244C8.71934 1.47479 8.43895 1.66792 8.33147 1.95301L7.39216 4.41765H2.24234C1.8311 4.41765 1.49463 4.74872 1.49463 5.15336V18.3962C1.49463 18.8009 1.8311 19.1319 2.24234 19.1319H21.6827C22.0939 19.1319 22.4304 18.8009 22.4304 18.3962V5.15336C22.4304 4.74872 22.0939 4.41765 21.6827 4.41765H16.5375L15.5048 1.7139ZM11.9633 17.2855C8.86963 17.2855 6.35547 14.8117 6.35547 11.7677C6.35547 8.72367 8.86963 6.24983 11.9633 6.24983C15.0569 6.24983 17.5711 8.72367 17.5711 11.7677C17.5711 14.8117 15.0569 17.2855 11.9633 17.2855ZM7.85059 11.7711C7.85059 9.54092 9.69649 7.72463 11.963 7.72463C14.2295 7.72463 16.0754 9.54092 16.0754 11.7711C16.0754 14.0012 14.2295 15.8175 11.963 15.8175C9.69649 15.8175 7.85059 14.0012 7.85059 11.7711Z" fill="#43A5F5"/>
                  </svg>
              </div>
               <input id="debImage" data-index="0" data-imgtype="DEBATES" name="backgroundImageTemp" data-accept="jpg|jpeg|png" data-accept_msg="Image is invalid. Please upload only PNG or JPG image." type="file" class="debate-image-upload form-control-file">
               <input type="hidden" value="" name="backgroundImage">
            </div>
         </div>
      </div>
      <div class="col-md-6 second-col">
         <label class="pt-0 form-label">Published on</label>
         <div class="inline-content">
            <div class="me-3 form-group">
               <label class="form-label" for="startedAt">Start Date</label>
               <div class="datePickerWrapper">
                  <input type="hidden" class="" id="startedAt" name="startedAt" value="">
                  <input type="text" name="startedAtField" class="form-control datepickerField"><i class="fa fa-calendar"></i>
                </div>
            </div>
            <div class="form-group">
               <label class="form-label" for="endedAt">End Date</label>
               <div class="datePickerWrapper">
                <input type="hidden" class="" id="endedAt" name="endedAt" value="">
                <input type="text" name="endedAtField" class="form-control datepickerField"><i class="fa fa-calendar"></i>
              </div>
              <div class="noEndDate">
                <label for="noEndDate" class="mb-0 mt-2 form-check-label">
                  <input name="noEndDate" type="checkbox" id="noEndDate" class="m-0 me-2 form-check-input" value="1">No end date
                </label>
              </div>
            </div>
         </div>

         <div class="form-group">
          <select name="userId">
            <option value="">Select Poll Author</option>
            <?php
              $current_user_id = $current_user->id;
             foreach($debate_authors as $debate_author){

              if(array_search('BRANDACCOUNT', array_column($debate_author->roles, 'name'))){
                $current_user_id = $debate_author->id;
              }
              
              $selected_author = $current_user_id == $debate_author->id ? 'selected' : '';
              
              echo '<option '.esc_attr($selected_author).' value="'.esc_attr($debate_author->id).'">'.esc_html($debate_author->name).'</option>';
            } ?>
          </select>
        </div>

         <div class="form-group">
            <div class="inline-content justify-content-between"><label class="form-label" for="endedAt">Poll Preview</label><span class="fieldInfo">300 px wide</span></div>
         </div>
         <?php
         if($brand_account){
            $current_user = $brand_account;
         }
         ?>
         <div class="debatePreviewSection">
            <div class="debatePreviewInner">
               <div class="debatePreviewHeader">
                  <div class="author">
                     <a target="_blank" rel="noreferrer" class="small-dp user-color-green" href="/<?php echo $current_user->username; ?>" tabindex="0">
                        <img data-src="" src="<?php echo esc_attr($current_user->avatarObject->thumb->location); ?>" alt="Super Admin" class="sidedLazyLoad img-circle avatar" style="border-color: rgb(219, 219, 219);">
                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="check-circle" class="svg-inline--fa fa-check-circle fa-w-16 procheckmark" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="color: rgb(31, 170, 115);">
                           <path fill="currentColor" d="M504 256c0 136.967-111.033 248-248 248S8 392.967 8 256 119.033 8 256 8s248 111.033 248 248zM227.314 387.314l184-184c6.248-6.248 6.248-16.379 0-22.627l-22.627-22.627c-6.248-6.249-16.379-6.249-22.628 0L216 308.118l-70.059-70.059c-6.248-6.248-16.379-6.248-22.628 0l-22.627 22.627c-6.248 6.248-6.248 16.379 0 22.627l104 104c6.249 6.249 16.379 6.249 22.628.001z"></path>
                        </svg>
                     </a>
                     <div class="copy-wrap">
                        <a target="_blank" rel="noreferrer" class="authorname" href="/<?php echo $current_user->username; ?>" tabindex="0">
                           <h6><?php echo esc_html($current_user->name); ?></h6>
                        </a>
                        <a rel="noreferrer" class="authorhandle"><span class="handler">Posted <?php echo date('M d, Y'); ?></span></a>
                     </div>
                  </div>
               </div>
               <div class="debatePreviewTitle mt-3">
                  <p></p>
                  <p></p>
                  <p></p>
               </div>
               <span class="lightColorSpan mt-2 d-block">Poll ends in 4 day(s)  • Vote below</span>
               <div class="debatePreviewSides mt-2">
                  <div class=""><label>Side 1</label></div>
                  <div class=""><label>Side 2</label></div>
               </div>
               <span class="lightColorSpan mt-2 d-block">0 Votes • 0 Comments </span>
               <div class="debatePreviewAdunit mt-2">
                  <p>[ad unit 300x250]</p>
               </div>
               <div class="debatePreviewFooter mt-2"><a class="customAnchor">Share</a><img alt="Logo" src="https://cdn.sided.co/prod/sided/wl/sided/images/c7ac0980-0852-11eb-b673-1dad531e23be.png" class="logo"></div>
            </div>
         </div>
      </div>
   </form>
  </div>
</div>