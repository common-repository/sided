<?php
$error = '';
if(isset($_POST['submit_apikey'])){
	if(isset($_POST['sided_private_access_token']) && trim($_POST['sided_private_access_token']) != ''){
		$sided_private_access_token = sanitize_text_field($_POST['sided_private_access_token']);
		delete_transient(SIDED_AUTH_STATUS_TRANSIENT_NAME);
		update_option('sided_sided_private_access_token', $sided_private_access_token);
	} else {
		$error = "Sided API key is required!";
	}
}

if(get_option('sided_sided_embed_placement_options') && !array_key_exists('updated_at', get_option('sided_sided_embed_placement_options'))){
	delete_option('sided_sided_embed_placement_options');
}

if(isset($_POST['new_api'])){
	delete_transient(SIDED_AUTH_STATUS_TRANSIENT_NAME);
	delete_option('sided_sided_private_access_token');
	delete_option('sided_sided_initiate_script');
	delete_option('sided_sided_embed_placement_options');
	delete_option('send_cats_to_sided');
	delete_option('send_tags_to_sided');
}

$sided_initiate_script = get_option('sided_sided_initiate_script');
$send_cats_to_sided = get_option('send_cats_to_sided');
$send_tags_to_sided = get_option('send_tags_to_sided');

include 'includes/sided-authenticate-apikey.php';
?>
<?php include 'includes/sided-settings-header.php'; ?>
<div class="wrap settings-form common-form "> 
    <form method="POST" action="">
    	<label>Enter your API Key</label>
    	<input type="text" name="sided_private_access_token" placeholder="Your api key will appear here once activated" value="<?php echo esc_attr($sided_private_access_token); ?>"><br>
    	<p class="error"><?php echo esc_html($error); ?></p>
    	<?php if($status_aat === 'Invalid Token'){
    		echo '<div class="inline-content justify-content-between mt-2">
						<div class="left inline-content">
							<svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M7.65 11.05H9.35V12.75H7.65V11.05ZM7.65 4.25H9.35V9.35H7.65V4.25ZM8.5 0C3.7995 0 0 3.825 0 8.5C0 10.7543 0.895533 12.9163 2.48959 14.5104C3.27889 15.2997 4.21592 15.9258 5.24719 16.353C6.27846 16.7801 7.38376 17 8.5 17C10.7543 17 12.9163 16.1045 14.5104 14.5104C16.1045 12.9163 17 10.7543 17 8.5C17 7.38376 16.7801 6.27846 16.353 5.24719C15.9258 4.21592 15.2997 3.27889 14.5104 2.48959C13.7211 1.70029 12.7841 1.07419 11.7528 0.647024C10.7215 0.219859 9.61624 0 8.5 0ZM8.5 15.3C6.69653 15.3 4.96692 14.5836 3.69167 13.3083C2.41643 12.0331 1.7 10.3035 1.7 8.5C1.7 6.69653 2.41643 4.96692 3.69167 3.69167C4.96692 2.41643 6.69653 1.7 8.5 1.7C10.3035 1.7 12.0331 2.41643 13.3083 3.69167C14.5836 4.96692 15.3 6.69653 15.3 8.5C15.3 10.3035 14.5836 12.0331 13.3083 13.3083C12.0331 14.5836 10.3035 15.3 8.5 15.3Z" fill="#EB5757"/>
							</svg>
							<span class="fadedSpan">&nbsp;Your key is invalid. Please Try again.</span>
						</div>
						<div class="right">
							<ul class="nav navbar inline-content m-0">
								<li><a href="mailto: support@sided.co">Need help?</a></li>
								<li><a href="https://get.sided.co/">Create New API</a></li>
							</ul>
						</div>
					</div>';
    	} ?>

    	<?php
    		if($status_aat === 'Valid Token'){
				echo '<div class="inline-content justify-content-between mt-2">
						<div class="left inline-content">
							<svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M8.5 0C3.825 0 0 3.825 0 8.5C0 13.175 3.825 17 8.5 17C13.175 17 17 13.175 17 8.5C17 3.825 13.175 0 8.5 0ZM8.5 15.3C4.7515 15.3 1.7 12.2485 1.7 8.5C1.7 4.7515 4.7515 1.7 8.5 1.7C12.2485 1.7 15.3 4.7515 15.3 8.5C15.3 12.2485 12.2485 15.3 8.5 15.3ZM12.4015 4.743L6.8 10.3445L4.5985 8.1515L3.4 9.35L6.8 12.75L13.6 5.95L12.4015 4.743Z" fill="#27AE60"/>
							</svg>
							<span class="fadedSpan">&nbsp;API Key Successfully Activated</span>
						</div>
						<div class="right">
							<ul class="nav navbar inline-content m-0">
								<li><a href="mailto: support@sided.co">Need help?</a></li>
								<li>
									<form method="post">
								      <input role="anchor" type="submit" name="new_api" value="Use new API key" /><br/>
								   </form>
								</li>
							</ul>
						</div>
					</div>
					<a href="admin.php?page=dashboard" class="btn btn-primary ms-0 mt-3">Success! You are now connected to Sided. Go to your dashboard</a>';
			} else {
				echo '<input type="submit" name="submit_apikey" value="Activate Plugin" class="btn btn-primary ms-0 mt-3">';
			}
    	?>
    </form>
    <?php if($status_aat === 'Valid Token'){ ?>
    	<hr/>
    	<div class="wrap one-click-integration common-form">
    		<label>One Click Integration</label>

    		<span class="fadedSpan">Select network:</span><br>
    		<select class="mb-3 mt-2 select_network" name="select_network">
				<option> - Select network - </option>
				<?php $sided_selected_network = get_option('sided_sided_selected_network');
					foreach($networks_list as $network){
					$selected = $sided_selected_network == $network->id ? 'selected' : '';
					echo '<option '.esc_attr($selected).' value="'.esc_attr($network->id).'"> '.esc_html($network->header->title).' </option>';
				} ?>
			</select>

    		<label class="mb-3"><input type="checkbox" <?php if($sided_initiate_script == 'true') { echo 'checked'; } ?> name="sided_initiate_script"><span class="fadedSpan">&nbsp;Add Sided script to header</span></label>

    		<label class="mt-3">Add placements to posts or sidebar</label>
    		<div id="placement-option-wrapper">
    			Loading...
	    	</div>

	    	<label class="mt-3">Match polls by post category</label>
	    	<label class="mb-3 mt-2">
	    		<input type="checkbox" <?php if ($send_cats_to_sided == 'true') { echo 'checked'; } ?> name="send_cats_to_sided">
	    		<span class="fadedSpan">&nbsp;Send categories to Sided</span>
	    	</label>
	    	<label class="mb-3 mt-2">
	    		<input type="checkbox" <?php if ($send_tags_to_sided == 'true') { echo 'checked'; } ?> name="send_tags_to_sided">
	    		<span class="fadedSpan">&nbsp;Send tags to Sided</span>
	    	</label>
    	</div>
    <?php } ?>
    <hr/>
    <label>Need an API KEY?</label>
    <a class="btn btn-secondary" href="https://get.sided.co/" target="_blank">Contact Sales</a>
    <?php // echo is_active_sidebar('sidebar'); ?>
</div>
<?php 
$placement_options_array = get_option('sided_sided_embed_placement_options');
unset($placement_options_array['updated_at']);
?>
<script type="text/javascript">
(function ($) {
	$('input[name="sided_initiate_script"]').change(function() {
        var data = {
            action: 'wpa_sided_initiate_script',
            checked: $(this).is(":checked") ? true : false,
        };
        jQuery.post( ajaxurl, data, function(response) {
            console.log(response);
        });
    });

    $('input[name="send_cats_to_sided"]').change(function() {
        var data = {
            action: 'wpa_send_cats_to_sided',
            checked: $(this).is(":checked") ? true : false,
        };
        jQuery.post( ajaxurl, data, function(response) {
            console.log(response);
        });
    });

    $('input[name="send_tags_to_sided"]').change(function() {
        var data = {
            action: 'wpa_send_tags_to_sided',
            checked: $(this).is(":checked") ? true : false,
        };
        jQuery.post( ajaxurl, data, function(response) {
            console.log(response);
        });
    });

    $('body').on('change' , 'select.select_network' , function(e){
    	$('#placement-option-wrapper').html('Loading...');
    	$('.select_network').addClass('disabled');
    	var selectedValue = e.target.value;
	    $.ajax({
	      url: ajaxurl,
	      type: 'post',
	      data: {
	        action: 'wpa_fetch_embed_placements',
	        selectedValue: selectedValue
	      },
	      success: function (data) {
			$('.select_network').removeClass('disabled');
			$('#placement-option-wrapper').html('');
			var dataArray = $.parseJSON(data);
            $.each(dataArray , function(key, value){
            	$('#placement-option-wrapper').append('<div class="placement-option mt-2 inline-content justify-content-between align-items-center"><label><input type="checkbox" name="placement_id" value="'+value['id']+'"><span class="fadedSpan">&nbsp;'+value['placementName']+'</span></label> <select name="embed_location_on_page"><option value="bottom_of_every_post">Bottom of every post</option><option value="sidebar">Sidebar</option></select></div>');
            });
	      },
	      error: function (data) {
	      }
	    })
	})	
	$(document).ready(function(){
		fetch_selected_placements();
	})
    function fetch_selected_placements(){
    	$('#placement-option-wrapper').html('');
		var dataArray = $.parseJSON('<?php echo json_encode($placement_options_array); ?>');
		if(dataArray == ''){ $(".select_network").trigger("change"); }
        $.each(dataArray , function(key, value){
        	var checked = value['active'] == 'true' ? 'checked' : '';
        	var selected = value['embed_location_on_page'] == 'sidebar' ? 'selected' : '';
        	$('#placement-option-wrapper').append('<div class="placement-option mt-2 inline-content justify-content-between align-items-center">    			<label><input '+checked+'  type="checkbox" name="placement_id" value="'+value['placement_id']+'"><span class="fadedSpan">'+value['placement_text']+'</span></label> <select  name="embed_location_on_page"><option value="bottom_of_every_post">Bottom of every post</option><option value="sidebar" '+selected+'>Sidebar</option></select></div>');
        });
    }

    $('body').on('change' , '.placement-option input , .placement-option select' , function(){
    	$('.placement-option').addClass('disabled');
    	var jsonObj = {};

		$('input[name="placement_id"]').each(function(index){
		    jsonObj[index] = {'active':$(this).is(':checked'),'placement_id':$(this).val(),'placement_text':$(this).next('span').text(), 'embed_location_on_page':$(this).parents('label').next('select').val()};
		});
		var data = {
            action: 'wpa_save_embed_options',
            jsonObj: jsonObj,
        };
        jQuery.post( ajaxurl, data, function(response) {
        	$('.placement-option').removeClass('disabled');
        });
    })
}(jQuery));
</script>