<?php
/*
Plugin Name: WP Gallery Exporter
Plugin URI: https://wpcloudgallery.com
Description: Export your NextGen, FooGallery and Envira galleries to your computer.
Author: wpcloudgallery.com
Author URI: https://wpcloudgallery.com
Text Domain: wpcloudgallery.com
Version: 1.3
*/

# Prevent direct access
if (!defined('ABSPATH')) die('Error!');

# Allow longer execution time needed for generating zip files
set_time_limit(300);

# Create wpgalleryexporter settings menu for admin
add_action( 'admin_menu', 'wpgalleryexporter_create_menu' );
add_action( 'network_admin_menu', 'wpgalleryexporter_network_admin_create_menu' );
add_action( 'wp_ajax_wpgalleryexporter_ajax_copy', 'wpgalleryexporter_ajax_copy' );
add_action( 'wp_ajax_wpgalleryexporter_ajax_zip', 'wpgalleryexporter_ajax_zip' );

# Ajax copy function
function wpgalleryexporter_ajax_copy() {
  $count = intval($_POST['count']);
  $file_count = intval($_POST['file_count']);
  $archive_file = sanitize_text_field($_POST['archive_file']);
	$file_local = sanitize_text_field(urldecode($_POST['file_local']));
  $file_folder = sanitize_file_name(urldecode($_POST['file_folder']));
  $file_zip = sanitize_file_name(urldecode($_POST['file_zip']));
  $file_zip_path = str_replace('-',' ',$file_folder) . '/' . $file_zip; // allow spaces in filename
  wpgexporter_copy_file($archive_file, $file_local, $file_zip_path);
  if ($count < $file_count) {
    echo 'Copying (' . $count . '/' . $file_count . ') <span style="color: #1769ff;">' . str_replace('-',' ',$file_folder) . '</span> / <span style="color: #777777;">' . $file_zip . '</span>';
  } else {
    echo '1';
  }
	wp_die(); // this is required to terminate immediately and return a proper response
}

# Ajax zip function
function wpgalleryexporter_ajax_zip() {
  $archive_file = $_POST['archive_file'];
  wpgexporter_create_zip($archive_file);
  $filename = WP_CONTENT_DIR . '/wpgexporter/' . $archive_file . '.zip';
  $size = filesize($filename);
  $units = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
  $step = 1024;
  $i = 0;
  while (($size / $step) > 0.9) {
      $size = $size / $step;
      $i++;
  }
  echo round($size, 2).$units[$i];
	wp_die(); // this is required to terminate immediately and return a proper response
}

function wpgexporter_copy_file($archiveFile, $file, $relpath) {
  // copy files to tmp directory, then zip it at the end
  try {
     wp_mkdir_p(WP_CONTENT_DIR . '/wpgexporter/'.$archiveFile.'/'.dirname($relpath));
     copy($file, WP_CONTENT_DIR . '/wpgexporter/'.$archiveFile.'/'.$relpath);
  } catch(ErrorException $ex) {
     // echo "Error: " . $ex->getMessage();
  }
}

function wpgexporter_rmrf($dir) {
  foreach (glob($dir) as $file) {
   if (is_dir($file)) {
       wpgexporter_rmrf("{$file}/*");
       rmdir($file);
   } else {
       unlink($file);
   }
  }
 }

function wpgexporter_create_zip($archiveFile) {
  $folder = WP_CONTENT_DIR . '/wpgexporter/' . $archiveFile;
  $filename = WP_CONTENT_DIR . '/wpgexporter/' . $archiveFile . '.zip';
  $zip = new ZipArchive();
  $zip->open($filename, ZipArchive::CREATE);
  
  $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder), RecursiveIteratorIterator::LEAVES_ONLY);
  
  foreach ($files as $name => $file)
  {
      // Skip directories (they would be added automatically)
      if (!$file->isDir())
      {
          // Get real and relative path for current file
          $filePath = $file->getRealPath();
          $relativePath = substr($filePath, strlen($folder) + 1);

          // Add current file to archive
          $zip->addFile($filePath, $relativePath);
      }
  }
  $zip->close();
  wpgexporter_rmrf($folder); // delete temporary files

}

# Create new top level menu for sites
function wpgalleryexporter_create_menu() {
  
  $icon = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDIzLjAuMSwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHZpZXdCb3g9IjAgMCA1MDAgNTAwIiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCA1MDAgNTAwOyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+CjxwYXRoIGQ9Ik0zOTUuNTksMjAxLjE2QzM5Mi40NywxMjMuNTUsMzI4LjM2LDYxLjM4LDI1MCw2MS4zOHMtMTQyLjQ3LDYyLjE4LTE0NS41OSwxMzkuNzhjLTUyLjg4LDkuMjgtOTMuMiw1NS41NC05My4yLDExMS4wNAoJYzAsNjIuMTcsNTAuNTgsMTEyLjc0LDExMi43NCwxMTIuNzRoMjUyLjExYzYyLjE2LDAsMTEyLjczLTUwLjU4LDExMi43My0xMTIuNzRDNDg4Ljc5LDI1Ni42OSw0NDguNDcsMjEwLjQzLDM5NS41OSwyMDEuMTZ6CgkgTTI1MCwzNTguNTRsLTg2LjU2LTg2LjU2bDMwLjM1LTMwLjM1bDM0Ljc0LDM0Ljczdi05NS4wNGg0Mi45M3Y5NS4wNGwzNC43NC0zNC43M2wzMC4zNSwzMC4zNUwyNTAsMzU4LjU0eiIvPgo8L3N2Zz4K';
  
  add_menu_page('WP Gallery Exporter Options', 'GalleryExporter', 'install_plugins', 'wpgalleryexporter_settings_page', 'wpgalleryexporter_settings_page', $icon, 25);
}

# Create new top level menu for network admin
function wpgalleryexporter_network_admin_create_menu() {
  
  $icon = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDIzLjAuMSwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHZpZXdCb3g9IjAgMCA1MDAgNTAwIiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCA1MDAgNTAwOyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+CjxwYXRoIGQ9Ik0zOTUuNTksMjAxLjE2QzM5Mi40NywxMjMuNTUsMzI4LjM2LDYxLjM4LDI1MCw2MS4zOHMtMTQyLjQ3LDYyLjE4LTE0NS41OSwxMzkuNzhjLTUyLjg4LDkuMjgtOTMuMiw1NS41NC05My4yLDExMS4wNAoJYzAsNjIuMTcsNTAuNTgsMTEyLjc0LDExMi43NCwxMTIuNzRoMjUyLjExYzYyLjE2LDAsMTEyLjczLTUwLjU4LDExMi43My0xMTIuNzRDNDg4Ljc5LDI1Ni42OSw0NDguNDcsMjEwLjQzLDM5NS41OSwyMDEuMTZ6CgkgTTI1MCwzNTguNTRsLTg2LjU2LTg2LjU2bDMwLjM1LTMwLjM1bDM0Ljc0LDM0Ljczdi05NS4wNGg0Mi45M3Y5NS4wNGwzNC43NC0zNC43M2wzMC4zNSwzMC4zNUwyNTAsMzU4LjU0eiIvPgo8L3N2Zz4K';
  
  add_menu_page('WP Gallery Exporter Options', 'GalleryExporter', 'manage_options', 'wpgalleryexporter_settings_page', 'wpgalleryexporter_settings_page', $icon, 25);
  
}

function wpgalleryexporter_update_option($name, $value) {
    return is_multisite() ? update_site_option($name, $value) : update_option($name, $value);
}

# include subsections
include 'wpgexporter-main.php';

?>