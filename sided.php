<?php
/**
* Plugin Name: Sided
* Plugin URI: https://sided.co/
* Description: It is a wordpress plugin to embed sided polls in your Wordpress website.
* Version: 1.4.2
* Author: Sided
**/

define( 'SIDED_VERSION', '1.4.2' );
define( 'SIDED_PLUGIN', __FILE__ );
define( 'SIDED_PLUGIN_DIR', untrailingslashit( dirname( SIDED_PLUGIN ) ) );
require_once SIDED_PLUGIN_DIR . '/env.php';
require_once SIDED_PLUGIN_DIR . '/partials/functions.php';

require __DIR__ . '/partials/sided-shortcode-to-embed.php';
SidedDebatesPlugin::register();

if ( ! is_admin() ) {
    return;
}

add_action('admin_menu', 'sided_menu');

function sided_menu() {  

  add_menu_page( 
      'Sided', 
      'Sided', 
      'edit_posts', 
      'dashboard', 
      'sided_dashboard_callback_function', 
      'dashicons-editor-code' 
     );
  add_submenu_page( 
      'dashboard', 
      'Dashboard',
      'Dashboard', 
      'edit_posts', 
      'dashboard', 
      null, 
      null
     );
 	add_submenu_page( 
      'dashboard', 
      'Create Poll',
      'Create Poll', 
      'edit_posts', 
      'create-debate', 
      'sided_create_debate_callback_function', 
      null
     );
 	add_submenu_page( 
      null, 
      'Edit Poll',
      'Edit Poll', 
      'edit_posts', 
      'dashboard&debate=&action=edit', 
      'sided_edit_debate_callback_function', 
      null
     );
  add_submenu_page( 
      null, 
      'Edit Draft',
      'Edit Draft', 
      'edit_posts', 
      'edit-draft', 
      'sided_edit_draft_callback_function', 
      null
     );
  add_submenu_page( 
      'dashboard', 
      'Settings',
      'Settings', 
      'edit_posts', 
      'settings', 
      'sided_settings_callback_function', 
      null
     );
  add_submenu_page( 
      'null', 
      'Create Poll from Block',
      'Create Poll from Block', 
      'edit_posts', 
      'create-debate-from-block', 
      'sided_create_debate_from_block_callback_function', 
      null
     );
}

function sided_dashboard_callback_function() {
  echo '<div class="sided-wp-plugin-wrapper">';
  include 'partials/sided-dashboard.php';
  echo '</div>';
}

function sided_settings_callback_function() {
  echo '<div class="sided-wp-plugin-wrapper">';
  include 'partials/sided-settings.php';
  echo '</div>';
}

function sided_create_debate_callback_function() {
  echo '<div class="sided-wp-plugin-wrapper">';
  include 'partials/sided-create-debate.php';
  echo '</div>';
}

function sided_edit_debate_callback_function() {
  echo '<div class="sided-wp-plugin-wrapper">';
  include 'partials/sided-edit-debate.php';
  echo '</div>';
}

function sided_edit_draft_callback_function() {
  echo '<div class="sided-wp-plugin-wrapper">';
  include 'partials/sided-edit-draft.php';
  echo '</div>';
}

function sided_create_debate_from_block_callback_function() {
  echo '<div class="sided-wp-plugin-wrapper">';
  include 'partials/sided-create-debate-from-block.php';
  echo '</div>';
}

include 'partials/includes/sided-authenticate-apikey.php';
if($status_aat == 'Valid Token') {
  require_once SIDED_PLUGIN_DIR . '/includes/block-editor/sided-block-editor.php';
}