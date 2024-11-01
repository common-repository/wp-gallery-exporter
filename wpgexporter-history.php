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

function wpgexporter_archive_list() {

    $files = array();
    $dir = new DirectoryIterator(WP_CONTENT_DIR . '/wpgexporter/');
    foreach ($dir as $fileinfo) {
      if ($fileinfo != "." && $fileinfo != "..") {
        $ext = pathinfo($fileinfo);
        if ($ext['extension'] == 'zip') {
          $files[$fileinfo->getMTime()] = $fileinfo->getFilename();
        }
      }
    }
    krsort($files);

    $i = 1;
    $counter = 0;
    foreach($files as $file) {
      
      $lastModified = date('M j, Y g:i:s A',filemtime(WP_CONTENT_DIR . '/wpgexporter/' . $file));
      
      $filename = WP_CONTENT_DIR . '/wpgexporter/' . $file;
      $size = filesize($filename);
      $units = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
      $step = 1024;
      $k = 0;
      while (($size / $step) > 0.9) {
          $size = $size / $step;
          $k++;
      }
      
      if ($counter % 2 === 0) {
        echo '<tr>';
      } else {
        echo '<tr class="alternate">';
      }
      echo '<td style="font-size: 13px; padding: 3px; padding-left: 10px; padding-right: 10px;" align="center">' . $i . '.</td>';
      echo '<td style="font-size: 13px; padding: 3px;"><a href="' . WP_CONTENT_URL . '/wpgexporter/' . $file .'">' . $file . '</a> <span style="color: #999999;">&#8211; ' . round($size, 2).$units[$k] . '</span></td>';
      echo '<td style="font-size: 13px; padding: 3px;" align="center" nowrap>' . $lastModified . '</td>';
      echo '</tr>';
      $i++;
      $counter++;
      
    }
}

function wpgexporter_delete_history($path) {
  // Open the source directory to read in files
  $i = new DirectoryIterator($path);
  foreach($i as $f) {
      if($f->isFile()) {
          unlink($f->getRealPath());
      } else if(!$f->isDot() && $f->isDir()) {
          wpgexporter_delete_history($f->getRealPath());
      }
  }
  rmdir($path);
}

if (isset($_POST['_wpnonce']) && isset($_POST['submit'])) { 
  wpgexporter_delete_history(WP_CONTENT_DIR . '/wpgexporter/');
}

wpgexporter_create_download_folder();

?>


<div style="max-width: 710px; margin: 5px; margin-top: 15px; margin-bottom: 15px; padding: 15px; font-size: 17px; line-height: 20px; color: #222222; background-color: #fdfbd4;">Clicking the <b>Delete&nbsp;All</b> button will delete all these files from your server (no undo).</div>
  
<form name="form1" method="post" action="">
<?php wp_nonce_field('form-settings'); ?>
<table class="wp-list-table widefat" style="max-width: 750px;" cellspacing="0">
<thead>
<tr>
  <th scope="col" class="manage-column" style="width: 3%;"></th>
  <th scope="col" class="manage-column" style="font-size: 16px; width: 70%;">ZIP Archive</th>
  <th scope="col" class="manage-column" style="font-size: 16px; text-align: center;">Created</th>
</tr>
</thead>
<tbody>

<?php wpgexporter_archive_list(); ?>
  <tr><td></td><td colspan="2"><?php submit_button('&nbsp;&nbsp;Delete All&nbsp;&nbsp;'); ?></td></tr>

</tbody>
</table>
</form>
