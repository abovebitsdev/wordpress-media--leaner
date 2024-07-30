<?php
if (!defined('CU_ADMIN_SETTINGS_LINK')) exit;

function cu_get_all_images_for_uploads(){
    $upload_dir = wp_upload_dir();
    $upload_path = $upload_dir['basedir'];
    $uploaded_files = scandir($upload_path);
    $year_folders = array_filter($uploaded_files, function($file) {
        return preg_match('/^\d{4}$/', $file);
    });
    $all_images = array();
    foreach ($year_folders as $year_folder) {
        $year_path = $upload_path . '/' . $year_folder;
        $month_folders = array_diff(scandir($year_path), array('.', '..'));
        foreach ($month_folders as $month_folder) {
            $month_path = $year_path . '/' . $month_folder;
            $images = glob($month_path . '/*.jpg');
            $images = array_merge($images, glob($month_path . '/*.jpeg'));
            $images = array_merge($images, glob($month_path . '/*.png'));
            $images = array_merge($images, glob($month_path . '/*.gif'));
            $images = array_merge($images, glob($month_path . '/*.bmp'));
            $images = array_merge($images, glob($month_path . '/*.tiff'));
            $images = array_merge($images, glob($month_path . '/*.tif'));
            $images = array_merge($images, glob($month_path . '/*.webp'));
            $images = array_merge($images, glob($month_path . '/*.trash'));
            $all_images = array_merge($all_images, $images);
        }
    }
    return $all_images;
}

function cu_get_all_used_images(){
    $all_used_images = array();
    $all_featured_images = cu_get_all_featured_images();
    $all_featured_images_without_sizes = cu_get_all_images_without_sizes($all_featured_images);
    $all_used_images = array_merge($all_used_images, $all_featured_images_without_sizes);

    $all_page_images = cu_get_all_block_images();
    $all_page_images_without_sizes = cu_get_all_images_without_sizes($all_page_images);
    $all_used_images = array_merge($all_used_images, $all_page_images_without_sizes);

    $all_option_images = cu_get_option_images();
    $all_option_images_without_sizes = cu_get_all_images_without_sizes($all_option_images);
    $all_used_images = array_merge($all_used_images, $all_option_images_without_sizes);

    $all_widget_images = cu_get_all_widget_images();
    $all_widget_images_without_sizes = cu_get_all_images_without_sizes($all_widget_images);
    $all_used_images = array_merge($all_used_images, $all_widget_images_without_sizes);

    $all_meta_yoast_images = cu_get_all_meta_yoast_images();
    $all_meta_yoast_images_without_sizes = cu_get_all_images_without_sizes($all_meta_yoast_images);
    $all_used_images = array_merge($all_used_images, $all_meta_yoast_images_without_sizes);

    $all_customize_images = cu_all_customize_images();
    $all_customize_images_without_sizes = cu_get_all_images_without_sizes($all_customize_images);
    $all_used_images = array_merge($all_used_images, $all_customize_images_without_sizes);

    // remove duplicate
    $all_used_images = array_unique($all_used_images);

    // fixed url to path
    foreach($all_used_images AS $i=>$all_used_image){
        $all_used_images[$i] = cu_url_to_path($all_used_image);
    }
    return $all_used_images;
}

function cu_get_all_meta_yoast_images(){
    global $wpdb;
    $yoast_images = $wpdb->get_col("SELECT DISTINCT(meta_value) 
        FROM `{$wpdb->postmeta}`
        WHERE meta_key = '_yoast_wpseo_opengraph-image' OR meta_key = '_yoast_wpseo_twitter-image'
        AND post_id IN (SELECT ID FROM `{$wpdb->posts}` WHERE post_type NOT LIKE 'revision')", 0);

    $wpseo_titles = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE 'wpseo_titles'" );
    if($wpseo_titles){
        $wpseo_titles = maybe_unserialize($wpseo_titles);
        if(isset($wpseo_titles['company_logo']) && !empty($wpseo_titles['company_logo'])){
            $yoast_images[] = cu_url_to_path($wpseo_titles['company_logo']);
        }
        if(isset($wpseo_titles['social-image-url-tax-category']) && !empty($wpseo_titles['social-image-url-tax-category'])){
            $yoast_images[] = cu_url_to_path($wpseo_titles['social-image-url-tax-category']);
        }
        if(isset($wpseo_titles['social-image-url-tax-post_tag']) && !empty($wpseo_titles['social-image-url-tax-post_tag'])){
            $yoast_images[] = cu_url_to_path($wpseo_titles['social-image-url-tax-post_tag']);
        }
        if(isset($wpseo_titles['company_logo_meta']['url']) && !empty($wpseo_titles['company_logo_meta']['url'])){
            $yoast_images[] = cu_url_to_path($wpseo_titles['company_logo_meta']['url']);
        }
        if(isset($wpseo_titles['open_graph_frontpage_image']) && !empty($wpseo_titles['open_graph_frontpage_image'])){
            $yoast_images[] = cu_url_to_path($wpseo_titles['open_graph_frontpage_image']);
        }
    }

    $wpseo_social = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE 'wpseo_social'" );
    if($wpseo_social){
        $wpseo_social = maybe_unserialize($wpseo_social);
        if(isset($wpseo_social['og_default_image']) && !empty($wpseo_social['og_default_image'])){
            $yoast_images[] = cu_url_to_path($wpseo_social['og_default_image']);
        }
        if(isset($wpseo_social['og_frontpage_image']) && !empty($wpseo_social['og_frontpage_image'])){
            $yoast_images[] = cu_url_to_path($wpseo_social['og_frontpage_image']);
        }
        if(isset($wpseo_social['og_frontpage_image']) && !empty($wpseo_social['og_default_image'])){
            $yoast_images[] = cu_url_to_path($wpseo_social['og_default_image']);
        }
    }

    $yoast_images = array_unique($yoast_images);
    return $yoast_images;
}

function cu_all_customize_images(){
    $customize_images = array();
    $site_icon_id = get_option( 'site_icon' );
    if ( ! empty( $site_icon_id ) ) {
        $customize_images[] = wp_get_attachment_image_url($site_icon_id, 'full');
    }
    return $customize_images;
}

function cu_get_all_block_images(){

    $block_images = array();

    $all_any_type_post = cu_get_all_any_type_post();
    if($all_any_type_post){
        foreach($all_any_type_post AS $any_type_post){
            $block_images = cu_images_from_content($any_type_post->ID, $block_images);
        }
    }

    return $block_images;
}

function cu_get_all_any_type_post(){
    global $wpdb;
    $posts = $wpdb->get_results("SELECT * FROM {$wpdb->posts} WHERE post_type NOT LIKE 'revision'");
    return $posts;
}

function cu_get_all_widget_images(){
    global $wpdb;

    $widget_images = array();
    $widget_content = $wpdb->get_results(
        "
    SELECT option_value
    FROM $wpdb->options
    WHERE option_name LIKE 'widget_%'
    ");

    foreach ($widget_content as $widget) {
        $widget_data = maybe_unserialize($widget->option_value);
        if (!empty($widget_data) && is_array($widget_data)) {
            foreach ($widget_data as $widget_instance) {

                if(isset($widget_instance['content'])){
                    $widget_html =  $widget_instance['content'];
                    $dom = new DOMDocument();
                    @$dom->loadHTML($widget_html);
                    $imgTags = $dom->getElementsByTagName('img');
                    foreach ($imgTags as $imgTag) {
                        $src = $imgTag->getAttribute('src');
                        if($src){
                            $widget_images[] = $src;
                        }
                    }
                }
            }
        }
    }
    return $widget_images;
}

function cu_get_option_images(){
    $images = array();
    if (function_exists('get_field')) {
        $images[] = get_field('header', 'option')['logo']['url'];
        $images[] = get_field('header', 'option')['logo_other']['url'];
        $images[] = get_field('popups', 'option')['thanksPopupImage']['url'];
    }
    return $images;
}

function cu_get_all_featured_images() {

    $featured_images = array();
    $all_any_type_post = cu_get_all_any_type_post();

    if($all_any_type_post){
        foreach($all_any_type_post AS $any_type_post){
            $featured_image = cu_get_featured_image($any_type_post->ID);
            if ($featured_image) {
                $featured_images[] = $featured_image['url'];
            }
        }
    }

    return $featured_images;
}

function cu_get_base_filename_without_size($url_image){
    $url_image = str_replace('-scaled', '', $url_image);
    if (pathinfo($url_image, PATHINFO_EXTENSION) == 'webp') {
        preg_match('/(.+?)(-\d+x\d+)?\.webp$/i', $url_image, $matches);
        return ($matches) ? $matches[1].'.webp' : false;
    }
    preg_match('/(.+?)(-\d+x\d+)?\.(jpg|jpeg|png|gif|bpm|tiff|tif|webp)$/i', $url_image, $matches);
    return ($matches) ? $matches[1].'.'.$matches[3] : false;
}

function cu_images_from_content($post_id, $images) {
    $post_content = get_post_field('post_content', $post_id);

    $blocks = parse_blocks($post_content);
    foreach ($blocks as $block) {
        $images = cu_parse_blocks($block, $images);
    }

    return $images;
}

function cu_parse_blocks($block, $images){

    switch ($block['blockName']) {
        case NULL:
            break;
        case 'core/spacer':
            break;
        case 'core/heading':
            break;
        case 'core/list':
            break;
        case 'yoast/faq-block':
            break;
        case 'core/image':
        case 'core/html':
        case 'core/paragraph':
            $images = get_image_from_html($block['innerHTML'], $images);
            break;
        case 'core/shortcode':
            break;
        default:		
            // checked ACF block
            if (strpos($block['blockName'], 'acf/') === 0 && isset($block['attrs']['data']) && is_array($block['attrs']['data'])) {
                foreach ($block['attrs']['data'] as $key => $value) {
                    if(is_numeric($value) && wp_attachment_is_image($value)){
                        // if image
                        $image_url = wp_get_attachment_image_url($value, 'full');
                        if($image_url) $images[] = $image_url;
                    } else {
                        // checked other block
						if(!empty($value) && is_string($value)){
                            $images = get_image_from_html($value, $images);
						}
                    }
                }
            }
            break;
    }
    return $images;
}

function get_image_from_html($html, $images){
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    if ($dom->getElementsByTagName('html')->length > 0) {
        $imgTags = $dom->getElementsByTagName('img');
        if ($imgTags->length > 0) {
            foreach ($imgTags as $imgTag) {
                $src = $imgTag->getAttribute('src');
                if ($src) $images[] = $src;
            }
        }
        $imgTags = $dom->getElementsByTagName('source');
        if ($imgTags->length > 0) {
            foreach ($imgTags as $imgTag) {
                $src = $imgTag->getAttribute('srcset');
                if ($src) $images[] = $src;
            }
        }
    }
    return $images;
}

function cu_get_featured_image($post_id) {
    $thumbnail_id = get_post_thumbnail_id($post_id);
    if (!$thumbnail_id) {
        return false;
    }
    $image_info = wp_get_attachment_image_src($thumbnail_id, 'full');

    $featured_image = array(
        'url' => $image_info[0],
        'width' => $image_info[1],
        'height' => $image_info[2]
    );

    return $featured_image;
}

function cu_get_all_images_without_sizes($all_images){
    $all_images_without_sizes = array();
    foreach($all_images AS $image){
        $image_without_sizes = cu_get_base_filename_without_size($image);
        if($image_without_sizes) $all_images_without_sizes[] = $image_without_sizes;
    }
    $all_images_without_sizes = array_unique($all_images_without_sizes);
    return $all_images_without_sizes;
}

function cu_sum_image_sizes($image_paths) {
    $total_size = 0;
    foreach ($image_paths as $path) {
        if (file_exists($path)) {
            $size_bytes = filesize($path);
            $size_mb = $size_bytes / (1024 * 1024); // mb
            $total_size += $size_mb;
        }
    }
    return round($total_size,2);
}

function cu_delete_image($url, $all_images_for_uploads){

    $path = cu_url_to_path($url);
    foreach($all_images_for_uploads AS $image_for_uploads){
        $image_for_uploads_without_size =  cu_get_base_filename_without_size($image_for_uploads);
        if($image_for_uploads_without_size == $path){
            cu_delete_post_by_image_url(cu_path_to_url($path));
            unlink($image_for_uploads);
        }
    }
}

function cu_trash_image($url, $all_images_for_uploads, $all_unused_images){
    $path = cu_url_to_path($url);
    foreach($all_images_for_uploads AS $image_for_uploads){
        $image_for_uploads_without_size =  cu_get_base_filename_without_size($image_for_uploads);
        if($image_for_uploads_without_size == $path){
            $restore = $image_for_uploads . '.trash';
            rename($image_for_uploads, $restore);
        }
    }
    $key = array_search($path, $all_unused_images);
    if($key && isset($restore)) $all_unused_images[$key] = $restore;
    return $all_unused_images;
}
function cu_restore_image($url, $all_images_for_uploads){
    $path = cu_url_to_path($url);
    foreach($all_images_for_uploads AS $image_for_uploads){
        $image_for_uploads_without_size =  cu_get_base_filename_without_size($image_for_uploads);
        if($image_for_uploads_without_size == $path){
            $restore = str_replace('.trash', '', $image_for_uploads) ;
            rename($image_for_uploads, $restore);
        }
    }
}

function cu_restore_all(){
    $all_trashed = cu_get_trashed();
    $all_images_for_uploads = cu_get_all_images_for_uploads();
    foreach ($all_trashed AS $trashed){
        cu_restore_image($trashed, $all_images_for_uploads);
    }

//    wp_send_json(
//        array(
//            'cu_get_all_unused_images' => count($all_unused_images)
//        )
//    );
}

function cu_delete_post_by_image_url($image_url) {
    global $wpdb;

    $image_guid = $wpdb->get_var($wpdb->prepare("SELECT guid FROM $wpdb->posts WHERE guid = %s", $image_url));

    if ($image_guid) {
        $post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid = %s", $image_guid));

        if ($post_id) {
            $wpdb->delete($wpdb->posts, array('ID' => $post_id), array('%d'));
            $wpdb->delete($wpdb->postmeta, array('post_id' => $post_id), array('%d'));
        }
    }
}


function cu_path_to_url($path){
    return str_replace(ABSPATH, site_url('/'),  $path);
}
function cu_url_to_path($url){
    return str_replace(site_url('/'),ABSPATH,  $url);
}

function cu_get_trashed(){
    $upload_dir = wp_upload_dir();
    $upload_path = $upload_dir['basedir'];
    $uploaded_files = scandir($upload_path);
    $year_folders = array_filter($uploaded_files, function($file) {
        return preg_match('/^\d{4}$/', $file);
    });
    $all_images = array();
    foreach ($year_folders as $year_folder) {
        $year_path = $upload_path . '/' . $year_folder;
        $month_folders = array_diff(scandir($year_path), array('.', '..'));
        foreach ($month_folders as $month_folder) {
            $month_path = $year_path . '/' . $month_folder;
            $all_images = array_merge($all_images, glob($month_path . '/*.trash'));
        }
    }
    return $all_images;
}

function cu_scan_update(){
    $all_images_for_uploads = cu_get_all_images_for_uploads();
    $all_images_for_uploads_original = cu_get_all_images_without_sizes($all_images_for_uploads);
    $all_used_images = cu_get_all_used_images();
    $all_used_images_original = cu_get_all_images_without_sizes($all_used_images);
    $unused_images = array();
    foreach($all_images_for_uploads_original AS $i=>$image_for_uploads){
        if(!in_array($image_for_uploads, $all_used_images_original)){
            $unused_images[] = $image_for_uploads;
        }
    }

    update_option('cu_get_all_unused_images', $unused_images);
    return $unused_images;
}

function cu_get_all_image_sizes() {
    global $_wp_additional_image_sizes;

    $sizes = array();

    foreach (get_intermediate_image_sizes() as $size) {
        $sizes[$size] = array(
            'width'  => intval(get_option($size . '_size_w')),
            'height' => intval(get_option($size . '_size_h')),
            'crop'   => (bool) get_option($size . '_crop')
        );
    }

    if ($_wp_additional_image_sizes) {
        foreach ($_wp_additional_image_sizes as $size => $value) {
            $sizes[$size] = array(
                'width'  => intval($value['width']),
                'height' => intval($value['height']),
                'crop'   => (bool) $value['crop']
            );
        }
    }

    return $sizes;
}

function cu_dump($data){
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
}

function cu_write($message) {
    file_put_contents(
        dirname(__FILE__) . '/log.log',
        date('d-M-Y H:i:s') . ' - ' . $message .PHP_EOL,
        FILE_APPEND
    );
}