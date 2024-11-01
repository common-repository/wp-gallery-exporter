<?php
# Prevent direct access
if (!defined('ABSPATH')) die('Error!');
?>
<?php

function wpgexporter_create_download_folder() {
  try {
     wp_mkdir_p(WP_CONTENT_DIR . '/wpgexporter');
  } catch(ErrorException $ex) {
     // echo "Error: " . $ex->getMessage();
  }
}

function wpgexporter_is_zip_supported($file) {
  wpgexporter_create_download_folder();
  $zip = new ZipArchive();
  $filename = WP_CONTENT_DIR . '/wpgexporter/' . $file . '.zip';
  if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
    return false;
  } else {
    $zip->close();
    return true;
  }
}

function wpgexporter_get_galleries() {
  
  $FOO_TABLE_GALLERY   = 'posts';

  global $wpdb;
  $gallery_table = $wpdb->prefix . $FOO_TABLE_GALLERY;

  return $wpdb->get_results( "select gal.ID, gal.post_title 
                              from {$gallery_table} gal
                              where post_type = 'foogallery'" );
}

function wpgexporter_get_gallery($id) {
  
  $FOO_TABLE_GALLERYMETA   = 'postmeta';

  global $wpdb;
  $gallerymeta_table = $wpdb->prefix . $FOO_TABLE_GALLERYMETA;

  return $wpdb->get_row( $wpdb->prepare( "select meta_value
                          from {$gallerymeta_table}
                          where meta_key = 'foogallery_attachments' and post_id = %d", $id ) );
}

function wpgexporter_get_gallery_image($id) {
  
  $FOO_TABLE_PICTURES   = 'posts';

  global $wpdb;
  $picture_table = $wpdb->prefix . $FOO_TABLE_PICTURES;

  return $wpdb->get_row( $wpdb->prepare( "select guid
                          from {$picture_table}
                          where ID = %d", $id ) );
}

function wpgexporter_list_galleries() {

  $galleries = wpgexporter_get_galleries();
  $counter = 0;
  foreach ($galleries as $gallery_id) {
    $gallery = wpgexporter_get_gallery($gallery_id->ID);
    // get list of ID's form meta_value 
    $galleryItems = $gallery->meta_value;
    $galleryItems = strstr($galleryItems, '{');
    $galleryArray = explode(';',$galleryItems);
    $odd = array();
    $even = array();
    $both = array(&$even, &$odd);
    array_walk($galleryArray, function($v, $k) use ($both) { $both[$k % 2][] = $v; });
    $idList = '';
    foreach ($odd as &$value) {
      $tmp = explode(':',$value);
      if (count($tmp) == 3) { $val = $tmp[2]; } else { $val = $tmp[1]; }
      $idList = $idList . $val .',';
    }
    $idList = rtrim($idList,',');
    $idList = str_replace('"','',$idList);
    $idListArray = explode(',', $idList);
    foreach ($idListArray as &$value) {
      $images = wpgexporter_get_gallery_image(intval($value));
      // echo $images->guid;
      // echo '<br>';
    }
    
    if ($counter % 2 === 0) {
      echo '<tr>';
    } else {
      echo '<tr class="alternate">';
    }
    echo '<td><input id="' . $gallery_id->ID . '" name="wpgexportergalleryIdList[]" value="' . $gallery_id->ID . '" type="checkbox" checked="checked" /></td>';
    echo '<td style="font-size: 14px;">' . esc_html($gallery_id->post_title) . '</td>';
    echo '<td style="font-size: 14px;" align="center">' . count($idListArray) . '</td>';
    echo '</tr>'; 
    $counter++;
  }
  
}

?>

<?php if (isset($_POST['_wpnonce']) && isset($_POST['submit'])) { ?>

<div style="max-width: 600px; margin: 5px; margin-top: 15px; margin-bottom: 15px; padding: 16px; font-size: 17px; line-height: 20px; color: #222222; background-color: #fdfbd4;">
Please do not leave this page until the archive has been created. This may take several minutes if you have a large number of photos.
</div>

<?php if ( empty($_POST['wpgexportergalleryIdList']) ) { ?>

  <div style="max-width: 1200px; margin: 5px; margin-top: 15px; margin-bottom: 15px; padding-left: 7px; padding: 5px; font-size: 18px; color: #ff0000;">
  You have not selected any galleries, please go back and select at least one gallery.
  </div>

<?php } else { ?>

  <div id="divProgress" style="max-width: 1200px; margin: 5px; margin-top: 15px; margin-bottom: 15px; padding-left: 7px; padding: 5px; font-size: 16px; color: #222222;">&nbsp;</div>

  <div id="divDownload" style="max-width: 900px; margin: 5px; margin-top: 15px; margin-bottom: 15px; padding-left: 7px; padding: 5px; font-size: 20px; color: #222222;">&nbsp;</div>

  <?php
  
  $wpgexportergalleryIdListArray = $_POST['wpgexportergalleryIdList'];
  
  $archive_file = 'foogallery-export-' . time();
  
  // get total count
  $fileCount = 0;
  $galleries = wpgexporter_get_galleries();
  foreach ($galleries as $gallery_id) {
    if (in_array($gallery_id->ID, $wpgexportergalleryIdListArray)) {
      $gallery = wpgexporter_get_gallery($gallery_id->ID);
      // get list of ID's form meta_value 
      $galleryItems = $gallery->meta_value;
      $galleryItems = strstr($galleryItems, '{');
      $galleryArray = explode(';',$galleryItems);
      $odd = array();
      $even = array();
      $both = array(&$even, &$odd);
      array_walk($galleryArray, function($v, $k) use ($both) { $both[$k % 2][] = $v; });
      $idList = '';
      foreach ($odd as &$value) {
        $tmp = explode(':',$value);
        if (count($tmp) == 3) { $val = $tmp[2]; } else { $val = $tmp[1]; }
        $idList = $idList . $val .',';
      }
      $idList = rtrim($idList,',');
      $idList = str_replace('"','',$idList);
      $idListArray = explode(',', $idList);

      foreach ($idListArray as &$value) {
        $fileCount++;
      }
    } 
  }

  ?> 
  
  <script>
  jQuery(document).ready(function($) {

  var ajaxes  = [

  <?php
  $count = 0;
  $galleries = wpgexporter_get_galleries();
  foreach ($galleries as $gallery_id) {
    if (in_array($gallery_id->ID, $wpgexportergalleryIdListArray)) {
    $gallery = wpgexporter_get_gallery($gallery_id->ID);
    // get list of ID's form meta_value 
    $galleryItems = $gallery->meta_value;
    $galleryItems = strstr($galleryItems, '{');
    $galleryArray = explode(';',$galleryItems);
    $odd = array();
    $even = array();
    $both = array(&$even, &$odd);
    array_walk($galleryArray, function($v, $k) use ($both) { $both[$k % 2][] = $v; });
    $idList = '';
    foreach ($odd as &$value) {
      $tmp = explode(':',$value);
      if (count($tmp) == 3) { $val = $tmp[2]; } else { $val = $tmp[1]; }
      $idList = $idList . $val .',';
    }
    $idList = rtrim($idList,',');
    $idList = str_replace('"','',$idList);
    $idListArray = explode(',', $idList);

    foreach ($idListArray as &$value) {
      $images = wpgexporter_get_gallery_image(intval($value));
      $picture_url = $images->guid;
      $file_folder = $gallery_id->post_title;
      $file_zip = basename(parse_url($picture_url)["path"]);
      $count++;
  ?>
        {
        data :
        {
        'action': 'wpgalleryexporter_ajax_copy',
        'count': '<?php echo $count; ?>',
        'file_count': '<?php echo $fileCount; ?>',
        'archive_file': '<?php echo $archive_file; ?>',
        'file_local': '<?php echo urlencode($picture_url); ?>',
        'file_folder': '<?php echo urlencode($file_folder); ?>',
        'file_zip': '<?php echo urlencode($file_zip); ?>',
        }
        },
  <?php
      }
    } 
  }

  
  ?>

  ],
  current = 0;

  function do_ajax() {

      //check to make sure there are more requests to make
      if (current < ajaxes.length) {

          //make the AJAX request with the given info from the array of objects
         $.ajax({
            type: "POST",
            url: ajaxurl,
            data: ajaxes[current].data,
            success: function(response) {
              if (response == 1) {
                $('#divProgress').html('<span style="font-size: 20px; line-height: 32px;">Please wait while the ZIP archive is being created ...</span>');

                var timeleft = <?php echo intval(max(10,(65*$fileCount/1000))); ?>; // estimate 65 secs per 1000 files (from filecount)
                var downloadTimer = setInterval(function(){
                  $('#divProgress').html('<span style="font-size: 20px; line-height: 32px;">Please wait while the ZIP archive is being created ...</span><br />Estimated time remaining: <span style="color: #ff0000;">' + timeleft + ' secs</span>');
                  timeleft -= 1;
                  if(timeleft <= 0)
                    clearInterval(downloadTimer);
                }, 1000);


                // launch new AJAX to create ZIP with time to update message
                // when complete, show final message

                var data2 = {
                'action': 'wpgalleryexporter_ajax_zip',
                'archive_file': '<?php echo urlencode($archive_file); ?>',
                };

                $.ajax({
                  type: "POST",
                  url: ajaxurl,
                  data: data2,
                  success: function(response2) {
                    // alert(response2);
                    clearInterval(downloadTimer);
                    $('#divProgress').html('Your ZIP archive is ready, download your ZIP archive by clicking the link below');
                    $('#divDownload').html('<a href="<?php echo WP_CONTENT_URL . '/wpgexporter/' . $archive_file; ?>.zip"><?php echo $archive_file; ?>.zip</a>' + ' &nbsp; <span style="color: #999999;">&#8211; ' + response2 + '</span>');
                  },
                  error: function() {
                    // alert('Error, ignore errors.');
                  }
                });

              } else {
                $('#divProgress').html(response);
              }
            },
            complete: function() {
              current++;
              do_ajax();
            },
            error: function() {
              console.log('error');
              // alert('Error, ignore errors.');
            }
          });
      }
  }

  do_ajax();

  });
  </script>

  
<?php } ?>

<?php } elseif ( !wpgexporter_is_zip_supported('test.zip') ) { ?>

<div style="max-width: 710px; margin: 5px; margin-top: 15px; margin-bottom: 15px; padding: 15px; font-size: 17px; line-height: 20px; color: #ff0000; background-color: #ffffff;">Sorry, your WordPress installation does not support creating zip files.</div>

<?php } else { ?>

<div style="max-width: 710px; margin: 5px; margin-top: 15px; margin-bottom: 15px; padding: 15px; font-size: 17px; line-height: 20px; color: #222222; background-color: #fdfbd4;">Please select/unselect the FooGallery galleries you would like exported and click the <b>Start&nbsp;Export</b> button to create a ZIP file containing all your images.</div>

<script>
function selectAll() {
    var checkboxes = document.querySelectorAll('input[type="checkbox"]');
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = true;
    }
}
function deselectAll() {
    var checkboxes = document.querySelectorAll('input[type="checkbox"]');
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = false;
    }
}
</script>

<form name="form1" method="post" action="">
  
<div style="padding: 5px; font-size: 16px;">Select <a style="text-decoration: none;" href="#" onclick="selectAll();">All</a> | <a style="text-decoration: none;" href="#" onclick="deselectAll();">None</a></div>

<?php wp_nonce_field('form-settings'); ?>
<table class="wp-list-table widefat" style="max-width: 750px;" cellspacing="0">
<thead>
<tr>
  <th scope="col" class="manage-column" style="width: 1%;"></th>
  <th scope="col" class="manage-column" style="font-size: 16px; width: 70%;">FooGallery Galleries</th>
  <th scope="col" class="manage-column" style="font-size: 16px; text-align: center;">Images</th>
</tr>
</thead>
<tbody>

<?php wpgexporter_list_galleries(); ?>
<tr><td></td><td colspan="3"><?php submit_button('&nbsp;&nbsp;Start Export&nbsp;&nbsp;'); ?></td></tr>

</tbody>
</table>
</form>

<?php
}
?>

<br /><br /><br />
