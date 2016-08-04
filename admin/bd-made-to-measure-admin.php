<?php
/*
Plugin Name: Blinds Direct - Made to Measure
Plugin URI: https://github.com/michaeldoye/bd-made-to-measure
Description: Tools for Blinds Direct
Author: Web SEO Online
Author URI: http://webseo.co.za
Version: 0.0.1

	Copyright: © 2016 Web SEO Online (email : michael@webseo.co.za)
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*

/**
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	
	if ( class_exists( 'BD_made_to_measure' ) ) { 

		class BD_made_to_measure_admin extends BD_made_to_measure {
			public function __construct() {

				// indicates we are running the admin
				if ( is_admin() ) {
					// Admin Menu
					add_action( 'admin_menu', array( &$this, 'bd_admin_page' ) );
					// Markup ajax
					add_action( 'wp_ajax_get_markup_ajax', array( &$this, 'get_markup_ajax' ) );
					// Save markup
					add_action( 'wp_ajax_bd_ajax_save_markup_data', array( &$this, 'bd_ajax_save_markup_data' ) );
					// Get price books
					add_action( 'wp_ajax_get_price_book_ajax', array( &$this, 'get_price_book_ajax' ) );
				}

			}


			/**
			 * This function is only called when our plugin's page loads
			 */	 
			public function load_admin_js(){
			    add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_admin_assets' ) );
			}


			/**
			 * Admin Settings
			 */	
			public function bd_admin_page() {
				$bd_page = add_menu_page(
			        'Blinds Made to Measure',
			        'Blinds Made to Measure',
			        'administrator',
			        'blinds-made-to-measure',
			        array( &$this, 'bd_settings'),
			        'dashicons-admin-generic'
			  );
			  // Load the JS conditionally
			  add_action( 'load-' . $bd_page, array( &$this, 'load_admin_js' ) );				
			}


			/**
			 * Add scripts used in the admin area
			 */	
			public function enqueue_admin_assets(){

			    // Enqueue custom js
			    wp_enqueue_script( 
			        'bd_admin_custom',
			        plugins_url() . '/bd-made-to-measure/assets/js/admin/bd_admin_custom.js',
			        array( 'jquery', 'jquery-ui-core', 'jquery-ui-tabs' )
			    );

			    // Enqueue Toastr js
			    wp_enqueue_script( 
			        'toastr',
			        'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js',
			        array( 'jquery' )
			    );

			    // Enqueue nprogress js
			    wp_enqueue_script( 
			        'nprogress',
			        'https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.js',
			        array( 'jquery' )
			    );


			    // Enqueue angular js
			    wp_enqueue_script( 
			        'angular',
			        'https://ajax.googleapis.com/ajax/libs/angularjs/1.5.7/angular.min.js',
			        array( 'jquery' )
			    );

			    // Create globals here for the custom.js file
				wp_localize_script( 'bd_admin_custom', 'blinds', array(
					'ajax_url' => admin_url( 'admin-ajax.php' )
				));

			     // Register Toastr CSS
			     wp_register_style( 
			         'custom_wp_admin_css',
			         'https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css',
			         false,
			         '1.0.0'
			     );
			     // Enqueue Toastr CSS
			     wp_enqueue_style( 'custom_wp_admin_css' );

			     // Register nprogress CSS
			     wp_register_style( 
			         'nprogress_css',
			         plugins_url() . '/bd-made-to-measure/assets/css/admin/nprogress.css',
			         false,
			         '1.0.0'
			     );
			     // Enqueue nprogress CSS
			     wp_enqueue_style( 'nprogress_css' );

			}

			
			/**
			 * include html for admin area
			 */	
			public function bd_settings() { 
				include plugin_dir_path( __FILE__ ) .'admin-html.php';
			}


			/**
			 * get_product_addons call parent function
			 */	
			public function get_product_addons() {
				return parent::get_product_addons();
			}


			/**
			 * gets the markup data
			 */	
			public function get_markup_ajax() {

				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
					global $wpdb;

					$group = $_GET['group'];

				    // Build and execute the query
				    $results = $wpdb->get_results( "SELECT * FROM `wp_woocommerce_markup_manager_rules` 
				        WHERE `variation` = '$group' ", OBJECT );

				    if (!$results) {
				      $results = $wpdb->last_error;
				    }
				    
				    echo json_encode($results);		

				}

				wp_die();	
			}


			/**
			 * Saves the markup data
			 */	
			public function bd_ajax_save_markup_data() {

				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
					global $wpdb;

					$variation_name = $_GET['group']; //Group name
					$markup_data = $_GET['mdata'];
					$markup_group = $_GET['range'];

					// Build and execute the query
					$add_markup = $wpdb->query("INSERT INTO `wp_woocommerce_markup_manager_rules` (variation, {$markup_group}) 
						VALUES ('$variation_name', '$markup_data') ON DUPLICATE KEY UPDATE {$markup_group} = '$markup_data' " );

					$results = $wpdb->get_results( "SELECT * FROM `wp_woocommerce_addon_price_table` 
						INNER JOIN `wp_woocommerce_markup_manager_rules` 
						ON `wp_woocommerce_addon_price_table`.`field_label` = `wp_woocommerce_markup_manager_rules`.`variation` 
						WHERE `field_label` = '$variation_name' ", OBJECT );
					
					// Send the reponse back
					echo empty($add_markup) ? json_encode($wpdb->last_error) : json_encode($results);

				}

				wp_die();
			}


			/**
			 * Gets the variations prices
			 */	
			public function get_price_book_ajax() {

				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
					global $wpdb;

					$group  = $_GET['group'];

					$results = $wpdb->get_results( "SELECT * FROM `wp_woocommerce_addon_price_table` 
						INNER JOIN `wp_woocommerce_markup_manager_rules` 
						ON `wp_woocommerce_addon_price_table`.`field_label` = `wp_woocommerce_markup_manager_rules`.`variation` 
						WHERE `field_label` = '$group' ", OBJECT );

				    if (!$results) {
				    	// Build and execute the query
					    $results = $wpdb->get_results( "SELECT * FROM `wp_woocommerce_addon_price_table` 
					        WHERE `field_label` = '$group' ", OBJECT );	      
				    }
				    
				    echo json_encode($results ? $results : $wpdb->last_error);		

				}

				wp_die();	
			}						


		}

		// finally instantiate our plugin class and add it to the set of globals
		$GLOBALS['BD_made_to_measure_admin'] = new BD_made_to_measure_admin();		
	}

}

