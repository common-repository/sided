<?php 
class SidedDebatesPlugin {
  public static function register() {
    $instance = new self();

        add_shortcode('sided-debate-embed', [$instance, 'shortcodeDebateEmbed']);
  }

    public static function shortcodeDebateEmbed($atts) {
      $id = isset($atts['debate-id']) ? $atts['debate-id'] : 'ID';
      // $ads = isset($atts['ads']) ? '&ads='.sanitize_text_field($atts['ads']) : '';
      // $ad_position = isset($atts['ad-position']) ? '&ad-position='.sanitize_text_field($atts['ad-position']) : '';
      // $ad_display = isset($atts['ad-display']) ? '&ad-display='.sanitize_text_field($atts['ad-display']) : '';
      // $appUrl = SIDED_APP_URL;
      /*$embed = '<script type="text/javascript" defer>document.write(\'<iframe src="'.$appUrl.'/embed2/#/main?id='.$id.$ads.$ad_position.$ad_display.'" class="sde-main" id="sde-main-iframe-'.$id.'"></iframe>\');</script><script type="text/javascript" src="'.$appUrl.'/embed-assets/script.js" defer></script>';*/

      $embed = '<div class="sided-widget" debateId="'.$id.'"></div>';

    return $embed;
    }
}