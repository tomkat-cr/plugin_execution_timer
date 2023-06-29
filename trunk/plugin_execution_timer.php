<?php
/*
Plugin Name: Plugin Execution Timer
Plugin URI: https://www.mediabros.com/plugin-execution-timer-plugin/
Description: Logs the execution time of other plugins during website frontend and admin UI rendering.
Version: 1.2
Author: Carlos J. Ramirez
Author URI: https://carlosjramirez.com
Requires at least: 5.0
Tested up to: 6.2.2
Requires PHP: 7.0
License: GPL
Text Domain: pluginexecutiontimer
*/

define('PLUGINS_LOAD_TIME_LOG_FILENAME', 'plugins_load_time.txt');
define('PLUGINS_LOAD_TIME_RELATIVE_PATH', plugin_basename( __FILE__ ));

function plt_get_date() {
    $Now = new DateTime('now', new DateTimeZone('GMT'));
    return $Now->format('Y-m-d H:i:s v');
    // return date('Y-m-d H:i:s v');
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

function log_entry($entry_type, $entry_name, $execution_time, $entry_text) {
    global $execution_timer_start, $log_data;
    $log_data[] = [
        'type' => $entry_type,
        'name' => $entry_name,
        'datetime' => plt_get_date(),
        'execution_timer_start' => $execution_timer_start,
        'execution_time' => $execution_time,
        'text' => $entry_text,
    ];
}

function execution_time_text($execution_time, $title='Execution Time') {
    return $title . ': ' . $execution_time . ' seconds';
}

function init_plugin_execution_timer($add_text='') {
    global $execution_timer_start, $log_data;

    $execution_timer_start = microtime(true);
    if (is_null($log_data)) {
        $log_data = [];
    }

    $entry_text =
        PHP_EOL . 
        '*** init_plugin_execution_timer ' . plt_get_date() . 
        ($add_text == '' ? '' : ' [' . $add_text . ']');

    log_entry(
        'initial_sequence',
        'init_plugin_execution_timer', 
        0,
        $entry_text
    );
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
add_action('plugin_loaded', 'save_plugin_execution_time', 1);

function save_plugin_execution_time($plugin) {
    global $execution_timer_start;

    $execution_timer_start_null = false;
    if (is_null($execution_timer_start)) {
        init_plugin_execution_timer('START');
        $execution_timer_start_null = true;
    }

    $execution_time = microtime(true) - $execution_timer_start;

    // Set execution_timer for next plugin/section
    $execution_timer_start = microtime(true);

    // Determine if it's frontend or admin UI
    $location = is_admin() ? 'Admin UI' : 'Frontend';

    if (
        $execution_timer_start_null &&
        strpos($plugin, "plugin_execution_timer.php") !== false
    ) {
        // Won't report itself if the $execution_timer_start was null
        return;
    }

    $entry_text =
        "Location: {$location}" . PHP_EOL .
        "Plugin Path: {$plugin}" . PHP_EOL . 
        execution_time_text($execution_time);

    log_entry(
        'plugin',
        basename($plugin),
        $execution_time,
        $entry_text
    );
}


function save_other_section_execution_time($section, $other_data='') {
    global $execution_timer_start;

    $execution_time = microtime(true) - $execution_timer_start;

    // Set execution_timer for next plugin/section
    $execution_timer_start = microtime(true);

    // Determine if it's frontend or admin UI
    $location = is_admin() ? 'Admin UI' : 'Frontend';

    $entry_text =
        "Location: {$location}" . PHP_EOL .
        "Section: {$section}" . PHP_EOL;
    if (!is_null($other_data) && $other_data != '') {
        $entry_text .=
            'Other data: ' . print_r($other_data, true) . PHP_EOL;
    }
    $entry_text .=
        execution_time_text($execution_time);

    log_entry(
        'section',
        $section, 
        $execution_time,
        $entry_text
    );
}

function report_section_plugins_loaded($data='') {
    save_other_section_execution_time('plugins_loaded', $data);
}

function report_section_sanitize_comment_cookies($data='') {
    save_other_section_execution_time('sanitize_comment_cookies', $data);
}

function report_section_setup_theme($data='') {
    save_other_section_execution_time('setup_theme', $data);
}

function report_section_after_setup_theme($data='') {
    save_other_section_execution_time('after_setup_theme', $data);
}

function report_section_init($data='') {
    save_other_section_execution_time('init', $data);
}

function report_section_wp_loaded($data='') {
    save_other_section_execution_time('wp_loaded', $data);
}

add_action( 'plugins_loaded', 'report_section_plugins_loaded', 1 );
add_action( 'sanitize_comment_cookies', 'report_section_sanitize_comment_cookies', 1 );
add_action( 'setup_theme', 'report_section_setup_theme', 1 );
add_action( 'after_setup_theme', 'report_section_after_setup_theme', 1 );
add_action( 'init', 'report_section_init', 1 );
add_action( 'wp_loaded', 'report_section_wp_loaded', 1 );

// Hook into the 'shutdown' action to stop measuring execution times
add_action('shutdown', 'stop_plugin_execution_timer', 1);

function stop_plugin_execution_timer($data='') {
    global $log_data;

    save_other_section_execution_time('shutdown', $data);

    // $log_data_text = implode(PHP_EOL, $log_data);
    $log_data_text = '';
    $total_execution_time = 0;
    $max_execution_time = -1;
    $max_element = null;
    $elements_list = [];
    foreach($log_data as $entry) {
        $entryname = $entry['type'] . ' | ' . $entry['name'];;
        $log_data_text .= $entry['datetime'] . ' | ' . $entry['text'] . PHP_EOL . PHP_EOL;
        if ($max_execution_time < $entry['execution_time']) {
            $max_element = $entryname;
        }
        $max_execution_time = max($max_execution_time, $entry['execution_time']);
        $total_execution_time += $entry['execution_time'];
        $elements_list[$entryname]= $entry['execution_time'];
    }

    $log_data = null;
    arsort($elements_list);
    $log_data_text .= 
        '** SUMMARY **' . PHP_EOL . PHP_EOL .
        execution_time_text($total_execution_time, 'Total execution time') . PHP_EOL .
        execution_time_text($max_execution_time, 'Max. execution time') . PHP_EOL .
        "Max. execution time element: {$max_element}" . PHP_EOL .
        "Ranking:" . PHP_EOL .
        print_r($elements_list, true). PHP_EOL . PHP_EOL;

    // Save the log data to a file in the website root directory
    add_to_log_file($log_data_text);
}

/* 
 **************
 * ADMIN PAGE *
 **************
 */

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
