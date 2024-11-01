<?php

include 'includes/sided-authenticate-apikey.php';
if($status_aat == 'Valid Token'){
	/*echo '<script>
          window.location = "'.get_site_url().'/wp-admin/admin.php?page=debates";
        </script>';*/
    if(isset($_GET['debate']) && isset($_GET['action'])){
    	if(sanitize_text_field($_GET['action']) == 'edit-draft'){
    		include 'sided-edit-draft.php';
    	} else {
    		include 'sided-edit-debate.php';
    	}
    	exit;
    }
    include 'sided-debates.php';
  	exit;
}

if($status_aat !== 'No Token'){
	echo '<script>
          window.location = "'.get_site_url().'/wp-admin/admin.php?page=settings";
        </script>';
  	exit;
}
?>

<?php include 'includes/sided-settings-header.php'; ?>
<h3>Have a Sided publisher account?<br>
<small><a target="_blank" href="https://app.sided.co/admin-settings/"><u>Login</u></a> here to your Sided Publisher dashboard find your API key on the Settings page for your network</small></h3>