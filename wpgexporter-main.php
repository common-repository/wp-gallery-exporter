<?php
# Prevent direct access
if (!defined('ABSPATH')) die('Error!');
?>
<?php function wpgalleryexporter_settings_page() { ?>

<div id="wpgalleryexporter_admin" class="wrap">

<div style="padding-bottom: 10px;">
<h1 style="font-size: 26px;">WP Gallery Exporter for NextGen, Envira, FooGallery</h1>
</div>

<?php $wpgalleryexporter_active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'about'; ?>

<h2 class="nav-tab-wrapper">

<a href="?page=wpgalleryexporter_settings_page&amp;tab=nextgen" class="nav-tab <?php echo $wpgalleryexporter_active_tab == 'nextgen' ? 'nav-tab-active' : ''; ?>">NextGen</a>
<a href="?page=wpgalleryexporter_settings_page&amp;tab=envira" class="nav-tab <?php echo $wpgalleryexporter_active_tab == 'envira' ? 'nav-tab-active' : ''; ?>">Envira</a>
<a href="?page=wpgalleryexporter_settings_page&amp;tab=foogallery" class="nav-tab <?php echo $wpgalleryexporter_active_tab == 'foogallery' ? 'nav-tab-active' : ''; ?>">FooGallery</a>
<a href="?page=wpgalleryexporter_settings_page&amp;tab=history" style="margin-left: 25px;" class="nav-tab <?php echo $wpgalleryexporter_active_tab == 'history' ? 'nav-tab-active' : ''; ?>">History</a>
<a href="?page=wpgalleryexporter_settings_page&amp;tab=about" class="nav-tab <?php echo $wpgalleryexporter_active_tab == 'about' ? 'nav-tab-active' : ''; ?>">About / FAQ</a>

</h2>

<!-- NextGen Tab -->
<?php if( $wpgalleryexporter_active_tab == 'nextgen' ) {
  include 'wpgexporter-nextgen.php';  
} 
?>

<!-- Envira Tab -->
<?php if( $wpgalleryexporter_active_tab == 'envira' ) {
  include 'wpgexporter-envira.php';  
} 
?>

<!-- FooGallery Tab -->
<?php if( $wpgalleryexporter_active_tab == 'foogallery' ) {
  include 'wpgexporter-foogallery.php';  
} 
?>

<!-- History Tab -->
<?php if( $wpgalleryexporter_active_tab == 'history' ) {
  include 'wpgexporter-history.php';  
} 
?>

<!-- About Tab -->
<?php if( $wpgalleryexporter_active_tab == 'about' ) {
  include 'wpgexporter-about.php';  
} 
?>

</div>
<?php } ?>