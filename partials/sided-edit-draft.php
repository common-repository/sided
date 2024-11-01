<?php 

include 'includes/sided-authenticate-apikey.php';

if($status_aat !== 'Valid Token'){
  echo '<script>
          window.location = "'.get_site_url().'/wp-admin/admin.php?page=settings";
        </script>';
  exit;
}

include 'includes/sided-common-header.php';

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
  $response_arr = json_decode($response['body']);
  if($response_arr->status == 'success'){
    if($_POST['type'] == 'create'){
      echo '<script>window.location.href="admin.php?page=dashboard&debate='.esc_js($_POST['debateId']).'&action=edit-debate"</script>';

      die;
    }
    echo '<h3 style="color: green !important;">Draft updated successfully</h3>';
    
  } else {
    echo '<h3 style="color: red !important;">There is an error is submitting debate data. Please try again later.</h3>';
  }
}

/* Get Current Poll Data Start */
$curr_debate = '';
if($_GET['debate']){
  $url = SIDED_API_URL.'/debate/'.sanitize_text_field($_GET['debate']).'?deviceId=123123';
  $args = array( 'timeout' => 50,
            'headers' => array( 'x-source-type' => 'wp-plugin',
                               'x-private-access-token'=> $sided_private_access_token ) 
             );
  $response = wp_remote_get( $url , $args);
  $response_arr = json_decode($response['body']);
  if($response_arr->status == 'success'){
    $curr_debate = $response_arr->data;
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
}
/* Get Current Poll Data End */

/* fetch debate authors start */
$debate_authors = '';
$url_fda = SIDED_API_URL.'/user/list?type=DEBATE_AUTHORS&clientId='.$selected_network;
$header_fda = array( 'timeout' => 50,
          'headers' => array( 'x-source-type' => 'wp-plugin',
                             'x-private-access-token'=> $sided_private_access_token ) 
           );
$response_fda = wp_remote_get( $url_fda, $header_fda);
$response_fda_arr = json_decode($response_fda['body']);
$debate_authors = $response_fda_arr->data;
/* fetch debate authors end */

/* fetch Current network's cats start */
$debate_cats = '';
$url_fcnc = SIDED_API_URL.'/categories?type=START_DEBATE&clientId='.$selected_network;
$header_fcnc = array( 'timeout' => 50,
          'headers' => array( 'x-source-type' => 'wp-plugin',
                             'x-private-access-token'=> $sided_private_access_token ) 
           );
$response_fcnc = wp_remote_get( $url_fcnc, $header_fcnc);
$response_fcnc_arr = json_decode($response_fcnc['body']);
$debate_cats = $response_fcnc_arr->data;
/* fetch Current network's cats end */

/*fetch curret user details start*/
$current_user = '';
$url_fcu = SIDED_API_URL.'/user/'.sanitize_text_field($response_aat_arr->data->userId);
$header_fcu = array( 'timeout' => 50,
          'headers' => array( 'x-source-type' => 'wp-plugin',
                             'x-private-access-token'=> $sided_private_access_token ) 
           );
$response_fcu = wp_remote_get( $url_fcu, $header_fcnc);
$response_fcu_arr = json_decode($response_fcu['body']);
$current_user = $response_fcu_arr->status == 'success' ? $response_fcu_arr->data : '';
/*fetch curret user details end*/

?>
  
<script type="text/javascript">
(function ($) { // Wait for the DOM to be ready

  function validImage(val) {
    var regex = new RegExp("(.*?)\.(jpg|jpeg|png)$");
    return regex.test(val);
  }

  $(document).on('change', '.debate-image-upload', function(){
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
        url: '<?php echo esc_js(SIDED_API_URL); ?>/debate/imageUpload?type=DEBATES&clientId=<?php echo esc_js($curr_debate->clientId); ?>',
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
        //maxDate: app.sn_max_debate_duration != '' ? moment('<?php echo $curr_debate->startedAt ?>').add(app.sn_max_debate_duration, 'days') : '',
        timePicker: true,
        locale: {
          format: 'MM/DD/YY @ hh:mmA'
        },
      }, function(start, end, label) {
        $('#endedAt').val(start);
      });
    }

    $('#addSide').click(function(){
      if($('.side').length > 5){ alert('Maximum 6 Sides are allowed'); return false; } 
      $nwSide = $('.side').first().clone();
      $nwSide.find('label.error').remove();
      $nwSide.find('input').val('');
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
        $(this).find("input[name^='sides']").attr('placeholder','Side '+(index+1));
        $(this).find("input[name^='sides']").attr('name','sides['+(index)+'][text]');
      });
    }

    $('#noEndDate').click(function(){
      $('#endedAt').parents('.datePickerWrapper').toggleClass('disabled');
    });
  })
}(jQuery));
</script>
<style type="text/css">
  .side:nth-child(2) input{
    border-color: #43a5f5;
  }
  .side:nth-child(3) input{
    border-color: #d91414;
  }
  .side:nth-child(4) input{
    border-color: #f4900c;
  }
  .side:nth-child(5) input{
    border-color: #ab47bd;
  }
  .side:nth-child(6) input{
    border-color: #2aa974;
  }
  .side:nth-child(7) input{
    border-color: #4f87b1;
  }

</style>
<div class="content-wrapper1">
  <div class="content">
   <form id="createDebate" class="startDebate common-form row" action="" method="POST" enctype="multipart/form-data">
    <div class="pageHeader inline-content align-items-center justify-content-between">
      <h3>Edit Draft</h3>
      <?php if($curr_debate->isDraft == '1') { ?>
        <div class="buttons mb-4 d-flex justify-content-end">
            <button type="submit" class="mt-0 me-0 btn btn-secondary" onclick="jQuery('#typeField').val('draft');">Save Draft</button>
            <button type="submit" class="mt-0 me-0 btn btn-primary" onclick="jQuery('#typeField').val('create');">Publish</button>
        </div>
      <?php } else { echo '<label class="error">This debate is already published.</label>';} ?>
    </div>
    <input type="hidden" name="debateId" value="<?php echo esc_attr($curr_debate->id); ?>">
    <input type="hidden" name="clientId" value="<?php echo esc_attr($curr_debate->clientId); ?>">
    <input id="typeField" type="hidden" name="type" value="create">
      <div class="col-md-6">
         <div class="form-group pt-0"><label class="form-label" for="thesis">Poll Title</label><input data-required="true" name="thesis" placeholder="Type statement or questions here..." type="text" id="thesis" class="form-control" value="<?php echo esc_attr($curr_debate->thesis); ?>"></div>
         <div class="form-group side-container">
            <div class="inline-content justify-content-between">
              <label class="form-label">Your Sides</label>
              <span id="addSide" role="button" class="customAnchor">+ Add Side</span>
            </div>
            <?php 
              foreach($curr_debate->sides as $key=>$side){
                echo '<div class="side">
                   <div class="message position-relative">
                      <span role="button" class="delete-side delete-button" style="display: none;">
                          <svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path d="M11 1.10786L9.89214 0L5.5 4.39214L1.10786 0L0 1.10786L4.39214 5.5L0 9.89214L1.10786 11L5.5 6.60786L9.89214 11L11 9.89214L6.60786 5.5L11 1.10786Z" fill="#9B9B9B"/>
                          </svg>
                      </span>
                      <input data-required="true" data-index="'.esc_attr($key).'" name="sides['.esc_attr($key).'][text]" type="text" class="form-control" value="'.esc_attr($side->text).'">
                   </div>
                </div>';
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
         <label class="pt-0 form-label">Draft saved on &nbsp;<span id="publishedAt" class="lightColorSpan"></span></label>
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
  </div>
</div>