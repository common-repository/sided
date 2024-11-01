<?php 

include 'includes/sided-authenticate-apikey.php';

if($status_aat !== 'Valid Token'){
  echo '<script>
          window.location = "'.get_site_url().'/wp-admin/admin.php?page=settings";
        </script>';
  exit;
}

include 'includes/sided-common-header.php';

/* Submit deploy poll data start */
if(isset($_POST['embedPlacementIds'])){
  $url = SIDED_API_URL.'/admin/embedPlacement/deployDebateToAllPages?clientId='.$_POST['currDebateClientId'];
  $args = array( 'timeout' => 50,
            'method' => 'POST',
            'headers' => array( 'Content-Type' => 'application/json',
                                'x-source-type' => 'wp-plugin',
                               'x-private-access-token'=> $sided_private_access_token ) ,
            'body' => wp_json_encode($_POST),
            'data_format' => 'body',
             );
  $response = wp_remote_post( $url, $args);
  if( !is_wp_error( $response ) ) {
    $response_arr = json_decode($response['body']);
    if($response_arr->status == 'success'){
      echo '<h3 style="color: green !important;">Poll deployed to All Pages successfully.</h3>';
    } else {
      echo '<h3 style="color: red !important;">There is an error in deploying debate. Please try again later.</h3>';
    }
  } else {
    echo $response->get_error_message();
  }
}
/* Submit deploy poll data end */

/* Submit remove poll deploy data start */
if(isset($_POST['remove_poll'])){
  $url = SIDED_API_URL.'/admin/embedPlacement/removeDebateFromAllPages?clientId='.$_POST['currDebateClientId'];
  $args = array( 'timeout' => 50,
            'method' => 'POST',
            'headers' => array( 'Content-Type' => 'application/json',
                                'x-source-type' => 'wp-plugin',
                               'x-private-access-token'=> $sided_private_access_token ) ,
            'body' => wp_json_encode($_POST),
            'data_format' => 'body',
             );
  $response = wp_remote_post( $url, $args);
  if( !is_wp_error( $response ) ) {
    $response_arr = json_decode($response['body']);
    if($response_arr->status == 'success'){
      //echo '<h3 style="color: green !important;">'.esc_html($response_arr->message).'</h3>';
      echo '<h3 style="color: green !important;">Poll Removed from All Pages successfully.</h3>';
    } else {
      echo '<h3 style="color: red !important;">There is an error in deploying debate. Please try again later.</h3>';
    }
  } else {
    echo $response->get_error_message();
  }
}
/* Submit remove poll deploy data End */

/* Submit poll data start */
if(isset($_POST['thesis'])){
  $url = SIDED_API_URL.'/debate/update/'.sanitize_text_field($_POST['debateId']);
  $args = array( 'timeout' => 50,
            'method' => 'PUT',
            'headers' => array( 'Content-Type' => 'application/json',
                                'x-source-type' => 'wp-plugin',
                               'x-private-access-token'=> $sided_private_access_token ) ,
            'body' => wp_json_encode($_POST),
            'data_format' => 'body',
             );
  $response = wp_remote_post( $url, $args);
  if( !is_wp_error( $response ) ) {
    $response_arr = json_decode($response['body']);
    if($response_arr->status == 'success'){
      echo '<h3 style="color: green !important;">'.esc_html($response_arr->message).'</h3>';
    } else {
      echo '<h3 style="color: red !important;">There is an error is submitting poll data. Please try again later.</h3>';
    }
  } else {
    echo $response->get_error_message();
  }
  //echo '<pre>'; print_r($response_arr->message); echo '</pre>';
  //echo '<hr><pre>'; print_r($_POST); echo '</pre>';
}
/* Submit poll data end */

/* Get Current Debate Data Start */
$curr_debate = '';
if($_GET['debate']){
  $url = SIDED_API_URL.'/debate/'.sanitize_text_field($_GET['debate']).'?deviceId=123123';
  $args = array( 'timeout' => 50,
            'headers' => array( 'x-source-type' => 'wp-plugin',
                               'x-private-access-token'=> $sided_private_access_token ) 
             );
  $response = wp_remote_get( $url , $args);

  if( !is_wp_error( $response ) ) {
    $response_arr = json_decode($response['body']);
    if($response_arr->status == 'success'){
      $curr_debate = $response_arr->data;
      if($curr_debate->clientId != $selected_network){
        echo '<script>
          window.location = "'.get_site_url().'/wp-admin/admin.php?page=dashboard";
        </script>';
      }
    } else {
      echo esc_html($response_arr->message[0]->error);
    }

    $isNoEndDate = $curr_debate->endedAt == '' ? true : false;
    $endDate = $curr_debate->endedAt == null ? date('Y-m-d') : $curr_debate->endedAt;
  } else {
    echo $response->get_error_message();
  }
}
/* Get Current Debate Data End */

/* fetch currentDomainId start */
$url_fcdi = SIDED_API_URL.'/admin/embedSource/getDomains?clientId='.$curr_debate->clientId;
$header_fcdi = array( 'timeout' => 50,
          'headers' => array( 'x-source-type' => 'wp-plugin',
                             'x-private-access-token'=> $sided_private_access_token ) 
           );
$response_fcdi = wp_remote_get( $url_fcdi, $header_fcdi);
if( !is_wp_error( $response_fcdi ) ) {
  $response_fcdi_arr = json_decode($response_fcdi['body']);
  $embed_domains = $response_fcdi_arr->data;

  $curr_domain = $_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
  $key = array_search($curr_domain, array_column($embed_domains,'domain'));
  if($key !== false){
    $currentDomainId = $embed_domains[$key]->id;
  } else {
    $currentDomainId = null;
  }

if($key !== false){
  $embed_domain_data = $embed_domains[$key]->embedPlacementDebates;
  $sel_debate_data_key = array_search($curr_debate->id, array_column($embed_domain_data,'debateId'));
  if($sel_debate_data_key !== false){
    $sel_debate_data = $embed_domain_data[$sel_debate_data_key];
    foreach($embed_domain_data as $key=>$value){
      if($value->debateId !== $curr_debate->id){
        unset($embed_domain_data[$key]); 
      }
    }
    $embed_domain_data_filtered = array_values($embed_domain_data);
    //echo '<pre>'; print_r($embed_domain_data_filtered); echo '</pre>';
    //echo '<pre>'; print_r($embed_domains[$sel_embed_domains_key]); echo '</pre>';
  }
}
} else {
  echo $response_fcdi->get_error_message();
}
/* fetch currentDomainId end */

/* fetch debate authors start */
$url_fda = SIDED_API_URL.'/user/list?type=DEBATE_AUTHORS&clientId='.$curr_debate->clientId;
$header_fda = array( 'timeout' => 50,
          'headers' => array( 'x-source-type' => 'wp-plugin',
                             'x-private-access-token'=> $sided_private_access_token ) 
           );
$response_fda = wp_remote_get( $url_fda, $header_fda);
if( !is_wp_error( $response_fda ) ) {
  $response_fda_arr = json_decode($response_fda['body']);
  $debate_authors = $response_fda_arr->data;
} else {
  echo $response_fda->get_error_message();
}
/* fetch debate authors end */

/* fetch Current network's cats start */
$url_fcnc = SIDED_API_URL.'/categories?type=START_DEBATE&clientId=&clientId='.$curr_debate->clientId;
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

/* Fetch Embed placements Data Start */
$url_ep = SIDED_API_URL.'/admin/embedPlacement/getEmbedPlacements?clientId='.$selected_network;
$header_ep = array( 'timeout' => 50,
          'headers' => array( 'x-source-type' => 'wp-plugin',
                             'x-private-access-token'=> $sided_private_access_token ) 
           );
$response_ep = wp_remote_get( $url_ep, $header_ep);
if( !is_wp_error( $response_ep ) ) {
  $response_ep_arr = json_decode($response_ep['body']);  
  $embed_placements = $response_ep_arr->data;
  //echo '<pre>'; print_r($embed_placements); echo '</pre>';
} else {
  echo $response_ep->get_error_message();
}
/* Fetch Embed placements Data End */

/* Fetch Categories List Start */
$url_fcl = SIDED_API_URL.'/categories?clientId='.$selected_network;
$header_fcl = array( 'timeout' => 50,
          'headers' => array( 'x-source-type' => 'wp-plugin',
                             'x-private-access-token'=> $sided_private_access_token ) 
           );
$response_fcl = wp_remote_get( $url_fcl, $header_fcl);
if( !is_wp_error( $response_fcl ) ) {
  $response_fcl_arr = json_decode($response_fcl['body']);  
  $categories_list = $response_fcl_arr->data;
  //echo '<pre>'; print_r($categories_list); echo '</pre>';
} else {
  echo $response_fcl->get_error_message();
}
/* Fetch Embed placements Data End */
?>
  
<script type="text/javascript">
(function ($) {
    // Initialize form validation on the registration form.
    // It has the name attribute "registration"

  function validImage(val) {
    var regex = new RegExp("(.*?)\.(jpg|jpeg|png)$");
    return regex.test(val);
  }

  $('body').on('change', '.debate-image-upload', function(){
    var input = $(this);
    if(!this.files) {
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
        $('input[name="backgroundImage"]').val('');
  });

  function uploadDebateImage(deb_image){
    var fd = new FormData();
     fd.append('backgroundImage',deb_image[0]);
     $.ajax({
        url: '<?php echo esc_url(SIDED_API_URL); ?>/debate/imageUpload?type=DEBATES&clientId=<?php echo esc_js($curr_debate->clientId); ?>',
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

  $(document).ready(function (){

    $("#createDebate").validate({
    // Specify validation rules
    rules: {
      thesis: {
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
      /*categoryId: "The category field is required."*/
    },
    // Make sure the form is submitted to the destination defined
    // in the "action" attribute of the form when valid
    submitHandler: function(form) {
      var deb_image = $('input[name="backgroundImageTemp"]')[0].files;
      var deb_img_code = $('input[name="backgroundImage"]').val();
      if(deb_image.length > 0 && deb_img_code == ''){
        uploadDebateImage(deb_image);
      } else {
        form.submit();
        //submitForm(form);
      }
    }
  });


    endedAt('');
    $('#startedAt').val(moment('<?php echo esc_js($curr_debate->startedAt) ?>')['_d']);
    $('#endedAt').val(moment('<?php echo esc_js($endDate); ?>')['_d']);
    $('#publishedAt').text(moment('<?php echo esc_js($curr_debate->startedAt) ?>').format('MM/DD/YY @ hh:mmA'));

    $('input[name="startedAtField"]').daterangepicker({
      singleDatePicker: true,
      minDate: moment('<?php echo esc_js($curr_debate->startedAt) ?>')['_d'],
      startDate: moment('<?php echo esc_js($curr_debate->startedAt) ?>')['_d'],
      maxDate: moment('<?php echo esc_js($curr_debate->startedAt) ?>')['_d'],
      timePicker: true,
      locale: {
        format: 'MM/DD/YY @ hh:mmA'
      },
    }, function(start, end, label) {
      endedAt(start);
      $('#startedAt').val(start);
    });

    function endedAt(){
      var endedAtMinDate = moment('<?php echo esc_js($curr_debate->startedAt) ?>')['_d'];
      $('input[name="endedAtField"]').daterangepicker({
        
        singleDatePicker: true,
        minDate: moment('<?php echo esc_js($curr_debate->startedAt) ?>')['_d'],
        startDate: moment('<?php echo esc_js($endDate); ?>')['_d'],
        //maxDate: app.sn_max_debate_duration != '' ? moment('<?php echo esc_js($curr_debate->startedAt) ?>').add(app.sn_max_debate_duration, 'days') : '',
        timePicker: true,
        locale: {
          format: 'MM/DD/YY @ hh:mmA'
        },
      }, function(start, end, label) {
        $('#endedAt').val(start);
      });
    }

    $('#noEndDate').click(function(){
      $('#endedAt').parents('.datePickerWrapper').toggleClass('disabled');
    });

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
    return false
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
  var reqEl = $(this).parents('.row-2');
  var sideIndex = $(this).parents('.side').data('sideindex');
  $(this).parents('span.sideTags').remove();
  reqEl.find('.sideTags').each(function(index){
    $(this).find('input').attr('name','sides['+sideIndex+'][tags]['+index+'][text]');
  });
});

  })
}(jQuery));
</script>
<div class="content-wrapper1 <?php echo $curr_debate->isClosed == '1' ? 'closed' : ''; ?>">
  <div class="content">
   <form id="createDebate" class="startDebate common-form row" action="" method="POST" enctype="multipart/form-data">
    <?php
      $side_count = 0;
      foreach($curr_debate->sides as $side){
        echo '<input type="hidden" name="sides['.$side_count.'][id]" value="'.$side->id.'"><input type="hidden" name="sides['.$side_count.'][text]" value="'.$side->text.'">';
        $side_count++;
      }
    ?>

    <div class="pageHeader inline-content justify-content-between">
      <h3>Edit Poll</h3>
      <?php if($curr_debate->isClosed != '1') { ?>
        <div class="buttons mb-4 d-flex justify-content-end">
          <a class="mt-0 me-0 btn btn-secondary" href="admin.php?page=dashboard">Cancel</a>
          <button type="submit" class="mt-0 me-0 btn btn-primary" onclick="jQuery('#typeField').val('create');">Save</button>
        </div>
      <?php } ?>
    </div>
    <input type="hidden" name="debateId" value="<?php echo esc_attr($curr_debate->id); ?>">
    <input type="hidden" name="clientId" value="<?php echo esc_attr($curr_debate->clientId); ?>">
    <input id="typeField" type="hidden" name="type" value="create">
      <div class="col-md-6">
         <div class="form-group pt-0"><label class="form-label" for="thesis">Poll Title</label><input data-required="true" name="thesis" placeholder="Type statement or questions here..." type="text" id="thesis" class="form-control" value="<?php echo esc_attr($curr_debate->thesis); ?>"></div>
         <div class="form-group side-container">
            <div class="inline-content justify-content-between">
              <label class="form-label">Your Sides</label>
            </div>
            <?php 
            $side_count = 0;
            foreach($curr_debate->sides as $side){
              $votes_data = $curr_debate->isVoted ? '
                        <div class="percentageBar" style="width: '.((int)$side->votes * 100 / (int)$curr_debate->votes).'%; background: '.$side->sideColor.'26;"></div>
                        <span class="votesPercentage">'.round(((int)$side->votes * 100 / (int)$curr_debate->votes), 2).'%</span>' : '';
              $disabled = $side->link && $side->link !='' ? 'disabled' : '';
              echo '<div class="side" data-sideindex="'.$side_count.'">
                      <div class="message position-relative">
                        <div class="row-1 d-flex align-items-center">
                          <div class="position-relative w-100 ">
                            '.esc_html($votes_data).'
                            <p class="form-control mb-0" style="border-color: '.esc_attr($side->sideColor).'; background: '.esc_attr($side->sideColor).'26;">'.esc_html($side->text).'</p>
                          </div>
                          <button type="button" data-type="link" class="side-link ms-2 btn btn-secondary-light btn-sm" '.$disabled.'><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="link" class="svg-inline--fa fa-link procheckmark" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M579.8 267.7c56.5-56.5 56.5-148 0-204.5c-50-50-128.8-56.5-186.3-15.4l-1.6 1.1c-14.4 10.3-17.7 30.3-7.4 44.6s30.3 17.7 44.6 7.4l1.6-1.1c32.1-22.9 76-19.3 103.8 8.6c31.5 31.5 31.5 82.5 0 114L422.3 334.8c-31.5 31.5-82.5 31.5-114 0c-27.9-27.9-31.5-71.8-8.6-103.8l1.1-1.6c10.3-14.4 6.9-34.4-7.4-44.6s-34.4-6.9-44.6 7.4l-1.1 1.6C206.5 251.2 213 330 263 380c56.5 56.5 148 56.5 204.5 0L579.8 267.7zM60.2 244.3c-56.5 56.5-56.5 148 0 204.5c50 50 128.8 56.5 186.3 15.4l1.6-1.1c14.4-10.3 17.7-30.3 7.4-44.6s-30.3-17.7-44.6-7.4l-1.6 1.1c-32.1 22.9-76 19.3-103.8-8.6C74 372 74 321 105.5 289.5L217.7 177.2c31.5-31.5 82.5-31.5 114 0c27.9 27.9 31.5 71.8 8.6 103.9l-1.1 1.6c-10.3 14.4-6.9 34.4 7.4 44.6s34.4 6.9 44.6-7.4l1.1-1.6C433.5 260.8 427 182 377 132c-56.5-56.5-148-56.5-204.5 0L60.2 244.3z"></path></svg></button>
                          <button type="button" data-type="tag" class="side-tag ms-2 btn btn-secondary-light btn-sm"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="tag" class="svg-inline--fa fa-tag procheckmark flip" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M0 80V229.5c0 17 6.7 33.3 18.7 45.3l176 176c25 25 65.5 25 90.5 0L418.7 317.3c25-25 25-65.5 0-90.5l-176-176c-12-12-28.3-18.7-45.3-18.7H48C21.5 32 0 53.5 0 80zm112 32a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"></path></svg>+</button>
                        </div>

                        <div class="row-2 mt-2">';

                        if($side->link && $side->link !=''){
                          echo '<span class="sideLink"><input type="hidden" name="sides['.$side_count.'][link]" value="'.$side->link.'" readonly><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="link" class="svg-inline--fa fa-link procheckmark" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M579.8 267.7c56.5-56.5 56.5-148 0-204.5c-50-50-128.8-56.5-186.3-15.4l-1.6 1.1c-14.4 10.3-17.7 30.3-7.4 44.6s30.3 17.7 44.6 7.4l1.6-1.1c32.1-22.9 76-19.3 103.8 8.6c31.5 31.5 31.5 82.5 0 114L422.3 334.8c-31.5 31.5-82.5 31.5-114 0c-27.9-27.9-31.5-71.8-8.6-103.8l1.1-1.6c10.3-14.4 6.9-34.4-7.4-44.6s-34.4-6.9-44.6 7.4l-1.1 1.6C206.5 251.2 213 330 263 380c56.5 56.5 148 56.5 204.5 0L579.8 267.7zM60.2 244.3c-56.5 56.5-56.5 148 0 204.5c50 50 128.8 56.5 186.3 15.4l1.6-1.1c14.4-10.3 17.7-30.3 7.4-44.6s-30.3-17.7-44.6-7.4l-1.6 1.1c-32.1 22.9-76 19.3-103.8-8.6C74 372 74 321 105.5 289.5L217.7 177.2c31.5-31.5 82.5-31.5 114 0c27.9 27.9 31.5 71.8 8.6 103.9l-1.1 1.6c-10.3 14.4-6.9 34.4 7.4 44.6s34.4 6.9 44.6-7.4l1.1-1.6C433.5 260.8 427 182 377 132c-56.5-56.5-148-56.5-204.5 0L60.2 244.3z"></path></svg><a class="d-inline" rel="noreferrer" href="'.$side->link.'" target="_blank">'.$side->link.'</a><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle-xmark" class="svg-inline--fa fa-circle-xmark procheckmark flip" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"></path></svg></span>';
                        }

                        if(!empty($side->tags)){
                          $tag_count = 0;
                          foreach($side->tags as $side_tag){
                            echo '<span class="sideTags"><input type="hidden" name="sides['.$side_count.'][tags]['.$tag_count.'][text]" value="'.$side_tag->text.'" readonly><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="tag" class="svg-inline--fa fa-tag procheckmark flip" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M0 80V229.5c0 17 6.7 33.3 18.7 45.3l176 176c25 25 65.5 25 90.5 0L418.7 317.3c25-25 25-65.5 0-90.5l-176-176c-12-12-28.3-18.7-45.3-18.7H48C21.5 32 0 53.5 0 80zm112 32a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"></path></svg>'.$side_tag->text.'<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="circle-xmark" class="svg-inline--fa fa-circle-xmark procheckmark flip" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c9.4-9.4 24.6-9.4 33.9 0l47 47 47-47c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-47 47 47 47c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-47-47-47 47c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l47-47-47-47c-9.4-9.4-9.4-24.6 0-33.9z"></path></svg></span>';
                            $tag_count++;
                          }
                        }

                  echo '</div>

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
                    </div>';
            $side_count++;
            }
            ?>
         </div>
         <div class="form-group">
            <label class="form-label">Poll Topic</label>
            <input type="hidden" name="oldCategoryId" value="<?php echo $curr_debate->categories ? esc_attr($curr_debate->categories[0]->id) : ''; ?>">
            <select data-required="true" name="categoryId" class="categoryId">
               <option value="" data-id="">Select a category</option>
               <?php
                foreach($debate_cats as $debate_cat){
                  $selected_cat = $curr_debate->categories && $curr_debate->categories[0]->id == $debate_cat->id ? 'selected' : '';
                  echo '<option '.esc_attr($selected_cat).' value="'.esc_attr($debate_cat->id).'" data-id="'.esc_attr($debate_cat->id).'">'.esc_html($debate_cat->text).'</option>';
                }
               ?>
            </select>
            <textarea class=" mt-3 form-control" name="background" placeholder="Share any information that might help people choose a side"><?php echo esc_html($curr_debate->background); ?></textarea>
         </div>
         <div class="form-group">
            <div class="inline-content justify-content-between">
                <label class="form-label">Poll Image</label>
                <span class="fieldInfo">Recommended 300 x 150 px</span>
            </div>
            <div class="field-backgroundImage img-field-wrap">
              <?php if(!empty($curr_debate->backgroundImageObject)) { ?>
                <span class="close">                 
                  <svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11 1.10786L9.89214 0L5.5 4.39214L1.10786 0L0 1.10786L4.39214 5.5L0 9.89214L1.10786 11L5.5 6.60786L9.89214 11L11 9.89214L6.60786 5.5L11 1.10786Z" fill="#9B9B9B"/>
                  </svg>
                </span>
                <img id="debImagePreview" src="<?php echo esc_url($curr_debate->backgroundImageObject->small->location) ; ?>">
                 <div class="img-field-placeholder" style="display: none;">
                    <svg class="upload-label" width="24" height="21" viewBox="0 0 24 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path fill-rule="evenodd" clip-rule="evenodd" d="M9.03323 0H15.1551C15.9355 0 16.6318 0.473616 16.9028 1.19554L17.5711 2.94286H21.6835C22.9219 2.94286 23.9266 3.93147 23.9266 5.15V18.3929C23.9266 19.6114 22.9219 20.6 21.6835 20.6H2.24312C1.00473 20.6 0 19.6114 0 18.3929V5.15C0 3.93147 1.00473 2.94286 2.24312 2.94286H6.3555L6.93498 1.43004C7.2621 0.570179 8.09859 0 9.03323 0ZM15.5048 1.7139C15.4487 1.57136 15.3085 1.47479 15.1543 1.47479H9.03244C8.71934 1.47479 8.43895 1.66792 8.33147 1.95301L7.39216 4.41765H2.24234C1.8311 4.41765 1.49463 4.74872 1.49463 5.15336V18.3962C1.49463 18.8009 1.8311 19.1319 2.24234 19.1319H21.6827C22.0939 19.1319 22.4304 18.8009 22.4304 18.3962V5.15336C22.4304 4.74872 22.0939 4.41765 21.6827 4.41765H16.5375L15.5048 1.7139ZM11.9633 17.2855C8.86963 17.2855 6.35547 14.8117 6.35547 11.7677C6.35547 8.72367 8.86963 6.24983 11.9633 6.24983C15.0569 6.24983 17.5711 8.72367 17.5711 11.7677C17.5711 14.8117 15.0569 17.2855 11.9633 17.2855ZM7.85059 11.7711C7.85059 9.54092 9.69649 7.72463 11.963 7.72463C14.2295 7.72463 16.0754 9.54092 16.0754 11.7711C16.0754 14.0012 14.2295 15.8175 11.963 15.8175C9.69649 15.8175 7.85059 14.0012 7.85059 11.7711Z" fill="#43A5F5"/>
                    </svg>
                  </div>
              <?php } else { ?>
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
              <?php } ?>
               <input id="debImage" data-index="0" data-imgtype="DEBATES" name="backgroundImageTemp" data-accept="jpg|jpeg|png" data-accept_msg="Image is invalid. Please upload only PNG or JPG image." type="file" class="debate-image-upload form-control-file">
               <input type="hidden" value="<?php echo esc_attr($curr_debate->backgroundImage); ?>" name="backgroundImage">
            </div>
         </div>
      </div>
      <div class="col-md-6 second-col">
         <label class="pt-0 form-label">Published on &nbsp;<span id="publishedAt" class="lightColorSpan"></span></label>
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
               <div class="datePickerWrapper <?php if($isNoEndDate) echo 'disabled'; ?>">
                  <input type="hidden" class="" id="endedAt" name="endedAt" value="">
                  <input type="text" name="endedAtField" class="form-control datepickerField"><i class="fa fa-calendar"></i>
                </div>
                <div class="noEndDate">
                  <label for="noEndDate" class="mb-0 mt-2 form-check-label">
                    <input name="noEndDate" type="checkbox" id="noEndDate" class="m-0 me-2 form-check-input" value="1" <?php if($isNoEndDate) echo ' checked'; ?>>No end date
                  </label>
                </div>
            </div>
         </div>
         <div class="form-group">
            <select name="userId">
            <option value="">Select Poll Author</option>
            <?php foreach($debate_authors as $debate_author){
              $selected_author = $curr_debate->user->id == $debate_author->id ? 'selected' : '';
              echo '<option '.esc_attr($selected_author) .' value="'.esc_attr($debate_author->id).'">'.esc_html($debate_author->name).'</option>';
            } ?>
          </select>
        </div>
        <?php if($currentDomainId !== null){ ?>
          <div class="form-group">
            <label class="form-label">Bulk Actions:</label>
            <div class="deployActions">
              <?php if($sel_debate_data_key === false) { ?>
                <a onclick="showDeployDebateModal('tab-placement');" class="linkActive" title="Deploy to All Pages">Deploy to All Pages</a>
              <?php } else { ?>
                <a onclick="removeDebateFromAllPages();" class="linkActive" title="Deploy to All Pages">Remove from All Pages</a>
              <?php } ?>
              <?php if($sel_debate_data && $sel_debate_data->embedDomainCategories !== ''){ ?>
                <a onclick="showDeployDebateModal('tab-category');" class="linkActive" title="Deploy to Category">Deployed to <?php echo $sel_debate_data->embedDomainCategories; ?></a>
              <?php } else { ?>
                <a onclick="showDeployDebateModal('tab-category');" class="linkActive" title="Deploy to Category">Deploy to Category</a>
              <?php } ?>
            </div>
          </div>
        <?php } ?>
         <div class="form-group">
            <div class="inline-content justify-content-between"><label class="form-label" for="endedAt">Poll Preview</label><span class="fieldInfo">300 px wide</span></div>
         </div>
         <div class="debatePreviewSection">
            <div class="debatePreviewInner">
               <div class="debatePreviewHeader">
                  <div class="author">
                     <a target="_blank" rel="noreferrer" class="small-dp user-color-green" href="/<?php echo esc_url($curr_debate->user->username); ?>" tabindex="0">
                        <img data-src="" src="<?php echo esc_url($curr_debate->user->avatarObject->thumb->location); ?>" alt="Super Admin" class="sidedLazyLoad img-circle avatar" style="border-color: rgb(219, 219, 219);">
                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="check-circle" class="svg-inline--fa fa-check-circle fa-w-16 procheckmark" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="color: rgb(31, 170, 115);">
                           <path fill="currentColor" d="M504 256c0 136.967-111.033 248-248 248S8 392.967 8 256 119.033 8 256 8s248 111.033 248 248zM227.314 387.314l184-184c6.248-6.248 6.248-16.379 0-22.627l-22.627-22.627c-6.248-6.249-16.379-6.249-22.628 0L216 308.118l-70.059-70.059c-6.248-6.248-16.379-6.248-22.628 0l-22.627 22.627c-6.248 6.248-6.248 16.379 0 22.627l104 104c6.249 6.249 16.379 6.249 22.628.001z"></path>
                        </svg>
                     </a>
                     <div class="copy-wrap">
                        <a target="_blank" rel="noreferrer" class="authorname" href="/<?php echo esc_url($curr_debate->user->username); ?>" tabindex="0">
                           <h6><?php echo esc_html($curr_debate->user->name); ?></h6>
                        </a>
                        <a rel="noreferrer" class="authorhandle"><span class="handler">Posted <?php echo date_format(date_create($curr_debate->startedAt) , 'M d, Y'); ?></span></a>
                     </div>
                  </div>
               </div>
               <?php if(!empty($curr_debate->backgroundImageObject)) { ?>
                <div class="debatePreviewBgImg mt-3">
                  <img alt="backgroundImage" src="<?php echo esc_url($curr_debate->backgroundImageObject->small->location) ; ?>">
                </div>
              <?php } ?>
               <div class="debatePreviewTitle mt-3">
                  <h3><?php echo esc_html($curr_debate->thesis); ?></h3>
               </div>
               <span class="lightColorSpan mt-2 d-block">
                <?php if($curr_debate->isClosed) { ?>
                  <span className="lightColorSpan mt-2 d-block">Poll ended <?php echo date_format(date_create($curr_debate->endedAt) , 'M d, Y'); ?></span>
                <?php } else { 
                    $date1=date_create($curr_debate->endedAt);
                    $date2=date_create(date("Y-m-d"));
                    $diff=date_diff($date1,$date2);
                    $final_difference = $diff->y ? esc_html($diff->y) . ' year(s)' : ($diff->m ? esc_html($diff->m) .' month(s)' : ($diff->d ? esc_html($diff->d) .' day(s)' : ($diff->h ? esc_html($diff->h) . ' hour(s)' : ($diff->i ? esc_html($diff->i) . ' min(s)' : ($diff->s ? esc_html($diff->s).' sec(s)' : '')))));
                ?>
                  <span className="lightColorSpan mt-2 d-block"><?php if(!$isNoEndDate) { ?>Poll ends in <?php echo esc_html($final_difference); ?> •<?php } ?> Vote below</span>
                <?php } ?>
                </span>
               <div class="debatePreviewSides mt-2">
                <?php foreach ($curr_debate->sides as $side) {
                  echo '<div class=""><label>'.esc_html($side->text).'</label></div>';
                } ?>
               </div>
               <span class="lightColorSpan mt-2 d-block"><?php echo esc_html($curr_debate->votes);  ?> Votes • <?php echo esc_html($curr_debate->comments); ?> Comments </span>
               <div class="debatePreviewAdunit mt-2">
                  <p>[ad unit 300x250]</p>
               </div>
               <div class="debatePreviewFooter mt-2"><a class="customAnchor">Share</a><img alt="Logo" src="https://cdn.sided.co/prod/sided/wl/sided/images/c7ac0980-0852-11eb-b673-1dad531e23be.png" class="logo"></div>
            </div>
         </div>
      </div>
   </form>
    <div class="custom-popup-wrap" style="display: none;">
      <div class="custom-popup">
        <svg role="button" class="close" onclick="hideDeployDebateModal();" viewPort="0 0 12 12" version="1.1" xmlns="http://www.w3.org/2000/svg">
            <line x1="1" y1="11" 
                  x2="11" y2="1" 
                  stroke="black" 
                  stroke-width="2"/>
            <line x1="1" y1="1" 
                  x2="11" y2="11" 
                  stroke="black" 
                  stroke-width="2"/>
        </svg>
        <form method="post" class="deploy-debate-form sidedCustomTabsContent">
          <input type="hidden" value="<?php echo $curr_debate->id; ?>" name="debateId">
          <input type="hidden" value="<?php echo $currentDomainId;
           ?>" name="embedDomainId">
          <input type="hidden" value="<?php echo $curr_debate->clientId; ?>" name="currDebateClientId">
          <div class="tabContent" id="tab-category" style="display: none;">
            <div class="mb-3 d-flex justify-content-between">
              <h3 class="fw-bold">Publish to Category</h3>
            </div>
            <p class="mb-3">Choose the categories you would like to publish this poll to</p>
            <div class="sidedCustomTabs">
              <a class="active" data-target="tab-category">Category</a>
              <a class="" data-target="tab-placement">Placement</a>
            </div>
            <div class="network-list">
              <div class="network-list-row">
                <?php $is_allCats_checked = count(explode(",",$sel_debate_data->embedDomainCategories)) === count($categories_list) ? 'checked' : ''; ?>
                <label className="network-list-item">
                  <input data-name="embedDomainCategories[]"
                        type="checkbox" 
                        class="sided-checkbox1"
                        onclick="checkAll(this);" 
                        <?php echo $is_allCats_checked; ?>
                      />Available categories (<?php echo count($categories_list); ?>)
                  </label>
              </div>
              <?php
                foreach($categories_list as $category){ 

                  $checked = '';
                  if($sel_debate_data->embedDomainCategories && array_search($category->text , explode(",",$sel_debate_data->embedDomainCategories)) !== false){
                    $checked = 'checked';
                  }

                  echo '
                    <div class="network-list-row">
                      <label className="network-list-item">
                        <input name="embedDomainCategories[]"
                              type="checkbox" 
                              class="sided-checkbox" 
                              value="'.$category->text.'"
                              '.$checked.'
                            />
                            '.$category->text.'
                          
                      </label>
                    </div>';
                }
              ?>
            </div>
            <div class="textCenter form-btns common-form mt-4 mb-3">
              <button onclick="hideDeployDebateModal();" type="button" class="mx-1 btn btn-secondary">Cancel</button>
              <button onclick="showDeployDebateModal('tab-placement');" type="button" class="mx-1 btn btn-primary">Select Placement</button>
            </div>
          </div>
          <div class="tabContent" id="tab-placement">
            <div class="mb-3 d-flex justify-content-between">
              <h3 class="fw-bold">Select Placement</h3>
            </div>
            <p class="mb-3">Select the location where this poll will show</p>
            <div class="sidedCustomTabs">
              <a class="" data-target="tab-category">Category</a>
              <a class="active" data-target="tab-placement">Placement</a>
            </div>
            <div class="network-list">
              <div class="network-list-row">
                <?php $is_allPlacement_checked = count($embed_domain_data_filtered) ===count($embed_placements) ? 'checked' : ''; ?>
                <label className="network-list-item">
                  <input data-name="embedPlacementIds[]"
                        type="checkbox" 
                        class="sided-checkbox1"
                        onclick="checkAll(this);" 
                        <?php echo $is_allPlacement_checked; ?>
                      />Available placements (<?php echo count($embed_placements); ?>)
                </label>
                <label><input type="checkbox" checked="true" class="sided-checkbox1" value="true" name="replaceExistingPolls">Replace existing polls </label>
              </div>
              <?php
                $i = 0;
                foreach($embed_placements as $embed_placement){ 
                  $checked = '';
                  if($embed_domain_data_filtered && array_search($embed_placement->id, array_column( $embed_domain_data_filtered, 'embedPlacementId')) !==  false){
                    $checked = 'checked';
                  }
                  echo '
                    <div class="network-list-row">
                      <label className="network-list-item">
                        <input name="embedPlacementIds[]"
                              type="checkbox" 
                              class="sided-checkbox" 
                              value="'.$embed_placement->id.'"
                              '.$checked.'
                            />
                            '.$embed_placement->placementName.'
                      </label>
                    </div>';
                    $i++;
                }
              ?>
            </div>
            <div class="textCenter form-btns common-form mt-4 mb-3">
              <button onclick="hideDeployDebateModal();" type="button" class="mx-1 btn btn-secondary">Back</button>
              <input type="submit" class="mx-1 btn btn-primary" name="publish_poll" value="Publish Poll">
            </div>
          </div>
        </form>
        <?php if($sel_debate_data_key !== false) { ?>
          <form method="post" class="remove-debate-form" id="remove-debate-form">
            <input type="hidden" value="<?php echo $curr_debate->id; ?>" name="debateId">
            <input type="hidden" value="<?php echo $currentDomainId;
             ?>" name="embedDomainId">
            <input type="hidden" value="<?php echo $curr_debate->clientId; ?>" name="currDebateClientId">
            <input type="hidden" value="true" name="remove_poll">
          </form>
        <?php } ?>
      </div>
    </div>
  </div>
</div>
<script type="text/javascript">
  var $ = jQuery;
  function hideDeployDebateModal(){
    $('.custom-popup-wrap').fadeOut();
    $('body').css('overflow','auto');
  }
  function showDeployDebateModal(targetTab){
    $('body').css('overflow','hidden');
    $('.custom-popup-wrap').fadeIn();
    $('.sidedCustomTabsContent .tabContent').hide();
    $('#'+targetTab).show();
  }
  $('.sidedCustomTabs a:not(.active)').click(function(){
    var targetTab = $(this).data('target');
    $('.sidedCustomTabsContent .tabContent').hide();
    $('#'+targetTab).show();
  });
  function removeDebateFromAllPages(){
    console.log($('#remove-debate-form'));
    let text = "Are you sure you want to remove poll from all pages?";
    if (confirm(text) !== true) {
      return false;
    }
    $('#remove-debate-form').submit();
  }
  $('.deploy-debate-form').submit(function(){
    if($(this).find('input[name="embedPlacementIds[]"]:checked').length < 1){
      alert('Please select location where poll appears.');
      return false;
    }
    $('.form-btns *').prop('disabled', true);
    $('.form-btns *').css('opacity', '0.3');
  });

  $(document).keyup(function(e) {
    if (e.keyCode == 27) { // Esc
        hideDeployDebateModal();
    }
  });
  function checkAll(el){
    console.log($(el).data('name'));
    $('input[name="'+$(el).data('name')+'"]').each(function(){
        $(this).prop('checked', el.checked);
    });
  }
  $('.sided-checkbox').change(function(){
    var parentEl = $(this).parents('.network-list');
    if(parentEl.find('input[type="checkbox"].sided-checkbox').length === parentEl.find('input[type="checkbox"]:checked.sided-checkbox').length){
      parentEl.find('input:checkbox:first').prop('checked' , true);
    } else {
      parentEl.find('input:checkbox:first').prop('checked' , false);
    }
  })
</script>