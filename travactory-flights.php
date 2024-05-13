<?php
/**
 * Plugin Name: Travactory flights
 * Description: Display flights from a JSON file or an API.
 * Version: 1.0
 * Author: Rafał Czarnecki
 */

defined('ABSPATH') or die('Direct script access disallowed.');


// Register settings for storing API details
function travactory_flights_register_settings() {
    add_option('travactory_flights_use_mock', 'yes');
    add_option('travactory_flights_api_url', 'http://www.example.com/api/fares');
    add_option('travactory_flights_agentId', '');
    add_option('travactory_flights_departures', '');
    add_option('travactory_flights_destinations', '');
    add_option('travactory_flights_periodFromDate', '');
    add_option('travactory_flights_periodToDate', '');
    add_option('travactory_flights_airlineCodes', '');
    register_setting('travactory_flights_options_group', 'travactory_flights_use_mock');
    register_setting('travactory_flights_options_group', 'travactory_flights_api_url');
    register_setting('travactory_flights_options_group', 'travactory_flights_agentId');
    register_setting('travactory_flights_options_group', 'travactory_flights_departures');
    register_setting('travactory_flights_options_group', 'travactory_flights_destinations');
    register_setting('travactory_flights_options_group', 'travactory_flights_periodFromDate');
    register_setting('travactory_flights_options_group', 'travactory_flights_periodToDate');
    register_setting('travactory_flights_options_group', 'travactory_flights_airlineCodes');
}
add_action('admin_init', 'travactory_flights_register_settings');

// Add admin menu for plugin settings
function travactory_flights_admin_menu() {
    add_menu_page(
        'Travactory Flights Settings',
        'Travactory Flights',
        'manage_options',
        'travactory_flights',
        'travactory_flights_options_page'
    );
}
add_action('admin_menu', 'travactory_flights_admin_menu');

// Admin page setup
function travactory_flights_options_page() {
    ?>
    <div>
        <h2>Travactory Flights Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('travactory_flights_options_group'); ?>
            <table>
                <tr>
                    <th scope="row"><label for="travactory_flights_use_mock">Use Mock API:</label></th>
                    <td><input type="checkbox" id="travactory_flights_use_mock" name="travactory_flights_use_mock" value="yes" <?php checked('yes', get_option('travactory_flights_use_mock')); ?> /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="travactory_flights_api_url">API URL:</label></th>
                    <td><input type="text" id="travactory_flights_api_url" name="travactory_flights_api_url" value="<?php echo get_option('travactory_flights_api_url'); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="travactory_flights_agentId">Agent ID:</label></th>
                    <td><input type="number" id="travactory_flights_agentId" name="travactory_flights_agentId" value="<?php echo get_option('travactory_flights_agentId'); ?>"  /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="travactory_flights_departures">Departures:</label></th>
                    <td><input type="text" id="travactory_flights_departures" name="travactory_flights_departures" value="<?php echo get_option('travactory_flights_departures'); ?>" size="50" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="travactory_flights_destinations">Destinations:</label></th>
                    <td><input type="text" id="travactory_flights_destinations" name="travactory_flights_destinations" value="<?php echo get_option('travactory_flights_destinations'); ?>" size="50" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="travactory_flights_periodFromDate">Period from date:</label></th>
                    <td><input type="date" id="travactory_flights_periodFromDate" name="travactory_flights_periodFromDate" value="<?php echo get_option('travactory_flights_periodFromDate'); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="travactory_flights_periodToDate">Period to date:</label></th>
                    <td><input type="date" id="travactory_flights_periodToDate" name="travactory_flights_periodToDate" value="<?php echo get_option('travactory_flights_periodToDate'); ?>"  /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="travactory_flights_airlineCodes">Airline codes:</label></th>
                    <td><input type="text" id="travactory_flights_airlineCodes" name="travactory_flights_airlineCodes" value="<?php echo get_option('travactory_flights_airlineCodes'); ?>" size="50" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Fetch data from mock or api
function fetch_flights_data() {
    if (get_option('travactory_flights_use_mock') === 'yes') {
        // Use mock data
        return json_decode(file_get_contents(plugin_dir_path(__FILE__) . 'flights.json'), true);
    } else {
        // Use real API data
        $url = sprintf(
            '%s/%s/%s/%s/%s/%s?airlineCodes=%s',
            get_option('travactory_flights_api_url'),
            get_option('travactory_flights_agentId'),
            get_option('travactory_flights_departures'),
            get_option('travactory_flights_destinations'),
            get_option('travactory_flights_periodFromDate'),
            get_option('travactory_flights_periodToDate'),
            get_option('travactory_flights_airlineCodes')
        );

        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            return []; // Handle errors
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }
}

// Display flights table
function display() {
    $flights = fetch_flights_data();
    $output = '<table>';
    $output .= '<tr><th>Departure Date</th><th>Departure Airport</th><th>Destination Airport</th><th>Airline Code</th><th>Price</th></tr>';
    foreach ($flights as $flight) {
        $output .= sprintf(
            '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
            $flight['flight']['departure']['date'],
            $flight['flight']['departure']['name'],
            $flight['flight']['destination']['name'],
            $flight['flight']['airline']['code'],
            $flight['price']
        );
    }
    $output .= '</table>';
    return $output;
}
add_shortcode('travactory_flights', 'display');

// Print table content on all pages
function travactory_flights_content($content) {
    return $content . display();
}
add_filter('the_content', 'travactory_flights_content');
