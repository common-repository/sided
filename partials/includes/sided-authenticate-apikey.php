<?php

if (!class_exists( 'Sided_Authentication_Status_Controller' )) {
  class Sided_Authentication_Status_Controller {
    const ACCESS_TOKEN_OPTION_NAME = 'sided_sided_private_access_token';
    const TRANSIENT_DURATION_SECONDS = DAY_IN_SECONDS;
    const TRANSIENT_NAME = SIDED_AUTH_STATUS_TRANSIENT_NAME;

    public function __construct() {
      add_action( 'update_option', array( $this, 'refresh_authentication_status' ), 10, 1 );
    }

    public function set_authentication_status($option) {
      if (self::ACCESS_TOKEN_OPTION_NAME !== $option) {
        return;
      }

      $this->update_transient();
    }

    public function get_transient() {
      return get_transient(self::TRANSIENT_NAME);
    }

    public function update_transient($value) {
      set_transient(self::TRANSIENT_NAME, $value, self::TRANSIENT_DURATION_SECONDS);
    }

    public function get_private_access_token() {
      return get_option(self::ACCESS_TOKEN_OPTION_NAME);
    }

    public function get_api_route() {
      return SIDED_API_URL . '/auth/authenticateAccessToken';
    }

    public function get_api_headers($access_token) {
      return array(
        'timeout' => 2,
        'headers' => array(
          'x-source-type' => 'wp-plugin',
          'x-private-access-token'=> $access_token,
        ),
      );
    }

    public function api_response_is_success($response) {
      if (
        isset($response->status) &&
        $response->status === 'success' &&
        isset( $response->message ) &&
        $response->message === 'Authentication Successful!'
      ) {
        return true;
      }
      return false;
    }

    public function get_authentication_status_from_api() {
      $access_token = $this->get_private_access_token();
      if (!$access_token || $access_token === '') {
        return 'No Token';
      }

      $response = wp_remote_post($this->get_api_route(), $this->get_api_headers($access_token));

      if (!is_wp_error($response)) {
        $response_body = json_decode($response['body']);

        if ($this->api_response_is_success($response_body)) {
          return 'Valid Token';
        }
      }

      return 'Invalid Token';
    }

    public function refresh_authentication_status($option = null) {
      if ($option && $option !== self::ACCESS_TOKEN_OPTION_NAME) {
        return;
      }

      $status = $this->get_authentication_status_from_api();
      $this->update_transient($status);

      return $status;
    }

    public function get_authentication_status() {
      $status = $this->get_transient();

      if ($status && $status === 'Valid Token') {
        return $status;
      }

      $status = $this->refresh_authentication_status();
      
      return $status;
    }
  }
}

$Sided_Authentication_Status_Controller = new Sided_Authentication_Status_Controller;
$status_aat = $Sided_Authentication_Status_Controller->get_authentication_status();
$sided_private_access_token = $Sided_Authentication_Status_Controller->get_private_access_token();
