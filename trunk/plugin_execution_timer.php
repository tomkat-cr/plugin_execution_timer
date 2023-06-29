<?php
/*
Plugin Name: Plugin Execution Timer
Description: Logs the execution time of other plugins during website frontend and admin UI rendering.
Version: 1.0
Author: Carlos J. Ramirez
*/

define('PLUGINS_LOAD_TIME_LOG_FILENAME', 'plugins_load_time.txt');
define( 'PLUGINS_LOAD_TIME_RELATIVE_PATH', plugin_basename( __FILE__ ) );

function plt_get_date() {
    return date('Y-m-d H:i:s v');
}

// Filter to update the "pre_update_option" so this plugin is at the top position
function plugins_load_time_first_on_safe( $value, $old_value ){
    start_plugin_execution_timer_init();
    if( /* $plo_current_position = */ array_search(PLUGINS_LOAD_TIME_RELATIVE_PATH, $value) ){
        $new_value = array();
        $new_value[] = PLUGINS_LOAD_TIME_RELATIVE_PATH;
        for( $i = 0; $i < count($value); $i++ ){
            if( $value[$i] != PLUGINS_LOAD_TIME_RELATIVE_PATH ) {
                $new_value[] = $value[$i];
            }
        }
        $value = $new_value;
    }
    return $value;
}

add_filter( 'pre_update_option_active_plugins', 'plugins_load_time_first_on_safe', 256, 2 );
// add_filter( 'pre_update_option_active_plugins', 'start_plugin_execution_timer_init', 256, 2 );

function init_plugin_execution_timer($add_text='') {
    global $execution_timer_start, $log_data;
    $execution_timer_start = microtime(true);
    if (is_null($log_data)) {
        $log_data = 
        PHP_EOL . 
        '*** ' . plt_get_date() . 
        ($add_text == '' ? '' : ' [' . $add_text . ']') .
        PHP_EOL . PHP_EOL;
    }
}

function start_plugin_execution_timer($add_text='') {
    $log_data = 
        '*** ' . plt_get_date() . 
        ' || start_plugin_execution_timer >> ' . $add_text;
    add_to_log_file($log_data);

    init_plugin_execution_timer();
}

function start_plugin_execution_timer_init() {
    start_plugin_execution_timer('init');
}

// Hook into the 'plugin_loaded' action to measure loaded plugin execution time
add_action('plugin_loaded', 'save_plugin_execution_timer', 1);

function save_plugin_execution_timer($plugin) {
    global $execution_timer_start, $log_data;

    if (is_null($execution_timer_start)) {
        init_plugin_execution_timer('FORCED');
    }

    $execution_time = microtime(true) - $execution_timer_start;

    // Set execution_timer for next plugin
    $execution_timer_start = microtime(true);

    // Determine if it's frontend or admin UI
    $location = is_admin() ? 'Admin UI' : 'Frontend';

    $log_data .= 
        "Location: {$location}" . PHP_EOL .
        "Plugin Path: {$plugin}" . PHP_EOL . 
        "Execution Time: {$execution_time} miliseconds (" . 
        (round($execution_time/1000, 2)) . 
        ' seconds)' . PHP_EOL . PHP_EOL;
}

// Hook into the 'shutdown' action to stop measuring execution times
add_action('shutdown', 'stop_plugin_execution_timer', 1);

function stop_plugin_execution_timer() {
    global $log_data;

    // Save the log data to a file in the website root directory
    add_to_log_file($log_data);
}

// Add an admin menu option to view the plugins_load_time.txt file
add_action('admin_menu', 'plugin_execution_timer_admin_menu');

function plugin_execution_timer_admin_menu() {
    add_menu_page(
        'Plugin Execution Timer',
        'Plugin Execution Timer',
        'manage_options',
        'plugin-execution-timer',
        'plugin_execution_timer_admin_page'
    );
}

function add_to_log_file($log_data) {
    $log_file = ABSPATH . PLUGINS_LOAD_TIME_LOG_FILENAME;
    file_put_contents($log_file, $log_data . PHP_EOL, FILE_APPEND);
}

function clear_log_file() {
    $log_file = ABSPATH . PLUGINS_LOAD_TIME_LOG_FILENAME;
    if (file_exists($log_file)) {
        file_put_contents($log_file, '');
    }
}

function plugin_execution_timer_admin_page() {
    // Check if the user has the required permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Handle the "Clear Log" button submission
    if (isset($_POST['clear_log'])) {
        check_admin_referer('clear_log_action');
        clear_log_file();
    }

    // Read the contents of the plugins_load_time.txt file
    $log_file = ABSPATH . PLUGINS_LOAD_TIME_LOG_FILENAME;
    $log_data = file_exists($log_file) ? file_get_contents($log_file) : 'No data available.';

    // Display the log data in a textarea and the "Clear Log" button
    echo '<div class="wrap">';
    echo '<h1>Plugin Execution Timer</h1>';
    echo '<textarea readonly style="width: 100%; height: 500px;">' . esc_textarea($log_data) . '</textarea>';

    // Add the "Clear Log" button
    echo '<BR/><BR/>';
    echo '<form method="post" action="">';
    wp_nonce_field('clear_log_action');
    echo '<input type="submit" name="clear_log" class="button button-primary" value="Clear Log">';
    echo '</form>';

    echo '</div>';
}
