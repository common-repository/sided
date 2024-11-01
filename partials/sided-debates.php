<?php 

include 'includes/sided-authenticate-apikey.php';
if($status_aat == 'No Token'){
  echo '<script>
          window.location = "'.get_site_url().'/wp-admin/admin.php?page=dashboard";
        </script>';
  exit;
}
if($status_aat != 'Valid Token'){
  echo '<script>
          window.location = "'.get_site_url().'/wp-admin/admin.php?page=settings";
        </script>';
  exit;
}

if(isset($_POST['delete-debate-id'])){
  $url_dd = SIDED_API_URL.'/debate/destroy?debateId='.sanitize_text_field($_POST['delete-debate-id']);
  $header_dd = array( 'timeout' => 50,
            'method'  => 'DELETE',
            'headers' => array( 
                                'x-source-type' => 'wp-plugin',
                               'x-private-access-token'=> $sided_private_access_token ) 
             );
  $response_dd = wp_remote_post( $url_dd, $header_dd);
  $response_dd_arr = json_decode($response_dd['body']);
  if($response_dd_arr->status == 'success'){
    echo '<h3 style="color: green !important">'.esc_html($response_dd_arr->message).'</h3>';
  } else {
    echo '<h3 style="color: red !important">There is some problem in deleting this debate. Please try again later.</h3>';
  }
}

include 'includes/sided-common-header.php';
$searchText = isset($_GET['searchText']) ? sanitize_text_field($_GET['searchText']) : '';
$search_qstring = isset($_GET['searchText']) && $_GET['searchText'] != '' ? '&searchText='.sanitize_text_field($_GET['searchText']) : '';
$results_per_page = 25;
$page_number = isset($_GET['paged']) ? sanitize_text_field($_GET['paged']) : 1;
  $url = SIDED_API_URL.'/debate/getDebatesList?clientId='.$selected_network.'&perPage='.$results_per_page.'&pageNumber='.$page_number.$search_qstring;
	$header = array( 'timeout' => 50,
            'headers' => array( 'x-source-type' => 'wp-plugin',
                               'x-private-access-token'=> $sided_private_access_token ) 
             );
	$response = wp_remote_get( $url, $header);
	$response_arr = json_decode($response['body']);
	if($response_arr->status === 'success'){
		$debates_list = $response_arr->data->rows;
    $debates_count = $response_arr->data->count;
    $active_debates_count = $response_arr->data->activeDebatesCount;
echo '<div class="settings-header">
  <div class="inline-content align-items-center p-4 common-form">
    <img class="pe-4" src="'.plugin_dir_url( __FILE__ ).'includes/imgs/sided_favicon.jpg"/>
    <h2 class="ps-2 pe-4">'.esc_html($debates_count).'<small>Total Polls</small></h2>
    <h2 class="ps-2 pe-4">'.esc_html($active_debates_count).'<small>Active Polls</small></h2>
    <h3 class="ps-2">2 weeks ago<br><small>Last update</small></h2>
    <a style="position: absolute; right: 20px;" class="btn btn-primary" href="admin.php?page=create-debate">+ Add New</a>
  </div>
</div>';

  echo '<div class="debate-list-wrap">
  <form methor="get" action="" class="search-debate inline-content">
    <input type="hidden" name="page" value="'.esc_attr($_GET['page']).'">
    <div style="position: relative; display: inline-block;">
      <input type="text" placeholder="Search for a poll" name="searchText" value="'.esc_attr($searchText).'">
      <button type="submit" class="search-btn">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M6.5 0C8.22391 0 9.87721 0.684819 11.0962 1.90381C12.3152 3.12279 13 4.77609 13 6.5C13 8.11 12.41 9.59 11.44 10.73L11.71 11H12.5L17.5 16L16 17.5L11 12.5V11.71L10.73 11.44C9.59 12.41 8.11 13 6.5 13C4.77609 13 3.12279 12.3152 1.90381 11.0962C0.684819 9.87721 0 8.22391 0 6.5C0 4.77609 0.684819 3.12279 1.90381 1.90381C3.12279 0.684819 4.77609 0 6.5 0ZM6.5 2C4 2 2 4 2 6.5C2 9 4 11 6.5 11C9 11 11 9 11 6.5C11 4 9 2 6.5 2Z" fill="#A8A8A8"/>
        </svg>
      </button>
    </div>';
    echo $searchText != '' ? '<a href="admin.php?page=dashboard" class="btn btn-secondary">Reset</a>' : '';
  echo '</form>
  <table class="debates tableCommon table">
     <thead>
        <tr>
           <th>POLLS LIST</th>
           <th>STATUS</th>
           <th>PUBLISHED</th>
           <th>EMBED CODE</th>
           <th></th>
        </tr>
     </thead>
     <tbody>';
        if(empty($debates_list)){
          echo '<tr><td colspan="4"><h3 class="textCenter">No Poll</h3></td></tr>';
        }
        foreach ($debates_list as $debate) {
          $next_page = $debate->isDraft == '1' ? 'edit-draft' : 'edit-debate';
          $status = $debate->isDraft == '1' ? 'Draft' : ($debate->isSchedule == '1' ? 'Scheduled' : ($debate->isActive == '1' ? 'Active' : 'Closed'));
          $disabled_copy_shortcode = $debate->isDraft == '1' ? 'disabled' : '';
           echo '<tr><td>
              <div><b>'.esc_html($debate->thesis).'</b></div>
              <div><a class="customAnchor" href="'.esc_url($debate->debateUrl).'" target="_blank">'.esc_html($debate->debateUrl).'</a></div>
           </td>
           <td>'.esc_html($status).'</td>
           <td>'.date_format(date_create($debate->startedAt) , 'm/d/y').'</td>
           <td><a class="copyEmbedShortcode '.esc_attr($disabled_copy_shortcode).'" data-debId="'.esc_attr($debate->id).'" href="javascript:void(0);">Copy</a></td>
           <td>
            <div class="debate-list-action">
              <!--a class="btn btn-primary me-2" href="admin.php?page='.esc_attr($next_page).'&debate='.esc_attr($debate->id).'">View</a-->
              <a class="btn btn-primary me-2" href="admin.php?page=dashboard&debate='.esc_attr($debate->id).'&action='.esc_attr($next_page).'">View</a>
              <form method="post">
                <input type="hidden" value="'.esc_attr($debate->id).'" name="delete-debate-id">
                <input type="submit" class="btn btn-secondary" onclick="return confirm(\'Are you sure you want to delete this poll?\');" value="Delete">
              </form>
            </div>
           </td>
        </tr>';
        }
     echo '</tbody>
  </table><input readonly type="text" value="" id="copyShortcodeField"><div id="debatesPagination"></div></div>';
  }
  ?>
  
  <script type="text/javascript">
    (function($) {
    $('#debatesPagination').pagination({
        items: "<?php echo esc_js($debates_count); ?>",
        itemsOnPage: 25,
        currentPage: "<?php echo esc_js($page_number); ?>",
        prevText: "&laquo;",
        nextText: "&raquo;",
        useAnchors:false,
        cssStyle: 'light-theme',
        onPageClick:function(pageNumber, event) {
          window.location.href="admin.php?page=dashboard&paged="+pageNumber+"<?php echo esc_js($search_qstring); ?>";
        },
      });

    $('body').on('click','.copyEmbedShortcode', function(e){
      var debateId = e.target.dataset.debid;
      $('.notification').html('<p>Copying...</p>');
      $('#copyShortcodeField').val();

      $('#copyShortcodeField').val('<div class="sided-widget" debateId="'+debateId+'"></div>');
      if($('#copyShortcodeField').value !== ''){
          $('#copyShortcodeField').select();
          document.execCommand('copy');
          $('.notification').html('<p class="success">Embed code copied to clipboard!</p>');
      } else {
        $('.notification').html('<p class="error">There is some problem in copying embed shortcode. Please reload the page and try again!</p>');
      }

      const myTimeout = setTimeout( function(){
        $('.notification').html('');
      }, 3000);
      
      /*$.ajax({
        url: "<?php echo esc_js(SIDED_API_URL); ?>/debate/getEmbed/"+debateId,
         headers: { 'x-source-type' : 'wp-plugin',
                                 'x-private-access-token': "<?php echo esc_js($sided_private_access_token); ?>" },
        context: document.body,
        success: function (data) {
            var embedCode = data.data;
            $('#copyShortcodeField').val(embedCode.wordpress);

            if($('#copyShortcodeField').value !== ''){
                $('#copyShortcodeField').select();
                document.execCommand('copy');
                $('.notification').html('<p class="success">Embed shortcode copied to clipboard!</p>');
            } else {
              $('.notification').html('<p class="error">There is some problem in copying embed shortcode. Please reload the page and try again!</p>');
            }

            const myTimeout = setTimeout( function(){
              $('.notification').html('');
            }, 3000);

        },
      });*/
    })
      }(jQuery));
  </script>

  <!-- 
    <script type="text/javascript">
    $.ajax({
      url: "https://stage-api.sided.co/debate/getDebatesList?clientId=<?php echo $selected_network; ?>&perPage=<?php echo $results_per_page; ?>&pageNumber=<?php echo $page_number.$search_qstring; ?>",
       headers: { 'x-source-type' : 'wp-plugin',
                               'x-private-access-token': "<?php echo $sided_private_access_token; ?>" },
      context: document.body,
      success: function (data) {
          var deb_data = data.data.rows;
          var debate_list = '';
          for (var i=0; i<deb_data.length; i++) {
            var next_page = deb_data[i].isDraft == '1' ? 'edit-draft' : 'edit-debate';
            var status = deb_data[i].isDraft == '1' ? 'Draft' : (deb_data[i].isActive == '1' ? 'Active' : 'Closed');

            debate_list = debate_list+'<tr><td><div><b>'+deb_data[i].thesis+'</b></div><div><a class="customAnchor" href="'+deb_data[i].debateUrl+'" target="_blank">'+deb_data[i].debateUrl+'</a></div></td><td>'+status+'</td><td>'+moment(deb_data[i].startedAt).format('MM/DD/YY')+'</td><td><div class="debate-list-action"><a class="btn btn-primary me-2" href="admin.php?page='+next_page+'&amp;debate='+deb_data[i].id+'">View</a><form method="post"><input type="hidden" value="18013" name="delete-debate-id"><input type="submit" class="btn btn-secondary" value="Delete"></form></div></td></tr>';
          }
          $('.debates tbody').html(debate_list);
      },
    });
  </script> -->