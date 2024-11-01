<?php
/*
Plugin Name: Vma plus Station
Description: Vma plus Station Plugin is a plugin that allows you to easily build a metaverse on WordPress. It enables users to initiate a virtual space and offers features such as free movement within the virtual environment and the option to set destination URLs as thumbnails.
Version: 2.1.2
Author: Vma plus Co,.Ltd.
Author URI: https://www.vma-plus.com/
Text Domain: vma-plus-station
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 4.7
Requires PHP: 7.0
*/

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) exit;

// Define plugin version 
// Change this version everytime upgrade the plugin file  
define('PLUGIN_VERSION', '2.1.2');

// Plugin plan
define('PLUGIN_PLAN','FREE');
if ( ! function_exists( 'vps_fs' ) ) {
    // Create a helper function for easy SDK access.
    function vps_fs() {
        global $vps_fs;

        if ( ! isset( $vps_fs ) ) {
            // Include Freemius SDK.
            require_once dirname(__FILE__) . '/freemius/start.php';

            $vps_fs = fs_dynamic_init( array(
                'id'                  => '16319',
                'slug'                => 'vma-plus-station',
                'premium_slug'        => 'vma-plus-station-pro',
                'type'                => 'plugin',
                'public_key'          => 'pk_5c22396adb9cbd14123b35b3c0c7a',
                'is_premium'          => false,
                'has_addons'          => false,
                'has_paid_plans'      => true,
				'is_org_compliant'    => true,
                'menu'                => array(
                    'slug'           => 'vma-plus-plugin-setting',
                    'first-path'     => 'admin.php?page=metaverse-plugin-setting',
                    'contact'        => false,
                    'support'        => false,
					'pricing'		 => false,
					'account'		 => false,
                ),
				'anonymous_mode'	=> true,
                'is_live'          => true,
            ) );
        }

        return $vps_fs;
    }

    // Init Freemius.
    vps_fs();
    // Signal that SDK was initiated.
    do_action( 'vps_fs_loaded' );
}
vps_fs()->add_action('after_uninstall', 'delete_on_uninstall');

// Function to handle uninstall tasks
if (!function_exists('delete_on_uninstall')) {
    function delete_on_uninstall() {
        // Define the base option name
        $option_base = 'wporg_options';

		$template_section = 'template_sections';

        // Retrieve post IDs by content
        $search_content = '[vmaplus_custom_page]';
        $post_ids = get_page_ids_by_content($search_content);

		$option_name = $option_base;
		error_log("Processing option_name: $option_name");
		if (get_option($option_name) !== false) {
			delete_option($option_name);
			error_log("Deleted option: $option_name");
		}
		
		//Delete template_section that created by this plugin.
		if (get_option($template_section) !== false) {
			delete_option($template_section);
			error_log("Deleted option: $template_section");
		}

        // Delete each post
        foreach ($post_ids as $post_id) {
            if (get_post($post_id) !== null) {
                wp_delete_post($post_id, true); // 'true' for force delete
                error_log("Deleted post ID: $post_id");
            }
        }
    }
}

// Function to get page IDs containing specific content
if (!function_exists('get_page_ids_by_content')) {
	function get_page_ids_by_content($search_content) {
		// Define the query arguments
		$args = array(
			'post_type'   => 'page',
			'post_status' => 'any',
			'fields'      => 'ids',
			's'           => $search_content,
			'posts_per_page' => -1
		);

		// Create a new WP_Query instance
		$query = new WP_Query($args);

		// Return the array of post IDs
		return $query->posts;
	}
}

if ( ! class_exists( 'Vmaplus_Metaverse_Plugin_Setting' ) ) {
class Vmaplus_Metaverse_Plugin_Setting {

	/**
	 * Capability required by the user to access the My Plugin menu entry.
	 *
	 * @var string $capability
	 */
	private $capability = 'manage_options';

	protected $templates;

	/**
	 * Array of fields that should be displayed in the settings page.
	 *
	 * @var array $fields
	 */
	private $fields = [
		[
			'id' => 'site-path',
			'label' => 'Site Path',
			'description' => 'Define the site path for the metaverse',
			'type' => 'text',
		],
	];

	private $metaverse_section;
	private $column = [
		[
			'id' => 'poster_image_',
			'label' => 'Image_preview',
			'description' => '',
			'type' => 'poster',
		],
		[
			'id' => 'poster_image',
			'label' => 'Image',
			'description' => 'Upload the image for metaverse',
			'type' => 'upload',
		],
		[
			'id' => 'poster_url',
			'label' => 'Click URL',
			'description' => '',
			'type' => 'url',
		],
	];

	/**
	 * The Plugin Settings constructor.
	 */
	function __construct() {
		add_action( 'admin_init', [$this, 'settings_init'] );
		add_action( 'admin_init', [$this,'handle_metaverse_form_submission'] );
		add_action( 'admin_menu', [$this, 'options_page'] );
		add_action( 'admin_head', array($this, 'add_custom_css'));
		add_action( 'admin_footer', array($this, 'add_custom_js'));
		add_action('plugins_loaded', [$this,'vma_plus_station_load_textdomain']);

		// Initialize metaverse_section array
        $this->metaverse_section = [];

        // Populate metaverse_section array using a loop
        foreach (range(1, 18) as $i) {
            $this->metaverse_section[] = [
                'id' => 'image-' . $i,
                'title' => 'Image ' . $i,
            ];
        }
				
		// add_filter( 'plugins_api', 'vma_plus_view_details', 9999, 3 );

		$this->templates = array();

		// Display the link with the plugin meta.
		// add_filter( 'plugin_row_meta', array( $this, 'plugin_links' ), 10, 4 );


		// Add a filter to the attributes metabox to inject template into the cache.
		if ( version_compare( floatval( get_bloginfo( 'version' ) ), '4.7', '<' ) ) {

			// 4.6 and older
			add_filter(
				'page_attributes_dropdown_pages_args',
				array( $this, 'register_project_templates' )
			);

		} else {

			// Add a filter to the wp 4.7 version attributes metabox
			add_filter(
				'theme_page_templates', array( $this, 'add_new_template' )
			);

		}

		// Add a filter to the save post to inject out template into the page cache
		add_filter(
			'wp_insert_post_data',
			array( $this, 'register_project_templates' )
		);
		


		// Add a filter to the template include to determine if the page has our
		// template assigned and return it's path
		add_filter(
			'template_include',
			array( $this, 'view_project_template')
		);


		// Add your templates to this array.
		$this->templates = array(
			'playcanvas/index.php' => 'Metaverse',
		);

		// Restrict template usage
        add_filter('theme_page_templates', [$this, 'restrict_custom_template_selection'], 10, 4);
        add_action('save_post', [$this, 'restrict_custom_template_save'], 10, 3);
        add_action('admin_notices', [$this, 'custom_template_error_notice']);
		//upgrade notification and settings button in plugin installed
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this,'vma_plus_plugin_action_links']);
		if (PLUGIN_PLAN != "PRO") {
			add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this,'vma_plus_upgrade_link']);
			add_action('admin_notices', [$this,'vma_plus_upgrade_notice'] );
		}

	}
	// Function to check if the custom template is already used
    public function is_custom_template_used($template) {
        $args = array(
            'post_type' => 'page', // Change to your custom post type if needed
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_wp_page_template',
                    'value' => $template
                )
            )
        );

        $query = new WP_Query($args);
        return $query->have_posts();
    }

    // Restrict template selection in admin
    public function restrict_custom_template_selection($post_templates, $wp_theme, $post, $post_type) {
		$template = 'playcanvas/index.php'; 
	
		// Check if $post is null or not an object
		if (!$post || !is_object($post)) {
			return $post_templates;
		}
	
		// Check if the current page uses the custom template
		$current_template = get_post_meta($post->ID, '_wp_page_template', true);
		
		// Check if the current template matches the desired template
		if ($current_template === $template) {
			return $post_templates;
		}
	
		// Check if the custom template is used on any page
		if ($this->is_custom_template_used($template)) {
			unset($post_templates[$template]);
		}
	
		return $post_templates;
	}
	
	function vma_plus_station_load_textdomain() {
		load_plugin_textdomain('vma-plus-station', false, dirname(plugin_basename(__FILE__)) . '/languages');
	}

    // Prevent template assignment during save
    public function restrict_custom_template_save($post_id, $post, $update) {
        if ($post->post_type !== 'page') { 
            return;
        }

        $template = 'playcanvas/index.php'; 

        if (isset($_POST['_wp_page_template']) && $_POST['_wp_page_template'] === $template) {
            if ($this->is_custom_template_used($template) && get_post_meta($post_id, '_wp_page_template', true) !== $template) {
                update_post_meta($post_id, '_wp_page_template', '');
                add_filter('redirect_post_location', function($location) {
                    return add_query_arg('custom_template_error', '1', $location);
                });
            }
        }
    }

    // Display admin notice
    public function custom_template_error_notice() {
        if (isset($_GET['custom_template_error'])) {
            echo '<div class="notice notice-error is-dismissible">
                <p>' . esc_html(__('This template can only be used on one post at a time.', 'vma-plus-station')) . '</p>
            </div>';
        }
    }

	public function add_new_template( $posts_templates ) {
		$posts_templates = array_merge( $posts_templates, $this->templates );
		return $posts_templates;
	}

	/**
	 * Adds our template to the pages cache in order to trick WordPress
	 * into thinking the template file exists where it doens't really exist.
	 */
	public function register_project_templates( $atts ) {

		// Create the key used for the themes cache
		$cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );

		// Retrieve the cache list.
		// If it doesn't exist, or it's empty prepare an array
		$templates = wp_get_theme()->get_page_templates();
		if ( empty( $templates ) ) {
			$templates = array();
		}

		// New cache, therefore remove the old one
		wp_cache_delete( $cache_key , 'themes');

		// Now add our template to the list of templates by merging our templates
		// with the existing templates array from the cache.
		$templates = array_merge( $templates, $this->templates );

		// Add the modified cache to allow WordPress to pick it up for listing
		// available templates
		wp_cache_add( $cache_key, $templates, 'themes', 1800 );

		return $atts;

	}

	function plugin_links( $plugin_meta, $plugin_file, $plugin_data ) {
        if ( __FILE__ === path_join( WP_PLUGIN_DIR, $plugin_file ) ) {
            // Here you supply the link to your README. 
            $url = plugins_url( 'readme.txt', __FILE__ );

            // This is an adaptation of part of `WP_Plugins_List_Table->single_row()`.
			$plugin_meta[] = sprintf(
                '<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
                add_query_arg( 'TB_iframe', 'true', $url ),
				// translators: %s: Plugin Name
                esc_attr( sprintf( _x( 'More information about %s', 'plugin context', 'vma-plus-station' ), $plugin_data['Name'] ) ),
				esc_attr( $plugin_data['Name'] ),
                __( 'View details' )
            );
        }
        return $plugin_meta;
    }

	function playcanvas_script(){
		$current_template = get_page_template_slug();
		if ($current_template === 'playcanvas/index.php') {

			wp_register_script('modules', plugins_url('/__modules__.js', __FILE__), array(),PLUGIN_VERSION , false );
			wp_enqueue_script('modules');
		
			wp_register_script('start', plugins_url('/__start__.js', __FILE__), array(),PLUGIN_VERSION , false  );
			wp_enqueue_script('start');
		
			wp_register_script('loading', plugins_url('/__loading__.js', __FILE__), array(),PLUGIN_VERSION , false  );
			wp_enqueue_script('loading');
		
			wp_register_script('pc', plugins_url('/playcanvas-stable.min.js', __FILE__), array(),PLUGIN_VERSION , false );
			wp_enqueue_script('pc');
		
			wp_register_script('settings', plugins_url('/__settings__.js', __FILE__), array(),PLUGIN_VERSION , false  );
			wp_enqueue_script('settings');
			wp_add_inline_scripts('settings', 'var PLUGIN_URL = ' . plugins_url(), 'before');

			wp_enqueue_style( 'playcanvas_css', plugins_url('/styles.css', __FILE__), array(),PLUGIN_VERSION , true );

		} 
	}

	function callHTMLPage() {
		$metaverse_option = get_option('wporg_options');

		return '<h1>' . $metaverse_option['site-path'] . '</h1>';// Return the buffered HTML content

	}


	/**
	 * Register the settings and all fields.
	 */
	function settings_init() : void {

		// Register a new setting this page.
		register_setting( 'my-plugin-settings', 'wporg_options' );
		register_setting( 'metaverse-setting', 'wporg_options' );
		add_shortcode( 'vmaplus_custom_page', 'callHTMLPage' );
		add_action( 'wp_enqueue_scripts', 'playcanvas_script' );
		add_action( 'admin_enqueue_scripts', function(){
			wp_enqueue_media();
			wp_enqueue_script('media-upload');
			wp_enqueue_script('thickbox');
			wp_enqueue_style('thickbox');
			wp_register_script('my-upload', plugins_url('/upload-script.js', __FILE__), array('jquery','media-upload','thickbox'), PLUGIN_VERSION, false );
			wp_enqueue_script('my-upload');
			wp_localize_script('my-upload', 'myLocalizedStrings', array(
				'selectImageTitle' => __('Select Image', 'vma-plus-station'),
				'useImageButtonText' => __('Use this image', 'vma-plus-station'),
				'noFileSelectedText' => __('No file selected', 'vma-plus-station')
			));
			wp_register_style( 'plugin-css', plugins_url('/styles.css', __FILE__), array(),PLUGIN_VERSION , false);
			wp_enqueue_style( 'plugin-css');
			wp_localize_script('jquery', 'myTranslation', array(
				'confirmationTitle' => __('Confirmation', 'vma-plus-station'),
				'confirmationMessage' => __('NOTE: If you are unable to upload your purchased file, please see <a href="https://wordpress.org/support/topic/how-to-increase-maximum-upload-file-size-from-2mb/" target="_blank">here.</a>', 'vma-plus-station'),
				'cancelButton' => __('Cancel', 'vma-plus-station'),
				'proceedButton' => __('Proceed', 'vma-plus-station'),
				'deleteSuccess' => __('Page deleted successfully.', 'vma-plus-station'),
				'deleteFail' => __('Failed to delete page.', 'vma-plus-station'),
				'copySuccess' => __('System information copied to clipboard.', 'vma-plus-station'),
				'copyFail' => __('Failed to copy system information.', 'vma-plus-station'),
			));

			wp_enqueue_style( 'bootstrap', plugins_url('/bootstrap.css', __FILE__), PLUGIN_VERSION, true );
			wp_enqueue_style( 'fontawesome', plugins_url('/fontawesome.all.css', __FILE__), PLUGIN_VERSION, true );
			wp_register_script('bootstrapScript', plugins_url('/bootstrap.bundle.js',__FILE__),array(),PLUGIN_VERSION , false );
			wp_enqueue_script('bootstrapScript');
		} );

		apply_filters('media_upload_default_tab', 'library');

		// Register a new section.
		add_settings_section(
			'my-plugin-settings-section',
			__('MANAGE SITE PATH', 'vma-plus-station'),
			[$this, 'render_section'],
			'my-plugin-settings'
		);

		add_settings_section(
			'metaverse-setting-section',
			__('MANAGE IMAGE', 'vma-plus-station'),
			[$this, 'render_metaverse_section'],
			'metaverse-setting',
			[
				'before_section' => '<div class="metaverse-image-section">',
				'after_section' => '</div>',
			]
		);

		foreach( $this->metaverse_section as $section) {
			add_settings_section(
				$section['id'],
				$section['title'],
				[$this, 'render_metaverse_section'],
				'metaverse-setting',
				[
					'before_section' => '<div class="metaverse-image-setting">',
					'after_section' => '</div>',
				]
			);
		}

		foreach( $this->metaverse_section as $key=>$section) {
			$index = $key + 1;
			foreach( $this->column as $field ) {
				// Register a new field in the main section.
				add_settings_field(
					$field['id'] . ' ' . $section['id'], /* ID for the field. Only used internally. To set the HTML ID attribute, use $args['label_for']. */
					$field['label'], /* Label for the field. */
					[$this, 'render_field'], /* The name of the callback function. */
					'metaverse-setting', /* The menu page on which to display this field. */
					$section['id'], /* The section of the settings page in which to show the box. */
					[
						'label_for' => $field['id'] . ' ' . $section['id'], /* The ID of the field. */
						'class' => 'metaverse_settings', /* The class of the field. */
						'field' => [
							'id' => $field['id'] . $index,
							'label' => $field['label'],
							'description' => $field['description'],
							'type' => $field['type'],
						] /* Custom data for the field. */
					]
				);
			}
		}

		/* Register All The Fields. */
		foreach( $this->fields as $field ) {
			// Register a new field in the main section.
			add_settings_field(
				$field['id'], /* ID for the field. Only used internally. To set the HTML ID attribute, use $args['label_for']. */
				$field['label'], /* Label for the field. */
				[$this, 'render_field'], /* The name of the callback function. */
				'my-plugin-settings', /* The menu page on which to display this field. */
				'my-plugin-settings-section', /* The section of the settings page in which to show the box. */
				[
					'label_for' => $field['id'], /* The ID of the field. */
					'class' => 'wporg_row', /* The class of the field. */
					'field' => $field, /* Custom data for the field. */
				]
			);
		}
	}

	
 
	// function vma_plus_view_details( $res, $action, $args ) {
	// 	if ( 'plugin_information' !== $action ) return $res;
	// 	if ( $args->slug !== 'vma-plus' ) return $res;
	// 	$res = new stdClass();
	// 	$res->name = 'Vma plus Station Plugin';
	// 	$res->slug = 'vma-plus';
	// 	$res->path = 'vma-plus/vma-plus.php';
	// 	$res->sections = array(
	// 		'description' => 'Vma plus Station Plugin は、Wordpress でメタバースが構築できるプラグインです。',
	// 	);
	// 	return $res;
	// }

	//function to add custom action links for Settings
	function vma_plus_plugin_action_links($links) {
		$settings_link = '<a href="admin.php?page=metaverse-plugin-setting">' . esc_html(__('Settings', 'vma-plus-station')) . '</a>';
		// Add the settings link to the array of links
		array_unshift($links, $settings_link);
		return $links;
	}

	//function to add custom action links for upgrade
	function vma_plus_upgrade_link($links) {
		$settings_link = '<a href="admin.php?page=upgrade-plugin">' . esc_html(__('Upgrade to Pro', 'vma-plus-station')) . '</a>';
		// Add the settings link to the array of links
		array_unshift($links, $settings_link);
		return $links;
	}

	//function to display an admin notice for upgrade
	public function vma_plus_upgrade_notice() {
		// Check if we are on the plugins.php page and it's your plugin
		$screen = get_current_screen();
		if ($screen && strpos($screen->id, 'vma-plus') !== false && strpos($screen->id, 'vma-plus-station-pro') === false ) {

			//add class
			$this->class_add_js_();
			// Display your upgrade notice here
			$upgrade_url = 'admin.php?page=upgrade-plugin';
			// Translated and escaped text
			$notice_message = __('Upgrade to Pro for additional features! <a href="%s" target="_blank">%s</a>', 'vma-plus-station');

			// Format the notice message with escaped URL and button text
			$formatted_message = sprintf(
				$notice_message,
				esc_url($upgrade_url),
				esc_html(__('Upgrade Now', 'vma-plus-station'))
			);
			echo '<div class="notice notice-info"><p>' . $formatted_message . '</p></div>';

			//echo '<div class="notice notice-info" ><p>Upgrade to Pro for additional features! <a href="' . esc_url($upgrade_url) . '" target="_blank">Upgrade Now</a></p></div>';
		}
	}

	/**
	 * Add a subpage to the WordPress Settings menu.
	 */
	function options_page() : void {
		// add_plugins_page(
		// 	'Vma plus Station', /* Page Title */
		// 	'Vma plus Station', /* Menu Title */
		// 	$this->capability, /* Capability */
		// 	'metaverse-plugin-setting', /* Menu Slug */
		// 	[$this, 'render_options_page'], /* Callback */
		// 	'dashicons-menu', /* Icon */
		// 	'6', /* Position */
		// );
		require_once plugin_dir_path( __FILE__ ) . 'metaverses-settings.php';
		require_once plugin_dir_path( __FILE__ ) . 'worlds-settings.php';
		require_once plugin_dir_path( __FILE__ ) . 'about.php';

		add_menu_page(
			'', /* Page Title */
			'Vma plus Station', /* Menu Title */
			$this->capability, /* Capability */
			'vma-plus-plugin-setting', /* Menu Slug */
			'', /* Callback */
			//'dashicons-menu', /* Icon */
			'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDE1LjA4NiIgaGVpZ2h0PSIyNzYuNDgiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTIxOC45MjcgMjEwLjQxMmMtMTYuNTg2LTM2LjMzNy0zMC4yMjQtNjYuMzk3LTMwLjMwNy02Ni44LS4xMy0uNjI5IDMuOTY1LS43NTQgMjguNzM0LS44NzdsMjguODg2LS4xNDMgMi41MTEtMS4yMjNjMi44OTQtMS40MSA1LjgxNC00LjcwOCA2LjcwMi03LjU3LjMzNC0xLjA3Ny43MjMtNC40MzYuODY0LTcuNDYzbC4yNTYtNS41MDRoLTg1Ljk0bC01LjcyIDMwLjc3MmMtNS40MjQgMjkuMTgtNS44MiAzMC45ODgtNy42NSAzNC45NDQtMy40NzMgNy41MS04LjM5NiAxMi4xMTUtMTYuMDkzIDE1LjA1bC0zLjY5NCAxLjQxLTIxLjYzNS4xNTZjLTE4LjA1LjEzLTIyLjIyMi4wMjUtMjUuMTczLS42MzMtMTAuMjU2LTIuMjg3LTE3LjU4Ny05LjczMy0yMS43OTctMjIuMTM2LS43NDktMi4yMDYtMTYuMzYyLTQzLjE3OS0zNC42OTYtOTEuMDUxUy42NTEgMS43ODYuNDIgMS4xNTJMMCAwaDQ0LjkybDMxLjc2MiA3Ni42NzJjMTcuNDY5IDQyLjE3IDMyLjEyMyA3Ny42OTUgMzIuNTY0IDc4Ljk0NiAxLjE1IDMuMjU1IDIuMzU0IDQuMTI2IDUuNzAzIDQuMTI2IDMuOTkyIDAgNC4zNDQtLjU2MiA1Ljg3NC05LjM4Mi42OTItMy45OTIgMy43NzktMjEuODg4IDYuODU4LTM5Ljc3bDUuNi0zMi41MTIgODMuNDMzLS4xMyA4My40MzMtLjEyOVY0My4wMDhoMzguOTQ2Vi41MTJoMzcuMDQ3djQyLjQ5NmgzOC45NDZ2MzkuNDI0SDM3Ni4xNHY0MS45ODRoLTM3LjA0N1Y4Mi40MzJoLTM4Ljk0NmwtLjAwOCAyMS4xMmMtLjAwNSAxMS42MTYtLjIxOSAyMy4zMDktLjQ3NiAyNS45ODQtMS42NDIgMTcuMDY4LTguNzk2IDMyLjI3My0xOS45MDcgNDIuMzEzLTguMzQzIDcuNTM3LTE3LjkwNSAxMS42NTUtMzAuOTg3IDEzLjM0MmwtMy4xNy40MDkgMjIuMzM3IDQ0LjhjMTIuMjg2IDI0LjY0IDIyLjQ0NSA0NS4wODggMjIuNTc2IDQ1LjQ0LjE4OC41MDYtNC4xMTguNjQtMjAuNTk2LjY0aC0yMC44MzN6IiBmaWxsPSIjYWFhIi8+PHBhdGggZD0iTTI0Ny44MyAyNzMuMjhjLTUuMDItMTAuNjMzLTU5LjIzLTEyOS42NS01OS4xMTEtMTI5Ljc4LjA4LS4wODUgMTIuNzM0LS4yMTUgMjguMTItLjI4OCAzMi4zNDMtLjE1MiAzMS4zNTgtLjAyNCAzNS42NDMtNC42NDMgMy4wNjktMy4zMDggMy45Ny02LjI1IDMuOTctMTIuOTY2di00Ljc3MWgtNDIuOTg0Yy0yMy42NCAwLTQyLjk4My4wODMtNDIuOTgzLjE4NSAwIDEuNDk1LTExLjEzNyA1OS43OS0xMS44MzMgNjEuOTM5LTIuNDcgNy42MjUtNy4xNDYgMTMuNDA2LTEzLjUxMyAxNi43MDctNi43MDYgMy40NzctOC42MzQgMy42ODMtMzIuNjQgMy40OTJsLTIxLjA5NC0uMTY5LTMuODc0LTEuNDA0Yy04LjctMy4xNTQtMTQuNjYtOS43Ny0xOC4xOTYtMjAuMkM2OC4wNTcgMTc3LjYxMS42NDQgMS4yNjEuMTUzLjQwNS4wMjUuMTgzIDEwLjAzMi4wMDEgMjIuMzkuMDAzbDIyLjQ3LjAwMyAzMi4xNzggNzcuNzAzYzE3LjY5OCA0Mi43MzcgMzIuMTc4IDc3Ljk3IDMyLjE3OCA3OC4yOTQgMCAuMzI1LjU1MyAxLjMgMS4yMyAyLjE2NiAxLjEyIDEuNDM1IDEuNDgxIDEuNTc1IDQuMDY4IDEuNTc1IDMuNTU4IDAgNC41MTItLjU0NyA1LjE0Ny0yLjk1Mi4yNzctMS4wNTIgMy4zODMtMTguNzMxIDYuOTAyLTM5LjI4OCAzLjUxOC0yMC41NTcgNi41MDctMzcuODg1IDYuNjQtMzguNTA4bC4yNDQtMS4xMzIgODMuMTEzLS4wMjUgODMuMTEyLS4wMjZ2MjYuMDI4YzAgMjguNTA3LS4xOTcgMzEuMjYyLTIuODc0IDQwLjEwNC03LjA2NiAyMy4zNDUtMjUuMzY2IDM5LjMyNi00Ni42NCA0MC43My0yLjU0Ny4xNjktNC42My40NTUtNC42My42MzggMCAuMTgyIDkuNjM3IDE5LjY3NSAyMS40MTcgNDMuMzE3IDExLjc4IDIzLjY0MyAyMS45MzUgNDQuMDggMjIuNTY4IDQ1LjQxOGwxLjE1MSAyLjQzMmgtNDEuMzIzem05MS4yNjMtMTY5Ljg1NlY4Mi40MzJoLTM4Ljk0NlY0My4wMDhoMzguOTQ2Vi41MTJoMzcuMDQ3djQyLjQ5NmgzOC45NDZ2MzkuNDI0SDM3Ni4xNHY0MS45ODRoLTM3LjA0N3oiIGZpbGw9IiM4YzhjOGMiLz48cGF0aCBkPSJNMjM4LjYzNCAyNTMuMDU2bC0zMC4zMjItNjYuNDMyLTE5LjYzMS00My4wMDggMjguMzA1LS4yOGMxOC4xOTYtLjE4MSAyOC44MTgtLjQ3NyAyOS43NDItLjgyOCA3LjE1Ni0yLjcyMiA5LjI0LTYuMTIgOS42MDktMTUuNjZsLjIzMi02LjAxNmgtODYuMDY2bC0uMjU0IDEuMTUyYy0uMTQuNjM0LTIuNzExIDE0LjM4OS01LjcxNCAzMC41NjctNS4xNDggMjcuNzM1LTUuNTczIDI5LjY2Ni03LjQyNSAzMy43OTItMy40MzMgNy42NDktOS41OTYgMTMuMDktMTcuNzY4IDE1LjY4OC00LjE2OSAxLjMyNi00Mi4wNzUgMS43MS00Ny44NzMuNDg2LTEwLjQ2My0yLjIxLTE3Ljg0NC05LjI1Ni0yMS45OTctMjEuMDAzLS42OTktMS45NzctMTYuNDMyLTQzLjIyMy0zNC45NjMtOTEuNjU4QzE1Ljk3OSA0MS40MjEuNzA5IDEuMzg2LjU3Ny44OTFjLS4yMjYtLjg1Mi45NjYtLjg5NCAyMi4wMS0uNzY4bDIyLjI0OS4xMzNMNzYuNjcgNzcuMTA5YzE3LjUwOCA0Mi4yNjggMzIuMjkgNzguMDIyIDMyLjg0NyA3OS40NTIuOTA1IDIuMzIxIDEuMjMgMi42NjIgMy4wMjIgMy4xODMgMi43OC44MDcgNS41NDMuMjI0IDYuNTE0LTEuMzc0LjQ4Ny0uOCAzLjI5OC0xNS44ODQgNy41MzYtNDAuNDM2IDMuNzE4LTIxLjUzOCA2Ljg4LTM5LjI5IDcuMDI5LTM5LjQ1LjE0OC0uMTYgMzcuNTctLjQwNyA4My4xNjItLjU1bDgyLjg5Mi0uMjU5djI1Ljg0YzAgMjguODA4LS4xODMgMzEuMTkxLTMuMTMyIDQwLjgwNC0zLjg5MiAxMi42ODItMTEuNDY4IDIzLjc0LTIxLjA5IDMwLjc4NS03LjIzMiA1LjI5NC0xNy4xMzQgOS4wMS0yNS41MyA5LjU4LTIuNDE1LjE2NC00LjM5Mi41MDktNC4zOTIuNzY2IDAgLjM3OCAzNy41NjQgNzYuMTcyIDQzLjUzIDg3LjgzbDEuNjM3IDMuMmgtNDEuMzY4em0xMDAuNDYtMTQ5LjYzMlY4Mi40MzJoLTM4Ljk0N1Y0My4wMDhoMzguOTQ2Vi41MTJoMzcuMDQ3djQyLjQ5NmgzOC45NDZ2MzkuNDI0SDM3Ni4xNHY0MS45ODRoLTM3LjA0N3oiIGZpbGw9IiM2ZTZlNmUiLz48L3N2Zz4=',
			99, /* Position (一番下)*/
		);
		add_submenu_page(
			'vma-plus-plugin-setting',
			__('Metaverses','vma-plus-station'), /* Page Title */
			__('Metaverses','vma-plus-station'), /* Menu Title */
			$this->capability, /* Capability */
			'metaverse-plugin-setting', /* Menu Slug */
			[$this, 'render_options_page_callback'], /* Callback */ 
		);
		add_submenu_page(
			'vma-plus-plugin-setting',
			__('Worlds','vma-plus-station'), /* Page Title */
			__('Worlds','vma-plus-station'), /* Menu Title */
			$this->capability, /* Capability */
			'worlds-plugin-setting', /* Menu Slug */
			'worlds_render', /* Callback */ 
		);
		add_submenu_page(
			'vma-plus-plugin-setting',
			__('About Us','vma-plus-station'), /* Page Title */
			__('About Us','vma-plus-station'), /* Menu Title */
			$this->capability, /* Capability */
			'about-plugin', /* Menu Slug */
			'about_render', /* Callback */ 
		);
		add_submenu_page(
			'vma-plus-plugin-setting',
			__('Upgrade to Pro','vma-plus-station'), /* Page Title */
			__('Upgrade to Pro','vma-plus-station'), /* Menu Title */
			$this->capability, /* Capability */
			'upgrade-plugin', /* Menu Slug */
			[$this, 'upgrade_render'], /* Callback */
		);

		remove_submenu_page('vma-plus-plugin-setting','vma-plus-plugin-setting');
	}
	
	public function render_options_page_callback() {
		
		$args = array(
			'post_type' => 'page',
			'post_status' => 'publish',
			'title' => ucwords('PlayCanvas'),
		);
		// Get pages matching the query
		$pages = get_posts($args);


		if (isset($_POST['create_playcanvas_page'])) {
			// Verify nonce
			if (!isset($_POST['create_playcanvas_page_nonce']) || !wp_verify_nonce($_POST['create_playcanvas_page_nonce'], 'create_playcanvas_page_action')) {
				wp_die('Security check'); // Nonce verification failed
			}
	
			// Check if the page already exists
			$page_exists = false;
			$query = array(
				'post_type' => 'page',
				'post_status' => 'publish',
				'title' => ucwords('PlayCanvas'),
			);
			$playcanvas_pages = get_posts($query);
			$check_page_exist = null;
			
	
			if (!empty($playcanvas_pages)) {
				$playcanvas_page = $playcanvas_pages[0];
				$check_page_exist = $playcanvas_page->ID;
			}
	
			// Create the page if it doesn't exist
			if (!isset($check_page_exist)) {
				$page_id = wp_insert_post(array(
					'post_type' => 'page',
					'post_status' => 'publish',
					'post_title' => ucwords('PlayCanvas'),
					'post_name' => sanitize_title('PlayCanvas'),
					'post_content' => '[vmaplus_custom_page]',
				));
				update_post_meta($page_id, '_wp_page_template', 'playcanvas/index.php'); // Set page template
				$page_exists = true;
			} else {
				$page_exists = true;
			}
	
			// Show admin notice if page already exists
			if ($page_exists) {
				add_action('admin_notices', function() {
					?>
					<div class="notice notice-warning is-dismissible">
						<p><?php echo esc_html(__('The PlayCanvas page already exists.', 'vma-plus-station')); ?></p>
					</div>
					<?php
				});
			}
			// Include JavaScript to update the button immediately
            add_action('admin_footer', function() {
                ?>
                <script type="text/javascript">
                    document.addEventListener('DOMContentLoaded', function() {
                        let button = document.querySelector('.create-button-lock');
                        if (button) {
							button.disabled = true;
							button.innerHTML = '<img src="' + '<?php echo esc_url(plugin_dir_url(__FILE__) . "assets/images/lock-icon.svg"); ?>' + '" alt="Lock Icon" width="20" height="20" style="margin-right:8px;">' +
							'<span style="vertical-align: middle;"><?php echo esc_html(__('Create New Site','vma-plus-station')) ;?></span>' +
							'<span class="pro-badge">Pro</span>';
                        }
                    });
                </script>
                <?php
			});
		}
	
		// Render the options page content
		?>
		<div class="wporg">
		<div>
			<style>
				.heading {
					display: flex;
					align-items: center;
					justify-content: space-between;
				}

				.align-end {
					text-align: end;
				}

				.button-list {
					margin: 0px 5px;
					border-radius: 8%;
					padding: 0px 11px;
				}

				.sites-list {
					display: flex;
					justify-content: space-between;
					align-items: center;
					margin-right: 5px;
				}

				.view, .edit {
					background-color: #2271b1;
					color: #f6f7f7;
					padding: 4px 10px;
					text-align: center;
					text-decoration: none;
					font-size: 13px;
					font-weight: 400;
					border-radius: 3px;
					position: relative;
					overflow: hidden;
					border: 1px solid #f6f7f7;
				}
				
				.delete {
					background-color: #B13222;
					color: #f6f7f7;
					padding: 4px 10px;
					text-align: center;
					text-decoration: none;
					font-size: 13px;
					font-weight: 400;
					border-radius: 3px;
					position: relative;
					overflow: hidden;
					border: 1px solid #f6f7f7;
				}

				/* Modal (Confirmation Box) */
				.modal {
					display: none; /* Hidden by default */
					position: fixed; /* Stay in place */
					left: 0;
					top: 0;
					width: 100%; /* Full width */
					height: 100%; /* Full height */
					overflow: auto; /* Enable scroll if needed */
					background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
				}

				/* Modal Content/Box */
				.modal-content {
					background-color: #EEEEEE;
					margin: 15% auto; /* 15% from the top and centered */
					padding: 20px;
					border: 1px solid #000;
					text-align: center;
					border-radius: 5px;
				}

				/* Buttons */
				.button-container {
					margin-top: 20px;
				}

				.delete-btn {
					background-color: #B13222;
					color: #f6f7f7;
					padding: 4px 15px;
					text-align: center;
					text-decoration: none;
					font-size: 13px;
					font-weight: 400;
					border-radius: 3px;
					position: relative;
					overflow: hidden;
					border: 1px solid #f6f7f7;
					margin:0px 5px;
				}

				.cancel-btn {
					background-color: #2271B1; 
					color: #f6f7f7;
					padding: 4px 15px;
					text-align: center;
					text-decoration: none;
					font-size: 13px;
					font-weight: 400;
					border-radius: 3px;
					position: relative;
					overflow: hidden;
					border: 1px solid #f6f7f7;
					margin:0px 5px;
				}

				.delete-btn:hover, .delete:hover {
					background-color: #da190b; 
				}

				.cancel-btn:hover, .view:hover, .edit:hover {
					background-color: #03508f; 
				}
			</style>

			<h1 class="heading"><?php echo esc_html(__('Metaverses','vma-plus-station')) ;?>
				<form method="post" style="display:inline;">
				<input type="hidden" name="create_playcanvas_page" value="1">
				<?php
				wp_nonce_field('create_playcanvas_page_action', 'create_playcanvas_page_nonce');
				?>
				<div class ="align-end">
				<button type="submit" class="create-button-lock" <?php if ($pages) echo 'disabled'; ?>>
					<?php if ($pages) : ?>
						<img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'assets/images/lock-icon.svg'); ?>" alt="Lock Icon" width="15" height="15">
					<?php endif; ?>
					<span style="vertical-align: middle;"><?php echo esc_html(__('Create New Site','vma-plus-station')) ;?></span>
					<?php if ($pages) : ?>
					<span class="pro-badge">Pro</span>
					<?php endif; ?>
				</button>
				</div>
				</form>
			</h1>
			<script>
				// Function to display the delete confirmation modal
				function confirmDelete(pageId) {
					var modal = document.getElementById("deleteModal");
					var confirmBtn = document.getElementById("confirmDeleteBtn");
					var cancelBtn = document.getElementById("cancelDeleteBtn");

					// Open modal
					modal.style.display = "block";

					// Set up action for Confirm button
					confirmBtn.onclick = function() {
						deletePage(pageId); // Call delete function
						modal.style.display = "none"; // Hide modal after action
					}

					// Set up action for Cancel button
					cancelBtn.onclick = function() {
						modal.style.display = "none"; // Hide modal without action
					}

					// Function to delete page
					function deletePage(pageId) {
						// Create form dynamically
						var form = document.createElement("form");
						form.setAttribute("method", "post");
						form.setAttribute("action", ""); 
						
						// Create hidden input field for page ID
						var pageIdField = document.createElement("input");
						pageIdField.setAttribute("type", "hidden");
						pageIdField.setAttribute("name", "delete_page_id");
						pageIdField.setAttribute("value", pageId);
						
						// Append input field to form
						form.appendChild(pageIdField);
						
						// Append form to document body and submit
						document.body.appendChild(form);
						form.submit();
					}
				}
				// Close modal when clicking outside the modal content
				window.onclick = function(event) {
					var modal = document.getElementById("deleteModal");
					if (event.target == modal) {
						modal.style.display = "none";
					}
				}
			</script>


		</div>
			<?php
				$args = array(
					'post_type' => 'page',
					'post_status' => 'publish',
					'title' => ucwords('PlayCanvas'),
					'meta_query' => array(
						array(
							'key' => '_wp_page_template',
							'value' => 'playcanvas/index.php' 
						)
					)
				);
				// Get pages matching the query
				$pages = get_posts($args);
		
				// List the pages
				if ($pages) {
					echo '<ul>';
					foreach ($pages as $page) {
						echo '<li class ="sites-list">';
						$encoded_post_name = $page->post_name;
						$decoded_post_name = urldecode($encoded_post_name);
						echo '<p style=" margin : 0; ">/'. esc_html($decoded_post_name) . '</p>';
						echo '<div style="display: flex; justify-content: flex-end; align-items: center;" >';
						$site_url = get_permalink($page->ID);
						echo '<button class="view button-list" onclick="window.open(\'' . esc_url($site_url) . '\', \'_blank\')"> ' . esc_html(__('View Site', 'vma-plus-station')) . '</button>';
						echo '<form method="post" style="display:inline;">';
						echo '<input type="hidden" name="page_id" value="' . esc_attr($page->ID) . '">';
						echo '<input type="hidden" name="site-path" value="' . esc_html($decoded_post_name) . '">';
						echo '<button type="submit" name="edit_page" class = "edit button-list">' . esc_html(__('Edit', 'vma-plus-station')) . '</button>';
						echo '</form>';
						echo '<button class = "delete button-list" onclick="confirmDelete(' . esc_attr($page->ID) . ')">' . esc_html(__('Delete', 'vma-plus-station')) . '</button>';
						echo '</div>';
						echo '</li>';
						echo '<hr>';
						echo '<div id="deleteModal" class="modal">';
						echo '<div class="modal-content" style="width:40%;">';
						// Translate and escape the message parts
						// translators: %s: Site name
						$message_part1 = esc_html(__('The site &quot;%s&quot; will be deleted.', 'vma-plus-station'));
						$message_part2 = esc_html(__('Are you sure?', 'vma-plus-station'));

						// Escape the decoded post name
						$escaped_post_name = esc_html($decoded_post_name);

						// Format the message with the escaped post name
						$message = sprintf($message_part1, $escaped_post_name);

						// Output the translated and formatted message
						echo '<p>' . $message . '<br>' . $message_part2 . '</p>';
						//echo '<p>The site "/'. esc_html($decoded_post_name) . '" will be deleted.</br> Are you Sure?</p>';
						echo '<div class="button-container">';
						echo '<button id="cancelDeleteBtn" class="cancel-btn">' . esc_html(__('No', 'vma-plus-station')) . '</button>';
						echo '<button id="confirmDeleteBtn" class="delete-btn">' . esc_html(__('Yes', 'vma-plus-station')) . '</button>';
						echo '</div>';
						echo '</div>';
						echo '</div>';
					}
					echo '</ul>';
				} else{
					echo '<p style ="margin-left : 1rem; padding : 20vw; font-size: 16px;" >';
					echo esc_html(__('No Site Exists. Click "Create New Site" to create a new Metaverse.','vma-plus-station')) ;
					echo '</p>';
				}
			
				if (isset($_POST['edit_page']) && !empty($_POST['page_id'])) {
					wp_nonce_field('edit_page_nonce_action', 'edit_page_nonce_field');
					$page_id = intval($_POST['page_id']);
					$site_path = $_POST['site-path'];
					$options = get_option('wporg_options', []);

					// Check if $options is an array
					if (!is_array($options)) {
						$options = [];
					}
					
					$options['site-path'] = $site_path;
					update_option('wporg_options', $options);
					// Call the function defined in the separate file
					render_edit_page($this->capability, $page_id);
				}

				// Check if delete_page_id is set in POST request
				if (isset($_POST['delete_page_id'])) {
					$page_id = intval($_POST['delete_page_id']);
					
					// Perform deletion logic (example: using wp_delete_post)
					$deleted = wp_delete_post($page_id, true); // Set second parameter to true to force delete
					
					if ($deleted !== false) {
						// Define the option name associated with the deleted page
						$option_name = 'wporg_options';
						
						// Delete the option from the database
						$deleted_option = delete_option($option_name);
						// JavaScript for success alert
						echo '<script>
							alert(myTranslation.deleteSuccess);
							window.location.replace(window.location.href);
						</script>';
					} else {
						// JavaScript for failure alert
						echo '<script>
							alert(myTranslation.deleteFail);
							window.location.replace(window.location.href);
						</script>';
					}
					exit;
				}
    }
	
	function handle_metaverse_form_submission() {
		$page_id="";
		//if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['metaverse_settings_submit'])) {
		if (isset($_POST['metaverse_settings_submit'])) {
			// Verify nonce
			if (!isset($_POST['metaverse_nonce']) || !wp_verify_nonce($_POST['metaverse_nonce'], 'metaverse-settings')) {
				error_log("Nonce verification failed.");
				add_settings_error('wporg_messages', 'nonce_verification_failed', __('Nonce verification failed.', 'vma-plus-station'));
				return;
			}

			// Construct the dynamic option name
			$options = isset($_POST['wporg_options']) ? $_POST['wporg_options'] : array();

			// Directly save options to preserve non-ASCII characters
			update_option('wporg_options', $options);
	
			// Check if PlayCanvas page exists
			$page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
			$playcanvas_page = get_post($page_id); // Assuming $page_id is valid
			if ($playcanvas_page && $playcanvas_page->post_type === 'page') {
				// Update existing PlayCanvas page
				$pc_update = array(
					'ID'             => $playcanvas_page->ID,
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
					'post_author'    => 1,
					'post_title'     => 'PlayCanvas',
					'post_name'      => $options['site-path'],
					'post_type'      => 'page',
				);
	
				$updated = wp_update_post($pc_update);
	
				if (is_wp_error($updated)) {
					error_log("Failed to update PlayCanvas page: " . $updated->get_error_message());
					add_settings_error('wporg_messages', 'update_failed', __('Failed to update PlayCanvas page.', 'vma-plus-station'));
				} else {
					add_settings_error('wporg_messages', 'update_success', __('Settings updated successfully.', 'vma-plus-station'), 'updated');
				}
			}
		}
		
	}

	public function upgrade_render() : void {
        // This function can be left empty 
    }

	public function add_custom_css() {
        ?>
        <style>
        /* Style the submenu item as a button */
        #toplevel_page_vma-plus-plugin-setting .wp-submenu a[href="admin.php?page=upgrade-plugin"] {
            background-color: #274E13;
			color: #ffffff; 
        }

        /* Hover effect */
        #toplevel_page_vma-plus-plugin-setting .wp-submenu a[href="admin.php?page=upgrade-plugin"]:hover {
            background-color: #04550d; 
			color: #ffffff; 
        }
		#toplevel_page_vma-plus-plugin-setting .wp-submenu a[href="admin.php?page=upgrade-plugin"]:visited {
            color: #ffffff;
        }
        </style>
        <?php
    }
	/* public function add_custom_js() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Open link in a new tab
            $('a[href="admin.php?page=upgrade-plugin"]').on('click', function(e) {
                e.preventDefault();

                window.open('https://www.vma-plus.com', '_blank', 'noopener');
            });
		});
        </script>
        <?php
    } */
   
	public function add_custom_js() {
		?>
		<script src="https://checkout.freemius.com/checkout.min.js"></script>
		<style>
			.modal-open {
				overflow: auto !important;
			}
    	</style>
		<script>
			jQuery(document).ready(function($) {

				function showModal(title, message, onConfirm) {
					// Check if modal already exists and remove it
					$('#dynamicModal').remove();

					// Create the modal HTML structure
					var modalHtml = `
					<div class="modal fade" id="dynamicModal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title" id="modalLabel">${title}</h5>
						</div>
						<div class="modal-body">
							${message}
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-dismiss="modal" id="cancelBtn">${myTranslation.cancelButton}</button>
							<button type="button" class="btn btn-primary" id="confirmBtn">${myTranslation.proceedButton}</button>
						</div>
						</div>
					</div>
					</div>
					`;

					// Append the modal HTML to the body
					$('body').append(modalHtml);

					// Show the modal
					$('#dynamicModal').modal('show');

					// Handle the "Cancel" button click
					$('#cancelBtn').off('click').on('click', function() {
						$('#dynamicModal').modal('hide'); // Hide the modal
						/* $('#dynamicModal').on('hidden.bs.modal', function () {
							$(this).remove(); // Remove the modal from the DOM
						}); */
					});

					// Handle the "Proceed" button click
					$('#confirmBtn').off('click').on('click', function() {
						$('#dynamicModal').modal('hide'); // Hide the modal
						/* $('#dynamicModal').on('hidden.bs.modal', function () {
							$(this).remove(); // Remove the modal from the DOM
						}); */
						if (typeof onConfirm === 'function') {
							onConfirm(); // Execute the callback function
						}
					});

					// Handle modal close
					$('#dynamicModal').on('hidden.bs.modal', function () {
						$(this).remove(); // Remove the modal from the DOM

						$('body').removeClass('modal-open');
        				$('.modal-backdrop').remove();
					});
				}
				function resetBodyOverflow() {
					$('body').css('overflow', 'auto');
					// Ensure that no modal-backdrop is left
					$('.modal-backdrop').remove();
				}

				var handler = FS.Checkout.configure({
					plugin_id:  '16319',
					plan_id:    '27226',
					public_key: 'pk_5c22396adb9cbd14123b35b3c0c7a',
					image:      'https://ps.w.org/vma-plus-station/assets/icon-256x256.png'
				});
	
				// Trigger Freemius checkout when the link is clicked
				$('a[href="admin.php?page=upgrade-plugin"]').on('click', function(e) {
					e.preventDefault();
					showModal(
						myTranslation.confirmationTitle,
            			myTranslation.confirmationMessage,
						function(e) {
							handler.open({
								name     : 'Vma Plus Station',
								licenses : 1,
								purchaseCompleted: function (response) {
									// Logic after purchase confirmation
									// Example: alert(response.user.email);
								},
								success  : function (response) {
									// Logic after successful purchase
									// Example: alert(response.user.email);
									resetBodyOverflow();
								},
								canceled : function (response) {
									// Logic if the purchase is canceled
									resetBodyOverflow(); // Ensure overflow is reset after cancellation
								}
							});
						}
					);
				});
			});
		</script>
		<?php
	}
	public function class_add_js_() {
        ?>
        <script>
        jQuery(document).ready(function($) {
			$('#wpbody-content').wrap('<div class="vma-plus-container"></div>');
		});
        </script>
        <?php
    }

	function render_metaverse_field(array $args) : void {
		$field = $args['field'];
		$options = get_option('wporg_options');

		?>
			<h2><?php echo esc_html( $field['title'] ); ?></h2>
		<?php
	}


	/**
	 * Render a settings field.
	 *
	 * @param array $args Args to configure the field.
	 */
	function render_field( array $args ) : void {

		$field = $args['field'];

		// Get the value of the setting we've registered with register_setting()
		$options = get_option( 'wporg_options' );
		$field_id = $field['id']; // Example: 'poster_image_1_preview'

		$base_field_id = str_replace('_preview', '', $field_id);

		// Step 2: Remove any underscores before digits
		$base_field_id = preg_replace('/_(?=\d)/', '', $base_field_id);

		// Get the URL from options using the modified key
		$value = isset($options[$base_field_id]) ? esc_attr($options[$base_field_id]) : '';

		switch ( $field['type'] ) {

			case "text": {
				?>
				<p>
					<span>
						<?php echo esc_html(site_url()) ."/"; ?>
					</span>
					<input
						type="text"
						id="<?php echo esc_attr( $field['id'] ); ?>"
						name="wporg_options[<?php echo esc_attr( $field['id'] ); ?>]"
						size=36
						value="<?php echo isset( $options[ $field['id'] ] ) ? esc_attr( $options[ $field['id'] ] ) : ''; ?>"
					>
				</p>
				<p class="description">
					<?php esc_html($field['description']); ?>
				</p>
				<?php
				break;
			}

			case "checkbox": {
				?>
				<input
					type="checkbox"
					id="<?php echo esc_attr( $field['id'] ); ?>"
					name="wporg_options[<?php echo esc_attr( $field['id'] ); ?>]"
					value="1"
					<?php echo isset( $options[ $field['id'] ] ) ? ( checked( $options[ $field['id'] ], 1, false ) ) : ( '' ); ?>
				>
				<p class="description">
					<?php esc_html( $field['description'] ); ?>
				</p>
				<?php
				break;
			}

			case "textarea": {
				?>
				<textarea
					id="<?php echo esc_attr( $field['id'] ); ?>"
					name="wporg_options[<?php echo esc_attr( $field['id'] ); ?>]"
				><?php echo isset( $options[ $field['id'] ] ) ? esc_attr( $options[ $field['id'] ] ) : ''; ?></textarea>
				<p class="description">
					<?php esc_html( $field['description'] ); ?>
				</p>
				<?php
				break;
			}

			case "select": {
				?>
				<select
					id="<?php echo esc_attr( $field['id'] ); ?>"
					name="wporg_options[<?php echo esc_attr( $field['id'] ); ?>]"
				>
					<?php foreach( $field['options'] as $key => $option ) { ?>
						<option value="<?php echo esc_attr( $key ); ?>" 
							<?php echo isset( $options[ $field['id'] ] ) ? ( selected( $options[ $field['id'] ], $key, false ) ) : ( '' ); ?>
						>
							<?php echo esc_html( $option ); ?>
						</option>
					<?php } ?>
				</select>
				<p class="description">
					<?php esc_html( $field['description'] ); ?>
				</p>
				<?php
				break;
			}

			case "password": {
				?>
				<input
					type="password"
					id="<?php echo esc_attr( $field['id'] ); ?>"
					name="wporg_options[<?php echo esc_attr( $field['id'] ); ?>]"
					value="<?php echo isset( $options[ $field['id'] ] ) ? esc_attr( $options[ $field['id'] ] ) : ''; ?>"
				>
				<p class="description">
					<?php esc_html( $field['description'] ); ?>
				</p>
				<?php
				break;
			}

			case "wysiwyg": {
				wp_editor(
					isset( $options[ $field['id'] ] ) ? $options[ $field['id'] ] : '',
					$field['id'],
					array(
						'textarea_name' => 'wporg_options[' . $field['id'] . ']',
						'textarea_rows' => 5,
					)
				);
				break;
			}

			case "email": {
				?>
				<input
					type="email"
					id="<?php echo esc_attr( $field['id'] ); ?>"
					name="wporg_options[<?php echo esc_attr( $field['id'] ); ?>]"
					value="<?php echo isset( $options[ $field['id'] ] ) ? esc_attr( $options[ $field['id'] ] ) : ''; ?>"
				>
				<p class="description">
					<?php esc_html( $field['description'] ); ?>
				</p>
				<?php
				break;
			}

			case "poster": {
				?>
				<div class="image-container">
					<div class="upload_image" >
						<img 
							id="<?php echo esc_attr($field['id'] . '_preview'); ?>" 
							src="<?php echo esc_url($value); ?>" 
							style="visibility: <?php echo !empty($value) ? 'visible' : 'hidden'; ?>;" 
						/>
					</div>
				</div>
				<?php
				break;
			}

			case "url": {
				?>
				<div class="input-group url-input">
				<div class="input-group">
				<span class="input-group-text url-input"><?php echo esc_html(__('Destination URL', 'vma-plus-station')); ?></span>
				<input
					type="text"
					class="form-control"
					id="<?php echo esc_attr( $field['id'] ); ?>"
					size="36"
					name="wporg_options[<?php echo esc_attr($field['id']); ?>]"
					value="<?php echo isset( $options[ $field['id'] ] ) ? esc_attr( $options[ $field['id'] ] ) : ''; ?>"
				/>
					</div>
				</div>
				<?php
				break;
			}

			case "upload": {
				?>
				<input id="<?php echo esc_attr( $field['id'] ); ?>"
					type="hidden"
					size="36"
					name="wporg_options[<?php echo esc_attr($field['id']); ?>]"
					placeholder="推奨サイズ : 768 × 1024 pixels"
					value="<?php echo isset( $options[ $field['id'] ] ) ? esc_attr( $options[ $field['id'] ] ) : ''; ?>" />
					<div class="input-group">
								<span class="input-group-text" style="padding: 0px 24px;cursor: pointer;" id="<?php echo esc_attr($field['id'] . '_select'); ?>" ><?php echo esc_html(__('Select Image', 'vma-plus-station')); ?></span>
								<label type="file" class="form-control contents-file file-input" name="upload-image" accept=".png, .jpeg, .jpg" id="<?php echo esc_attr($field['id'] . '_button'); ?>" ><?php echo esc_html(__('No file selected', 'vma-plus-station')); ?></label>
								<input class="btn btn-outline-danger remove-contents" id="<?php echo esc_attr($field['id'] . '_delete'); ?>" type="button" disabled value="<?php echo esc_attr(__('Delete', 'vma-plus-station')); ?>" />
					</div>
				<?php
				break;
			}

			case "date": {
				?>
				<input
					type="date"
					id="<?php echo esc_attr( $field['id'] ); ?>"
					name="wporg_options[<?php echo esc_attr( $field['id'] ); ?>]"
					value="<?php echo isset( $options[ $field['id'] ] ) ? esc_attr( $options[ $field['id'] ] ) : ''; ?>"
				>
				<p class="description">
					<?php esc_html( $field['description'] ); ?>
				</p>
				<?php
				break;
			}

		}
	}
						  
						
						
	/**
	 * Render a section on a page, with an ID and a text label.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     An array of parameters for the section.
	 *
	 *     @type string $id The ID of the section.
	 * }
	 */
	function render_section( array $args ) : void {
		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>"></p>
		<?php
	}

	function render_metaverse_section( array $args ) : void {
		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>"></p>
		<?php
	}

	public function view_project_template( $template ) {
		// Return the search template if we're searching (instead of the template for the first result)
		if ( is_search() ) {
			return $template;
		}

		// Get global post
		global $post;

		// Return template if post is empty
		if ( ! $post ) {
			return $template;
		}

		// Return default template if we don't have a custom one defined
		if ( ! isset( $this->templates[get_post_meta(
			$post->ID, '_wp_page_template', true
		)] ) ) {
			return $template;
		}

		// Allows filtering of file path
		$filepath = apply_filters( 'vmaplus_plugin_dir_path', plugin_dir_path( __FILE__ ) );

		$file =  $filepath . get_post_meta(
			$post->ID, '_wp_page_template', true
		);

		// Just to be safe, we check if the file exist first
		if ( file_exists( $file ) ) {
			return $file;
		} else {
			echo esc_url( $file );
		}

		// Return template
		return $template;

	}
}


new Vmaplus_Metaverse_Plugin_Setting();
}