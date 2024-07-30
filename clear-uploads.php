<?php
/**
 * Plugin Name: WordPress Media Cleaner
 * Plugin URI: https://abovebits.com/
 * Description: Media Cleaner for WordPress is an essential tool designed to streamline your WordPress media library. It efficiently removes unused media files and repairs broken links, ensuring your library remains clutter-free. The built-in trash functionality allows you to review and verify changes before final deletion. Additionally, Media Cleaner leverages intelligent analysis to maintain compatibility with various plugins.
 * Version: 0.21
 * Author: abovebits.com
 * Author URI: https://abovebits.com/
 */

define('CU_ADMIN_SETTINGS_LINK', 'delete-unused-images-settings');

include_once ('functions.php');

//////////////////////////////

add_action('admin_menu', 'unused_images_settings_page');

function unused_images_settings_page() {
    add_options_page(
        'Wordpress Media Cleaner Settings Page',
        'Media Cleaner Settings',
        'manage_options',
        CU_ADMIN_SETTINGS_LINK,
        'unused_images_settings_page_content'
    );
}

function cu_add_setting_link($links) {
    $settings_link = menu_page_url(CU_ADMIN_SETTINGS_LINK, false);
    $settings_link = '<a href="'.$settings_link.'">Settings</a>';
    array_push($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'cu_add_setting_link');

function unused_images_settings_page_content() {
    include_once (plugin_dir_path(__FILE__) . '/templates/admin-views.php');
}


//////////////////////////////

add_action('wp_ajax_cu_scan', 'cu_scan_callback');
function cu_scan_callback(){
//    $all_images_for_uploads = cu_get_all_images_for_uploads();
//    $all_images_for_uploads_original = cu_get_all_images_without_sizes($all_images_for_uploads);
//    $all_used_images = cu_get_all_used_images();
//
//    $unused_images = array_diff($all_images_for_uploads_original, $all_used_images);
//
//    update_option('cu_get_all_unused_images', $unused_images);
    $all_unused_images = cu_scan_update();

    wp_send_json(
        array(
            //'all_images_for_uploads' => count($all_images_for_uploads),
            //'all_images_for_uploads_original' => count($all_images_for_uploads_original),
            //'all_used_images' => count($all_used_images),
            'unused_images' => count($all_unused_images),
        )
    );
}
//////////////////////////////

add_action('wp_ajax_cu_unused_images', 'cu_unused_images_callback');
function cu_unused_images_callback(){
    $all_unused_images = get_option('cu_get_all_unused_images', false);

    if(!$all_unused_images){
        wp_send_json(
            array()
        );
    }

    foreach ($all_unused_images AS $i=>$image_path) {
        $all_unused_images[$i] = str_replace($_SERVER['DOCUMENT_ROOT'], get_site_url(), $image_path);
    }

    $all_unused_images = array_values($all_unused_images);

    wp_send_json(
        array(
            'images' => (array)$all_unused_images,
            'count' => count($all_unused_images),
        )
    );

}
//////////////////////////////

add_action('wp_ajax_cu_delete', 'cu_delete_callback');
function cu_delete_callback(){
    if(isset($_POST['data']) && !empty($_POST['data'])){
        $needs_delete_images = $_POST['data'];
        $all_images_for_uploads = cu_get_all_images_for_uploads();
        $needs_delete_images = array_values($needs_delete_images);

        foreach ($needs_delete_images AS $i => $needs_delete_image){
            if($i < 100){
                cu_delete_image($needs_delete_image, $all_images_for_uploads);
                unset($needs_delete_images[$i]);
            }
        }
        $needs_delete_images = array_values($needs_delete_images);
        $all_unused_images = cu_scan_update();

        wp_send_json(
            array(
                'cu_get_all_unused_images' => count($all_unused_images),
                'needs_delete_images' => count($needs_delete_images),
                'data' => $needs_delete_images,
            )
        );

    }
    wp_send_json(
        array(
            'end' => true
        )
    );
}

//////////////////////////////

add_action('wp_ajax_cu_trash', 'cu_trash_callback');
function cu_trash_callback(){
    if(isset($_POST['data']) && !empty($_POST['data'])){
        $needs_delete_images = $_POST['data'];
        $all_images_for_uploads = cu_get_all_images_for_uploads();
        $all_unused_images = get_option('cu_get_all_unused_images', false);
        foreach ($needs_delete_images AS $needs_delete_image){
            $all_unused_images = cu_trash_image($needs_delete_image, $all_images_for_uploads, $all_unused_images);
        }
        update_option('cu_get_all_unused_images', $all_unused_images);

        wp_send_json(
            array(
                'cu_get_all_unused_images' => count($all_unused_images)
            )
        );

    }
    wp_send_json(
        array(
            'end' => true
        )
    );
}
//////////////////////////////

add_action('wp_ajax_cu_restore', 'cu_restore_callback');
function cu_restore_callback(){
    if(isset($_POST['data']) && !empty($_POST['data'])){
        if($_POST['data'] == 'all'){
            cu_restore_all();
        }
        $needs_delete_images = $_POST['data'];
        $all_images_for_uploads = cu_get_all_images_for_uploads();
        $all_unused_images = get_option('cu_get_all_unused_images', false);
        foreach ($needs_delete_images AS $needs_delete_image){
            $all_unused_images = cu_restore_image($needs_delete_image, $all_images_for_uploads, $all_unused_images);
        }
        update_option('cu_get_all_unused_images', $all_unused_images);

        wp_send_json(
            array(
                'cu_get_all_unused_images' => count($all_unused_images)
            )
        );

    }
    wp_send_json(
        array(
            'end' => true
        )
    );
}

//////////////////////////////

