<?php
/**
 * Plugin Name: 		Gravity Forms Global Forms
 * Description: 		Allows forms from the Gravity Forms plugin to be used across any site on a WordPress multisite installation via a new shortcode.
 * Version: 			1.0.1
 * Author:				John Russell
 * License: 			GPL-3.0-or-later
 * License URI:			https://www.gnu.org/licenses/gpl-3.0.html
 */



class GFGlobalForms {
	protected static $instance = null;

	protected function __construct() {
		$this->set_hooks();
	}



	/**
	 * Add shortcodes
	 * 
	 * @since 1.0.0
	 * 
	 * @return void
	 */
	protected function add_shortcodes(): void {
		add_shortcode('gravityform_global', array($this, 'shortcode_gravityform_global'));
	}



	/**
	 * Get a singleton instance
	 * 
	 * @since 1.0.0
	 * 
	 * @return GFGlobalForms
	 */
	public static function get_instance() {
		if (is_null(static::$instance)) {
			static::$instance = new static();
		}

		return static::$instance;
	}



	/**
	 * Maybe process a Gravity Forms form. This is necessary when a form from site A is being submit on site B since the form ID's won't match and the form doesn't exist on site B
	 * 
	 * @since 1.0.0
	 * 
	 * @return void
	 */
	protected function maybe_process_form(): void {
		if (isset($_POST['gravityform_global_site_id'])) {
			$site_id = intval($_POST['gravityform_global_site_id']);
			$current_blog_id = get_current_blog_id();

			if (is_int($site_id) && $site_id > 0) {
				if ($current_blog_id != $site_id) {
					switch_to_blog($site_id);
				}

				GFForms::maybe_process_form();

				if ($current_blog_id != $site_id) {
					restore_current_blog();
				}
			}
		}
	}



	/**
	 * Maybe shortcircuit Gravity Forms default form processing. This is needed in case the form ID from the origin site coincidentally matches a form ID on the current site, which could add an entry to the wrong form (and wrong site)
	 * 
	 * @since 1.0.0
	 * 
	 * @return void
	 */
	protected function maybe_short_circuit_form_processing(): void {
		if (isset($_POST['gravityform_global_site_id'])) {
			$site_id = intval($_POST['gravityform_global_site_id']);

			// If gravityform_global_site_id is set then prevent the Gravity Forms default form processing from happening (prevents double entries and entries into the wrong form)
			if (is_int($site_id) && $site_id) {
				remove_action('wp', array('GFForms', 'maybe_process_form'), 9);
				remove_action('admin_init', array('GFForms', 'maybe_process_form'), 9);
			}
		}
	}



	/**
	 * Set WordPress hooks
	 * 
	 * @since 1.0.0
	 * 
	 * @return void
	 */
	protected function set_hooks(): void {
		add_action('plugins_loaded', array($this, 'wp_hook_plugins_loaded'));
		add_action('init', array($this, 'wp_hook_init'));
		add_action('wp', array($this, 'wp_hook_wp'));
	}



	/**
 	 * Handler for the gravityform_global shortcode
   	 * 
	 * @since 1.0.0
	 * 
	 * @return string
	public function shortcode_gravityform_global($atts, $content = null) {
		global $blog_id, $wpdb;
	
		$atts = shortcode_atts(array(
			'title'					=> true,
			'description'				=> true,
			'id'					=> 0,
			'name'					=> '',
			'field_values'				=> '',
			'ajax'					=> false,
			'tabindex'				=> 0,
			'action'				=> 'form',
			'theme'					=> 'gravity-theme',
			'styles'				=> '',
			'form_url'				=> null,				// Added: This should be the only attribute needed for this to work (the domain and site_id can be derived from this)
			'redirect_to_origin'			=> false,				// Added (optional): Whether to submit the form to the current page (false) OR submit the form to the original form_url (true)
			'site_domain'				=> null,				// Added (optional): The domain of the site where the original form exists (only needed if 'form_url' isn't set)
			'site_id'				=> null,				// Added (optional): The site/blog ID of the site where the original form exists (only needed if 'form_url' and 'site_domain' aren't set)
		), $atts, 'gravityform_global');
	
		// Force NO AJAX since the confirmation doesn't work at this point if AJAX is used
		$atts['ajax'] = false;
	
		$current_blog_id = get_current_blog_id();
		$switched_to_blog = false;
		$response = '';
	
		// Prioritize using the "form_url" attribute, but IF this is not set then process the site_id and site_domain
		if (!empty($atts['form_url'])) {
			$temp_url_parts = parse_url(sanitize_url($atts['form_url']));
	
			if (is_array($temp_url_parts) && !empty($temp_url_parts['host'])) {
				$atts['site_domain'] = $temp_url_parts['host'];
				$atts['site_id'] = get_blog_id_from_url($atts['site_domain']);
			}
		} else {
			// check site_id (set to current site_id if empty)
			if (empty($atts['site_id'])) {
				$atts['site_id'] = get_current_blog_id();
			}
	
			// set site_id from site_domain
			if (!empty($atts['site_domain'])) {
				$temp_blog_id = get_blog_id_from_url($atts['site_domain']);
	
				// Only override the site_id if the temp_blog_id is not empty (likely 0)
				if (!empty($temp_blog_id)) {
					$atts['site_id'] = $temp_blog_id;
				}
			} else {
				// Set the site_domain from the site_id to ensure there is a valid site_domain
				$temp_blog_details = get_blog_details($atts['site_id']);
	
				$atts['site_domain'] = $temp_blog_details->domain;
			}
		}
	
		// render the form and save to $response
		if (class_exists('GFForms') && method_exists('GFForms', 'parse_shortcode') && !empty($atts['site_id'])) {
			if ($current_blog_id != $atts['site_id']) {
				// Switch to the site where the form exists
				switch_to_blog($atts['site_id']);
	
				$switched_to_blog = true;
			}
	
			// Render the form
			$response = GFForms::parse_shortcode($atts, $content);
	
			if ($switched_to_blog === true) {
				// Restore the original site
				restore_current_blog();
			}
			
		} else {
			$response = '<p>Oops! This form could not be found.</p>';
		}
	
		// set the form action to point to the desired site (only works on subdomain multisite configurations)
		if ($atts['ajax'] == true) {
			$response = preg_replace('/action=\'.*#/', 'action=\'' . $atts['form_url'] . '#', $response);
		}
		
		// If redirect_to_origin is set to true then replace the default action with the full URL of 'form_url'
		if (!empty($atts['form_url']) && $atts['redirect_to_origin'] == true) {
			$response = preg_replace('/action=\'.*?\'/', 'action=\'' . $atts['form_url'] . '\'', $response);
		} else {
			// Add hidden fields for submission processing - ONLY necessary if processing on a remote site (not the origin)
			$hidden_fields = '<input type="hidden" name="gravityform_global_site_id" value="' . $atts['site_id'] . '"/>';
			$response = preg_replace('/(<form.*?>)/', '${1}' . $hidden_fields, $response);
		}
	
		return $response;
	}



	/**
	 * WordPress Hook: init
	 * 
	 * @since 1.0.0
	 * 
	 * @return void
	 */
	public function wp_hook_init(): void {
		$this->add_shortcodes();
	}



	/**
	 * WordPress Hook: plugins_loaded
	 * 
	 * @since 1.0.0
	 * 
	 * @return void
	 */
	public function wp_hook_plugins_loaded(): void {
		$this->maybe_short_circuit_form_processing();
	}



	/**
	 * WordPress Hook: wp
	 * 
	 * @since 1.0.0
	 * 
	 * @return void
	 */
	public function wp_hook_wp(): void {
		$this->maybe_process_form();
	}
}
GFGlobalForms::get_instance();
