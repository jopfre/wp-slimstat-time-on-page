<?php
/*
Plugin Name: WP SlimStat - Time On Page
Description:
Version: 1.0
*/
function sec_to_time($seconds) {
  $hours = floor($seconds / 3600);
  $minutes = floor($seconds % 3600 / 60);
  $seconds = $seconds % 60;

  return sprintf("%d:%02d:%02d", $hours, $minutes, $seconds);
} 

class wp_slimstat_time_on_page{

  public static function init(){
    if ( !class_exists( 'wp_slimstat' ) ) {
      return true;
    }

    // Register this report on the Audience screen
    add_filter( 'slimstat_reports_info', array( __CLASS__, 'add_report_info' ) );

    add_filter( 'slimstat_column_names', array( __CLASS__, 'add_column_names' ) );
    // Add a new option to the Add-ons Settings tab
    // add_filter( 'slimstat_options_on_page', array( __CLASS__, 'add_options' ), 10, 2 );
  }

  public static function add_report_info( $_reports_info = array() ) {
    $_reports_info[ 'slim_time_on_page' ] = array(
      // Report Title
      'title' => 'Average Time On Page/Post',

      // Callback that renders the data retrieved from the DB
      'callback' => array( __CLASS__, 'raw_results_to_html' ),

      // Arguments for the callback
      'callback_args' => array(

        // The 'raw' param is the name of the function that retrieves the data from the DB
        // Please note: if you specify this paramenter, Slimstat will attempt to use it 
        // for the Excel generator and Email functionality
        // 'columns' => 
        'raw' => array( __CLASS__, 'get_raw_results' )
      ),

      // Report layout: normal, wide, full-width, tall
      // You can mix and match class names (normal tall) 
      'classes' => array( 'normal' ),

      // On what screen should this report appear?
      // slimview2: Overview
      // slimview3: Audience
      // slimview4: Site Analysis
      // slimview5: Traffic Sources
      // slimview6: Geolocation
      // dashboard: WordPress Dashboard
      'screens' => array( 'slimview4' )
    );

    return $_reports_info;
  }

  public static function add_column_names( $_all_columns_names = array() ) {
    return array_merge( array(
      'resource' => array( 'Resource', 'varchar' ),
      'TimeOnPage' => array( 'Time on Page', 'varchar' )
    ), $_all_columns_names );
  }

  // Use this function to append addon-specific settings to the Settings screen
  // You can add your settings to any screen, but we recommending using the Add-ons screen ( #7 )
  // Please make sure to use unique slugs, so that they don't conflict with existing ones
  public static function add_options( $_settings = array(), $_current_tab = 0 ) {
    // $_settings[ 7 ][ 'rows' ][ 'addon_time_on_page_header' ] = array(
    //   'description' => 'Super Duper',
    //   'type' => 'section_header'
    // );
    // $_settings[ 7 ][ 'rows' ][ 'addon_time_on_page_switch' ] = array(
    //   'description' => 'Switch',
    //   'type' => 'toggle',
    //   'long_description' => 'This switch can turn on or off a special behavior in your add-on.'
    // );
    // $_settings[ 7 ][ 'rows' ][ 'addon_time_on_page_text' ] = array(
    //   'description' => 'Text Field',
    //   'type' => 'text',
    //   'long_description' => 'A text field.'
    // );
    // $_settings[ 7 ][ 'rows' ][ 'addon_time_on_page_textarea' ] = array(
    //   'description' => 'Text Area',
    //   'type' => 'textarea',
    //   'long_description' => 'A text area.'
    // );
    
    return $_settings;
  }



  public static function get_raw_results( $_report_id = 'p0' ) {
     $sql = "
      SELECT resource, AVG(dt_out - dt) as 'TimeOnPage'
      FROM {$GLOBALS['wpdb']->prefix}slim_stats
      WHERE resource <> '' AND dt_out > 0
      GROUP BY resource
      ORDER BY TimeOnPage DESC";

    return wp_slimstat_db::get_results( $sql );
  }
  
  public static function raw_results_to_html( $_args = array() ) {
    
    // Call the function that retrieves the data from the DB
    // This function should always return the ENTIRE dataset
    $all_results = call_user_func( $_args[ 'raw' ] , $_args );
    
    if ( empty( $all_results ) ) {
      echo '<p class="nodata">No data to display</p>';
    }
    else {
      // Slice the results to get only what we need
      $results = array_slice(
        $all_results,
        wp_slimstat_db::$filters_normalized[ 'misc' ][ 'start_from' ],
        wp_slimstat_db::$filters_normalized[ 'misc' ][ 'limit_results' ]
      );

      // Paginate results, if needed
      wp_slimstat_reports::report_pagination( count($results), count($all_results), false ); 
      
      // Loop through the resultset
      foreach ( $results as $a_row ) {
        echo "<p>{$a_row[ 'resource' ]} <span>".sec_to_time($a_row[ 'TimeOnPage' ])."</span></p>"; 
      }
    }

    // Exit if this function was called through Ajax (refresh button)
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
      die();
    }
  }
}
// end of class declaration

// Bootstrap
if ( function_exists( 'add_action' ) ) {
  add_action( 'plugins_loaded', array( 'wp_slimstat_time_on_page', 'init' ), 10 );
}