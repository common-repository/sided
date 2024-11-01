<?php
add_action('wp_enqueue_scripts', 'sided_include_sided_scripts');
//delete_option('sided_sided_embed_placement_options');

function sided_include_sided_scripts()
{
    global $wp_version;

    $sided_initiate_script = get_option('sided_sided_initiate_script');
    if ($sided_initiate_script == 'true') {
        wp_enqueue_script(
            'Sided embed script', 
            sprintf('%s/embed-assets/load.min.js', SIDED_AWS_S3_BASE_URL),
            [], 
            sprintf('%s_9.3.5', $wp_version)
        );

        add_filter( 'script_loader_tag', function ( $tag, $handle ) {
            if ( 'Sided embed script' !== $handle ) {
                return $tag;
            }
            //return str_replace( ' src', ' defer src', $tag ); // defer the script
            return str_replace( ' src', ' async src', $tag ); // OR async the script
            //return str_replace( ' src', ' async defer src', $tag ); // OR do both!
        }, 10, 2 );
    }

    if (!get_option('sided_sided_embed_placement_options')) {
        return false;
    }
    $sided_embed_placement_options = get_option('sided_sided_embed_placement_options');
    unset($sided_embed_placement_options['updated_at']);
    $sided_selected_network = get_option('sided_sided_selected_network');
    foreach ($sided_embed_placement_options as $embed_placement_option) {
        if ($embed_placement_option['active'] == 'true') {
            if ($embed_placement_option['embed_location_on_page'] == 'sidebar') {
                $url = SIDED_API_URL . '/admin/embedPlacement/getEmbed/' . $embed_placement_option['placement_id'] . '?clientId=' . $sided_selected_network;
                $args = array('timeout' => 50,
                    'method' => 'GET',
                    'headers' => array('Content-Type' => 'application/json',
                        'x-source-type' => 'wp-plugin',
                        'x-private-access-token' => get_option('sided_sided_private_access_token')),
                    'data_format' => 'body',
                );
                $response = wp_remote_post($url, $args);
                $response_arr = json_decode($response['body']);

                if ($response_arr->status == 'success') {
                    $embed_code = $response_arr->data->html;

                    add_action('dynamic_sidebar_before', function ($index, $has_widgets) use ($embed_code) {
                        return sided_kama_dynamic_sidebar_before_action($index, $has_widgets, $embed_code);
                    }, 'sidebar', 2);
                }

            } else {
                // $url = SIDED_API_URL . '/admin/embedPlacement/getEmbed/' . $embed_placement_option['placement_id'] . '?clientId=' . $sided_selected_network;
                // $args = array('timeout' => 50,
                //     'method' => 'GET',
                //     'headers' => array('Content-Type' => 'application/json',
                //         'x-source-type' => 'wp-plugin',
                //         'x-private-access-token' => get_option('sided_sided_private_access_token')),
                //     'data_format' => 'body',
                // );
                // $response = wp_remote_post($url, $args);
                // $response_arr = json_decode($response['body']);
                // if ($response_arr->status == 'success') {
                //     $embed_code = $response_arr->data->html;
                //     echo htmlentities($embed_code);die();
                //     $content = get_the_content();
                //     add_filter('the_content', function ($content) use ($embed_code) {
                //         return add_after_post_content($content, $embed_code);
                //     }, 12);
                // }
            }
        }
    }
}

add_filter('the_content', function ($content) {
    if (is_single()) {
        $sided_selected_network = get_option('sided_sided_selected_network');
        $sided_embed_placement_options = get_option('sided_sided_embed_placement_options');
        unset($sided_embed_placement_options['updated_at']);

        if (is_array($sided_embed_placement_options)) {
            foreach ($sided_embed_placement_options as $embed_placement_option) {
                if ($embed_placement_option['active'] == 'true' && $embed_placement_option['embed_location_on_page'] !== 'sidebar') {
                    return sprintf(
                        '%s<div class="sided-widget" clientId="%d" placementId="%d"></div>', 
                        $content,
                        $sided_selected_network,
                        $embed_placement_option['placement_id']
                    );
                }
            }
        }
    }

    return $content;
}, 12);

function add_send_cat_script() {
    $send_cats_to_sided = get_option('send_cats_to_sided');
    $send_tags_to_sided = get_option('send_tags_to_sided');

    if (($send_cats_to_sided == 'true' || $send_tags_to_sided == 'true') && is_single()) {
        $names = [];

        if ($send_cats_to_sided == 'true') {
            $categories = get_the_category();
            foreach ($categories as $category) {
                $names[] = $category->name;
            }
        }

        if ($send_tags_to_sided == 'true') {
            $tags = get_the_tags();
            foreach ($tags as $tag) {
                $names[] = $tag->name;
            }
        }

        if (count($names) > 0) {
            echo "<script>
                window.sidedScriptPayload = window.sidedScriptPayload || {};
                window.sidedScriptPayload.categories = ['" . implode("', '", $names) . "'];
            </script>";
        }
    }
}

add_action('wp_footer', 'add_send_cat_script');

add_action('wp_ajax_wpa_sided_initiate_script', 'sided_wpa_sided_initiate_script_callback');
add_action('wp_ajax_nopriv_wpa_sided_initiate_script', 'sided_wpa_sided_initiate_script_callback');
function sided_wpa_sided_initiate_script_callback()
{
    echo update_option('sided_sided_initiate_script', sanitize_text_field($_POST['checked']));
    wp_die();
}

add_action('wp_ajax_wpa_send_cats_to_sided', 'sided_wpa_send_cats_to_sided_callback');
add_action('wp_ajax_nopriv_wpa_send_cats_to_sided', 'sided_wpa_send_cats_to_sided_callback');
function sided_wpa_send_cats_to_sided_callback()
{
    echo update_option('send_cats_to_sided', sanitize_text_field($_POST['checked']));
    wp_die();
}

add_action('wp_ajax_wpa_send_tags_to_sided', 'sided_wpa_send_tags_to_sided_callback');
add_action('wp_ajax_nopriv_wpa_send_tags_to_sided', 'sided_wpa_send_tags_to_sided_callback');
function sided_wpa_send_tags_to_sided_callback()
{
    echo update_option('send_tags_to_sided', sanitize_text_field($_POST['checked']));
    wp_die();
}

add_action('wp_ajax_nopriv_wpa_fetch_embed_placements', 'sided_wpa_fetch_embed_placements_callback');
add_action('wp_ajax_wpa_fetch_embed_placements', 'sided_wpa_fetch_embed_placements_callback');
function sided_wpa_fetch_embed_placements_callback()
{
    update_option('sided_sided_selected_network', sanitize_text_field($_POST['selectedValue']));
    delete_option('sided_sided_embed_placement_options');

    $url = SIDED_API_URL . '/admin/embedPlacement/getEmbedPlacements/?clientId=' . sanitize_text_field($_POST['selectedValue']);
    $args = array('timeout' => 50,
        'method' => 'GET',
        'headers' => array('Content-Type' => 'application/json',
            'x-source-type' => 'wp-plugin',
            'x-private-access-token' => get_option('sided_sided_private_access_token')),
        'data_format' => 'body',
    );
    $response = wp_remote_post($url, $args);
    $response_arr = json_decode($response['body']);

    if ($response_arr->status == 'success') {
        echo json_encode($response_arr->data, true);
    }
    wp_die();
}

add_action('wp_ajax_nopriv_wpa_sided_generate_smart_poll', 'sided_wpa_sided_generate_smart_poll_callback');
add_action('wp_ajax_wpa_sided_generate_smart_poll', 'sided_wpa_sided_generate_smart_poll_callback');
function sided_wpa_sided_generate_smart_poll_callback()
{
    //print_r($_POST['SPC_keyword_val']);
    $url = SIDED_API_URL . '/admin/debate/generateDebates?count=4&url=' . sanitize_text_field($_POST['SPC_keyword_val']);
    $args = array('timeout' => 50,
        'method' => 'GET',
        'headers' => array('Content-Type' => 'application/json',
            'x-source-type' => 'wp-plugin',
            'x-private-access-token' => get_option('sided_sided_private_access_token')),
        'data_format' => 'body',
    );
    $response = wp_remote_post($url, $args);
    if( !is_wp_error( $response ) ) {
        $response_arr = json_decode($response['body']);
        if ($response_arr->status == 'success') {
            echo json_encode($response_arr->data, true);
        }
    } else {
        echo $response->get_error_message();
    }
    wp_die();
}

add_action('wp_ajax_wpa_save_embed_options', 'wpa_save_embed_options_callback');
add_action('wp_ajax_nopriv_wpa_save_embed_options', 'wpa_save_embed_options_callback');
function wpa_save_embed_options_callback()
{
    $_POST['jsonObj']['updated_at'] = current_datetime();
    echo update_option('sided_sided_embed_placement_options', $_POST['jsonObj']);
    wp_die();
}

function add_after_post_content($content, $embed_code)
{
    if (is_single()) {
        $content .= $embed_code;
    }
    return $content;
};

function sided_kama_dynamic_sidebar_before_action($index, $has_widgets, $embed_code)
{
    echo $embed_code;
}

//add_action( 'dynamic_sidebar_after', 'sided_kama_dynamic_sidebar_before_action', 10, 2 );

/**
 * Returns path to a plugin file.
 *
 * @param string $path File path relative to the plugin root directory.
 * @return string Absolute file path.
 */
function sided_plugin_path($path = '')
{
    return path_join(SIDED_PLUGIN_DIR, trim($path, '/'));
}

/**
 * Returns the URL to a plugin file.
 *
 * @param string $path File path relative to the plugin root directory.
 * @return string URL.
 */
function sided_plugin_url($path = '')
{
    $url = plugins_url($path, SIDED_PLUGIN);

    if (is_ssl()
        and 'http:' == substr($url, 0, 5)) {
        $url = 'https:' . substr($url, 5);
    }

    return $url;
}

add_action('wp_ajax_nopriv_wpa_fetch_debates', 'wpa_fetch_debates_callback');
add_action('wp_ajax_wpa_fetch_debates', 'wpa_fetch_debates_callback');
function wpa_fetch_debates_callback()
{
    $selected_network = get_option('sided_sided_selected_network') ? get_option('sided_sided_selected_network') : 1;
    //$selected_network = array_key_exists('selected_network', $_SESSION) ? $_SESSION['selected_network'] : 1;
    $searchText = isset($_GET['searchText']) ? sanitize_text_field($_GET['searchText']) : '';
    $search_qstring = isset($_GET['searchText']) && sanitize_text_field($_GET['searchText']) != '' ? '&searchText=' . sanitize_text_field($_GET['searchText']) : '';
    $results_per_page = isset($_GET['results_per_page']) ? sanitize_text_field($_GET['results_per_page']) : 999;
    $page_number = isset($_GET['paged']) ? sanitize_text_field($_GET['paged']) : 1;
    $url = SIDED_API_URL . '/debate/getDebatesList?clientId=' . $selected_network . '&perPage=' . $results_per_page . '&pageNumber=' . $page_number . $search_qstring;

    $args = array('timeout' => 50,
        'method' => 'GET',
        'headers' => array('Content-Type' => 'application/json',
            'x-source-type' => 'wp-plugin',
            'x-private-access-token' => get_option('sided_sided_private_access_token')),
        'data_format' => 'body',
    );
    $response = wp_remote_post($url, $args);
    $response_arr = json_decode($response['body']);

    if ($response_arr->status == 'success') {
        echo json_encode($response_arr->data, true);
    }
    wp_die();
}

add_action('wp_ajax_nopriv_wpa_fetch_current_debate', 'wpa_fetch_current_debate_callback');
add_action('wp_ajax_wpa_fetch_current_debate', 'wpa_fetch_current_debate_callback');
function wpa_fetch_current_debate_callback()
{
    
    $debateId = isset($_GET['debateId']) ? sanitize_text_field($_GET['debateId']) : '';
    $url = SIDED_API_URL . '/debate/'.$debateId.'?deviceId='.$debateId;

    $args = array('timeout' => 50,
        'method' => 'GET',
        'headers' => array('Content-Type' => 'application/json',
            'x-source-type' => 'wp-plugin',
            'x-private-access-token' => get_option('sided_sided_private_access_token')),
        'data_format' => 'body',
    );
    $response = wp_remote_post($url, $args);
    $response_arr = json_decode($response['body']);

    if ($response_arr->status == 'success') {
        echo json_encode($response_arr->data, true);
    }
    wp_die();
}

add_action('admin_enqueue_scripts', 'include_external_scripts');
function include_external_scripts()
{
    wp_register_script('jquery-validate.js', sided_plugin_url('assets/js/jquery.validate.min.js'), array('jquery'));
    wp_enqueue_script('jquery-validate.js');
    wp_register_script('jquery-pagination.js', sided_plugin_url('assets/js/jquery.simplePagination.min.js'), array('jquery'));
    wp_enqueue_script('jquery-pagination.js');
    // wp_register_script('moment.js',  sided_plugin_url('assets/js/moment.min.js'),array());
    wp_enqueue_script('moment');
    wp_register_script('daterangepicker.js', sided_plugin_url('assets/js/daterangepicker.min.js'), array('moment'));
    wp_enqueue_script('daterangepicker.js');
    wp_register_style('daterangepicker.css', sided_plugin_url('assets/css/daterangepicker.css'), array());
    wp_enqueue_style('daterangepicker.css');
    wp_register_style('sided-admin-post.css', sided_plugin_url('admin/css/styles.css'), array(), SIDED_VERSION, false);
    wp_enqueue_style('sided-admin-post.css');
    if (isset($_GET['sided_hide_force'])) {
        wp_register_style('sided-admin-block', sided_plugin_url('admin/css/blockstyles.css'), array(), SIDED_VERSION, false);
        wp_enqueue_style('sided-admin-block');
    }
}
