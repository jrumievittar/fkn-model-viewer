<?php
/**
 * Plugin Name: FKN Model Viewer
 * Plugin URI: https://wordpress.org/plugins/fkn-model-viewer
 * Description: This is a plugin to insert the model-viewer script and generate shortcode to insert a 3d model in the frontend web page.
 * Version: 1.0.0
 * Author: J. Rumie Vittar by fuken
 * Author URI: https://fuken.es/
 * Author Email: contacto@fuken.es
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * * This plugin uses:
 * - Draco 3D Data Compression (Apache 2.0 License)
 * - Basis Universal Supercompressed GPU Texture Codec (Apache 2.0 License)
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


// Add script -------------------------------
function fkn_mv_mas_add_script() {
    wp_enqueue_script( 'model-viewer', plugin_dir_url( __FILE__ ) . 'public/model-viewer-lib.js', array(), null, true );
}
add_action('wp_enqueue_scripts', 'fkn_mv_mas_add_script');
  
function fkn_mv_script_loader_tag($tag, $handle, $src) {
    if ( 'model-viewer' === $handle ) {
        $tag = '<script type="module" src="' . esc_url( $src ) . '"></script>';
    }
    return $tag;
}
add_filter('script_loader_tag', 'fkn_mv_script_loader_tag', 10, 3);




// Add the menu ------------------------------
 function fkn_mv_am_add_admin_menu() {
    // Add the new submenu to the main administration menu
    add_menu_page( 

        'fkn Model Viewer', // Submenu title
        'fkn Model Viewer', // Menu title
        'manage_options', // Capability
        'fkn_model_viewer_settings', // Settings page function
        'fkn_mv_p_page' // Function to display the settings page
    );
}
add_action('admin_menu', 'fkn_mv_am_add_admin_menu'); // Hook into the admin_menu action
// ---------------------------------------------------



// Admin page function ------------------------
function fkn_mv_p_page() {

    // Option controls
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['model_viewer_save'])) {

        // Verificar nonce
        if ( ! isset( $_POST['model_viewer_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['model_viewer_nonce'] ) ) , 'model_viewer_save' ) ) {
            wp_die( 'Nonce verification failed.' );
        }
        
        // Save the shortcode
        $file_id = fkn_mv_handle_file_upload();
        $shortcode = fkn_mv_gs_generate_shortcode($file_id);
        echo '<div class="updated"><p>Saved shortcode: ' . esc_html($shortcode) . '</p></div>';
    }

    // Code for the admin page functionality form
    echo '<div class="wrap">';
    echo '<h2>fkn Model Viewer Options</h2>';
    echo '<p>Please select a .glb file:</p>';
    echo '<br>';
    // Mostrar el nonce
    echo '<form method="post" enctype="multipart/form-data">';
    wp_nonce_field( 'model_viewer_save', 'model_viewer_nonce' );
    echo '<input type="file" name="model_viewer_file" required>';
    echo '<br><br>';
    echo '<input type="submit" name="model_viewer_save" class="button button-primary" value="Save Shortcode">';
    echo '</form>';

    // Instructions for use
    echo '<br><br><br>';
    echo '<h3>Instructions for use:</h3>';
    echo '<p>To use the plugin, simply select a .glb file in the file field and press the “Save Shortcode” button.</p>';
    echo '<p>The shortcode can be copied and pasted anywhere on the website.</p>';
    echo '<h4>How to Fix: “Sorry, This File Type is Not Allowed for Security Reasons” in WordPress</h4>';
    echo '<ul>';
    echo '<li><strong>Using a WordPress plugin</strong></li>';
    //echo '<ol>';
    //echo '<li>Downloading the free plugin <a href="https://es.wordpress.org/plugins/wp-extra-file-types/" target="_blank">Extra File Types</a></li>';
    //echo '</ol>';
    echo '</ul>';

    // Regard 
    echo '<br>';
    echo '<p>Made with ❤️. Thank you for using <strong>fkn Model Viewer</strong> by <strong>fuken</strong>. Please consider making a donation to support <a href="https://paypal.me/jrumievittar?country.x=ES&locale.x=es_ES" target="_blank">this project</a>.</p>';
    echo '</div>';
}
// ---------------------------------------------------




// Handle file upload --------------------------
function fkn_mv_handle_file_upload() {
    
    // Verificar nonce
    if (!isset($_POST['model_viewer_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['model_viewer_nonce'])), 'model_viewer_save')) {
        wp_die('Nonce verification failed.');
    }

    // Verificar si se ha subido un archivo
    if (!isset($_FILES['model_viewer_file']) || !is_array($_FILES['model_viewer_file'])) {
        wp_die('No file uploaded.');
    }

    $file = $_FILES['model_viewer_file'];
    
    // Verificar si hay errores en la carga del archivo
    if ($file['error'] !== UPLOAD_ERR_OK) {
        wp_die('Error loading file.');
    }

    // Sanear el nombre del archivo
    $file_name = sanitize_file_name($file['name']);

    // Asegurarse de que el archivo fue subido
    // if (!isset($_FILES['model_viewer_file']) || !is_array($_FILES['model_viewer_file'])) {
    //     wp_die('No file uploaded.');
    // }


    // Saneamiento de datos del archivo
    // $file = array_map('sanitize_text_field', $file);


    // Usar wp_handle_upload para manejar la carga del archivo
    // $upload_overrides = array( 'test_form' => false );
    // $upload_info = wp_handle_upload($file, $upload_overrides);

    // if (isset($upload_info['error'])) {
    //     wp_die($upload_info['error']);
    // }
    // Usar wp_handle_upload para manejar la carga del archivo
    $upload_overrides = array('test_form' => false);
    $movefile = wp_handle_upload($file, $upload_overrides);

    if (isset($movefile['error'])) {
        wp_die($movefile['error']);
    }

    // Crear la entrada del archivo adjunto en la base de datos de WordPress
    $attachment = array(
        'post_mime_type' => $movefile['type'],
        // 'post_title'     => preg_replace('/\.[^.]+$/', '', $file_name),
        'post_title'     => $file_name,
        'post_content'   => '',
        'post_status'    => 'inherit'
    );

    // $attach_id = wp_insert_attachment($attachment, $movefile['file']);

    // return $attach_id;

    // Sanear el nombre del archivo
    // $file_name = sanitize_file_name($upload_info['file']);

    // Validar que se haya seleccionado un archivo
    // if (empty($file_name)) {
    //     wp_die('Error: No file selected.');
    // }

    // Crear la entrada del archivo adjunto en la base de datos de WordPress
    // $attachment = array(
    //     'post_title'     => $file_name,
    //     'post_content'   => '',
    //     'post_status'    => 'inherit',
    //     'post_mime_type' => $upload_info['type'],
    // );

    $file_id = wp_insert_attachment($attachment, $movefile['file']);

    return $file_id;
}
// ---------------------------------------------------





// Generate shortcode -----------------------------
function fkn_mv_gs_generate_shortcode($file_id) {
    $file_url = wp_get_attachment_url($file_id);
    $shortcode = '[fkn_mv_s_shortcode src="' . esc_url($file_url) . '"]';
    return $shortcode;
}
// ---------------------------------------------------




// Shortcode function to display the shortcode in the editor -----------------
function fkn_mv_s_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'src' => '',
        ),
        $atts,
        'fkn_mv_s_shortcode'
    );

    if (empty($atts['src'])) {
        return 'Error: Atributo "src" requerido.';
    }

    $output = '<model-viewer loading="eager" camera-controls touch-action="pan-y" auto-rotate src="' . esc_url($atts['src']) . '" shadow-intensity="1" alt="A 3D model" style="width: 350px; height: 300px;"></model-viewer>';

    return $output;
}
add_shortcode('fkn_mv_s_shortcode', 'fkn_mv_s_shortcode');
// ---------------------------------------------------
