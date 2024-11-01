<div class="notification"></div>

<?php
if($status_aat === 'Valid Token'){
  $url_gsdc = SIDED_API_URL.'/userClients/getStartDebateClients';
    $header_gsdc = array( 'timeout' => 50,
              'headers' => array( 'x-source-type' => 'wp-plugin',
                                 'x-private-access-token'=> $sided_private_access_token ) 
               );
  $response_gsdc = wp_remote_get( $url_gsdc, $header_gsdc);
  //echo '<pre>'; print_r($response_gsdc ); echo '</pre>';
  if( !is_wp_error( $response_gsdc ) ) {
    $response_gsdc_arr = json_decode($response_gsdc['body']);
    $networks_list = $response_gsdc_arr->data;
  } else {
    echo $response_gsdc->get_error_message();
  }

  /*Selected network*/
$selected_network = get_option('sided_sided_selected_network');
$key = array_search($selected_network, array_column($networks_list,'id'));
  echo '<script>
          window.app = {
            sn_default_debate_duration: '.esc_js($networks_list[$key]->extra->defaultDebateDuration).',
            sn_max_debate_duration: '.esc_js($networks_list[$key]->extra->maxDebateDuration).', 
          };
        </script>';
}
?>
<script type="text/javascript">
  (function ($) {
    $(window).ready(function() {
      $("form:not(.search-debate)").on("keypress", function (event) {
          var keyPressed = event.keyCode || event.which;
          if (keyPressed === 13) {
              event.preventDefault();
              return false;
          }
      });
    });
  }(jQuery));
</script>
<div class="common-header inline-content justify-content-between align-items-center">
  <div class="inline-content align-items-center">
    <h1><strong>Sided</strong> WP Plugin <small>v<?php echo SIDED_VERSION; ?></small></h1>
  </div>
  <ul class="nav navbar inline-content justify-content-between">
    <li><a href="admin.php?page=dashboard" class="<?php echo isset($_GET['page']) && $_GET['page'] == 'dashboard' ? 'active' : '' ?>">Dashboard</a></li>
    <li><a href="admin.php?page=settings" class="<?php echo isset($_GET['page']) && $_GET['page'] == 'settings' ? 'active' : '' ?>">Settings</a></li>
    <li><a href="mailto: support@sided.co">Support</a></li>
  </ul>
</div>