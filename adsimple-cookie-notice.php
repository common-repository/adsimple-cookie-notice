<?php
/*
Author: AdSimple
Author URI: https://www.adsimple.at/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Plugin Name: AdSimple Cookie Notice
Description: AdSimple Cookie Notice allows you to elegantly inform users that your site uses cookies and to comply with the EU cookie law regulations.
Text Domain: adsimple-cookie-notice
Version: 1.0.12
*/

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

// set plugin instance
$adsimple_cookie_notice = new Adsimple_Cookie_Notice();

/**
 * Adsimple Cookie Notice class.
 */
class Adsimple_Cookie_Notice {

	const ACN_DEV = false;
	const ACN_DEV_USER = '';
	const ACN_DEV_PASS = '';

	/**
	 * @var $defaults
	 */
	private $defaults   		= array();
	private $positions 			= array();
	private $styles 			= array();
	private $choices 			= array();
	private $links 				= array();
	private $link_target 		= array();
	private $colors 			= array();
	private $buttons_styles		= array();
	private $options 			= array();
	private $effects 			= array();
	private $times 				= array();
	private $script_placements 	= array();
	private $currently_enabled 	= array();
	private $on_load 			= array();
	private $on_hide 			= array();
	private $fonts 				= array();
	private $font_sizes 		= array();

	/**
	 * @var $cookie, holds cookie name
	 */
	private static $cookie = array(
		'name'	 => 'adsimple_cookie_notice_accepted',
		'value'	 => 'TRUE'
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );

		$this->setDefaults();

		// settings
		$this->options = array(
			'general' => array_merge( $this->defaults['general'], get_option( 'adsimple_cookie_notice_options', $this->defaults['general'] ) )
		);


		// actions
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu_options' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'after_setup_theme', array( $this, 'load_defaults' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
                add_action( 'wp_head', array( $this, 'add_adsimple_script' ), -1 );

		if($this->options['general']['currently_enabled'] === 'on') {
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
			add_action( 'wp_print_footer_scripts', array( $this, 'wp_print_footer_scripts' ) );

			if($this->options['general']['position'] == 'before_content') {
				add_action( 'wp_head', array( $this, 'add_adsimple_cookie_notice' ), 1000 );
			} else {
				add_action( 'wp_footer', array( $this, 'add_adsimple_cookie_notice' ), 1000 );
			}
		}

		// filters
		add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );

		add_filter( 'rocket_htaccess_mod_rewrite', '__return_false' );
		add_filter( 'rocket_cache_dynamic_cookies', array( $this, 'rocket_cache_dynamic_cookies' ), 10, 1 );
	}

	private function setDefaults()
	{
		$this->defaults = array(
			'general' => array(
				'position'						=> 'bottom',
				'message_text'					=> __('Mit der weiteren Nutzung unserer Webseite erklären Sie sich damit einverstanden, dass wir Cookies verwenden um Ihnen die Nutzerfreundlichkeit dieser Webseite zu verbessern. Weitere Informationen zum Datenschutz finden Sie in unserer Datenschutzerklärung.', ''),
				'accept_text'					=> __('In Ordnung', ''),
				'refuse_text'					=> __('Ich möchte Cookies von Drittseiten nicht zulassen.', ''),
				'refuse_opt'					=> 'no',
				'refuse_code'					=> '',
				'custom_css'					=> '',
				'redirection'					=> false,
				'see_more'						=> 'yes',
				'link_target'					=> '_blank',
				'time'							=> 'month',
				'hide_effect'					=> 'fade',
				'on_scroll'						=> false,
				'on_scroll_offset'				=> 100,
				'colors' => array(
					'text'							=> '#ffffff',
					'bar'							=> '#57ca85',
					'border'						=> '#57ca85',
					'button'						=> '#ffffff',
					'button_text'					=> '#000000',
					'button_border'					=> '#ffffff',
					'readmore_border'				=> '#ffffff',
				),
				'buttons_style'					=> 'square',
				'on_load' 						=> false,
				'on_hide' 						=> true,
				'autohide_notice'				=> false,
				'autohide_delay'				=> 10000,
				'font'							=> 'inherit',
				'font_size'						=> 'default',
				'see_more_opt' => array(
					'text'						=> __('Datenschutzerklärung', ''),
					'link_type'					=> 'custom',
					'id'						=> 'empty',
					'link'						=> ''
				),
				'see_more_opt_tab' => array(
					'text'						=> __('Datenschutzinfo', ''),
					'link_type'					=> 'custom',
					'id'						=> 'empty',
					'link'						=> ''
				),
				'script_placement'				=> 'footer',
				'currently_enabled'				=> 'on',
				'translate'						=> true,
				'deactivation_delete'                           => 'no',
				'copyright'                                     => false
			),
			'version'							=> time(), //@TODO!!!!!
		);
	}

	/**
	 * Load plugin defaults
	 */
	public function load_defaults() {
		$this->positions = array(
			'top'	 			=> __( 'Top', 'adsimple-cookie-notice' ),
			'bottom' 			=> __( 'Bottom', 'adsimple-cookie-notice' ),
			'before_content' 	=> __( 'Before Content', 'adsimple-cookie-notice' ),
		);

		$this->links = array(
			'custom' 			=> __( 'Custom link', 'adsimple-cookie-notice' ),
			'page'	 			=> __( 'Page link', 'adsimple-cookie-notice' )
		);

		$this->link_target = array(
			'_blank',
			'_self'
		);

		$this->colors = array(
			'text'	 			=> __( 'Text color', 'adsimple-cookie-notice' ),
			'bar'	 			=> __( 'Bar color', 'adsimple-cookie-notice' ),
			'border'	 		=> __( 'Border color', 'adsimple-cookie-notice' ),
			'button'	 		=> __( 'Button color', 'adsimple-cookie-notice' ),
			'button_text' 		=> __( 'Button text color', 'adsimple-cookie-notice' ),
			'button_border' 	=> __( 'Button border color', 'adsimple-cookie-notice' ),
			'readmore_border' 	=> __( 'Readmore border color', 'adsimple-cookie-notice' ),
		);

		$this->buttons_styles = array(
			'square' => __( 'Square', 'adsimple-cookie-notice' ),
			'pill'	 => __( 'Pill', 'adsimple-cookie-notice' ),
			'round'	 => __( 'Round', 'adsimple-cookie-notice' ),
		);

		$this->times = array(
			'day'		 		=> array( __( '1 day', 'adsimple-cookie-notice' ), 86400 ),
			'week'		 		=> array( __( '1 week', 'adsimple-cookie-notice' ), 604800 ),
			'month'		 		=> array( __( '1 month', 'adsimple-cookie-notice' ), 2592000 ),
			'3months'	 		=> array( __( '3 months', 'adsimple-cookie-notice' ), 7862400 ),
			'6months'	 		=> array( __( '6 months', 'adsimple-cookie-notice' ), 15811200 ),
			'year'		 		=> array( __( '1 year', 'adsimple-cookie-notice' ), 31536000 ),
			'infinity'	 		=> array( __( 'infinity', 'adsimple-cookie-notice' ), PHP_INT_MAX )
		);

		$this->effects = array(
			'none'	 			=> __( 'None', 'adsimple-cookie-notice' ),
			'fade'	 			=> __( 'Fade', 'adsimple-cookie-notice' ),
			'slide'	 			=> __( 'Slide', 'adsimple-cookie-notice' ),
			'css_slide'	 		=> __( 'CSS Slide', 'adsimple-cookie-notice' ),
		);

		$this->script_placements = array(
			'header' 			=> __( 'Header', 'adsimple-cookie-notice' ),
			'footer' 			=> __( 'Footer', 'adsimple-cookie-notice' ),
		);

		$this->currently_enabled = array(
			'on' 				=> __( 'On', 'adsimple-cookie-notice' ),
			'off' 				=> __( 'Off', 'adsimple-cookie-notice' ),
		);

		$this->on_load = array(
			true 				=> __( 'Animate', 'adsimple-cookie-notice' ),
			false 				=> __( 'Sticky', 'adsimple-cookie-notice' ),
		);

		$this->on_hide = array(
			true 			=> __( 'Animate', 'adsimple-cookie-notice' ),
			false 			=> __( 'Disappear', 'adsimple-cookie-notice' ),
		);

		$this->fonts = array(
			'inherit'                                        => __( 'Default theme font', 'adsimple-cookie-notice' ),
			'Helvetica, Arial, sans-serif'                   => __( 'Sans Serif', 'adsimple-cookie-notice' ),
			'Georgia, Times New Roman, Times, serif'         => __( 'Serif', 'adsimple-cookie-notice' ),
			'Arial, Helvetica, sans-serif'                   => __( 'Arial', 'adsimple-cookie-notice' ),
			'Arial Black,Gadget,sans-serif'                  => __( 'Arial Black', 'adsimple-cookie-notice' ),
			'Georgia, serif'                                 => __( 'Georgia', 'adsimple-cookie-notice' ),
			'Helvetica, sans-serif'                          => __( 'Helvetica', 'adsimple-cookie-notice' ),
			'Lucida Sans Unicode, Lucida Grande, sans-serif' => __( 'Lucida', 'adsimple-cookie-notice' ),
			'Tahoma, Geneva, sans-serif'                     => __( 'Tahoma', 'adsimple-cookie-notice' ),
			'Times New Roman, Times, serif'                  => __( 'Times New Roman', 'adsimple-cookie-notice' ),
			'Trebuchet MS, sans-serif'                       => __( 'Trebuchet', 'adsimple-cookie-notice' ),
			'Verdana, Geneva'                                => __( 'Verdana', 'adsimple-cookie-notice' ),
		);

		$this->font_sizes = array(
			'default' => __('Default', 'adsimple-cookie-notice'),
			'7' => __('7', 'adsimple-cookie-notice'),
			'8' => __('8', 'adsimple-cookie-notice'),
			'9' => __('9', 'adsimple-cookie-notice'),
			'10' => __('10', 'adsimple-cookie-notice'),
			'11' => __('11', 'adsimple-cookie-notice'),
			'13' => __('13', 'adsimple-cookie-notice'),
			'14' => __('14', 'adsimple-cookie-notice'),
			'15' => __('15', 'adsimple-cookie-notice'),
			'16' => __('16', 'adsimple-cookie-notice'),
			'17' => __('17', 'adsimple-cookie-notice'),
			'18' => __('18', 'adsimple-cookie-notice'),
			'19' => __('19', 'adsimple-cookie-notice'),
			'20' => __('20', 'adsimple-cookie-notice'),
			'21' => __('21', 'adsimple-cookie-notice'),
			'22' => __('22', 'adsimple-cookie-notice'),
		);

		if ( $this->options['general']['translate'] === true ) {
			$this->options['general']['translate'] = false;

			$this->options['general']['message_text'] = __( 'Mit der weiteren Nutzung unserer Webseite erklären Sie sich damit einverstanden, dass wir Cookies verwenden um Ihnen die Nutzerfreundlichkeit dieser Webseite zu verbessern. Weitere Informationen zum Datenschutz finden Sie in unserer Datenschutzerklärung.', 'adsimple-cookie-notice' );
			$this->options['general']['accept_text'] = __( 'In Ordnung', 'adsimple-cookie-notice' );
			$this->options['general']['refuse_text'] = __( 'Ich möchte Cookies von Drittseiten nicht zulassen.', 'adsimple-cookie-notice' );
			$this->options['general']['see_more_opt']['text'] = __( 'Datenschutzerklärung', 'adsimple-cookie-notice' );
			$this->options['general']['see_more_opt_tab']['text'] = __( 'Mehr über Cookies erfahren', 'adsimple-cookie-notice' );

			update_option( 'adsimple_cookie_notice_options', $this->options['general'] );
		}

		// WPML >= 3.2
		if ( defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '3.2', '>=' ) ) {
			$this->register_wpml_strings();
		// WPML and Polylang compatibility
		} elseif ( function_exists( 'icl_register_string' ) ) {
			icl_register_string( 'Adsimple Cookie Notice', 'Message in the notice', $this->options['general']['message_text'] );
			icl_register_string( 'Adsimple Cookie Notice', 'Button text', $this->options['general']['accept_text'] );
			icl_register_string( 'Adsimple Cookie Notice', 'Refuse button text', $this->options['general']['refuse_text'] );
			icl_register_string( 'Adsimple Cookie Notice', 'Read more text', $this->options['general']['see_more_opt']['text'] );
			icl_register_string( 'Adsimple Cookie Notice', 'Custom link', $this->options['general']['see_more_opt']['link'] );
		}
	}

	/**
	 * Register WPML (>= 3.2) strings if needed.
	 *
	 * @return	void
	 */
	private function register_wpml_strings() {
		global $wpdb;

		// prepare strings
		$strings = array(
			'Message in the notice'	=> $this->options['general']['message_text'],
			'Button text'			=> $this->options['general']['accept_text'],
			'Refuse button text'	=> $this->options['general']['refuse_text'],
			'Read more text'		=> $this->options['general']['see_more_opt']['text'],
			'Custom link'			=> $this->options['general']['see_more_opt']['link']
		);

		// get query results
		$results = $wpdb->get_col( $wpdb->prepare( "SELECT name FROM " . $wpdb->prefix . "icl_strings WHERE context = %s", 'Adsimple Cookie Notice' ) );

		// check results
		foreach( $strings as $string => $value ) {
			// string does not exist?
			if ( ! in_array( $string, $results, true ) ) {
				// register string
				do_action( 'wpml_register_single_string', 'Adsimple Cookie Notice', $string, $value );
			}
		}
	}

	/**
	 * Load textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'adsimple-cookie-notice', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Add submenu.
	 */
	public function admin_menu_options() {
		add_options_page(
			__( 'AdSimple Cookie Notice', 'adsimple-cookie-notice' ), __( 'AdSimple Cookie Notice', 'adsimple-cookie-notice' ), apply_filters( 'acn_manage_adsimple_cookie_notice_cap', 'manage_options' ), 'adsimple-cookie-notice', array( $this, 'options_page' )
		);
	}

	/**
	 * Options page output.
	 *
	 * @return mixed
	 */
	public function options_page() {
		echo '
		<div class="wrap">
			<h2>' . __( 'AdSimple Cookie Notice', 'adsimple-cookie-notice' ) . '</h2>
			<div class="adsimple-cookie-notice-settings">
				<form action="options.php" method="post">';

		settings_fields( 'adsimple_cookie_notice_options' );
		do_settings_sections( 'adsimple_cookie_notice_options' );

		echo '
				<p class="submit">';
		submit_button( '', 'primary', 'save_adsimple_cookie_notice_options', false );
		echo ' ';
		submit_button( __( 'Reset to defaults', 'adsimple-cookie-notice' ), 'secondary', 'reset_adsimple_cookie_notice_options', false );
		echo '
				</p>
				</form>
			</div>
			<div class="clear"></div>
		</div>';
	}

	/**
	 * Regiseter plugin settings.
	 */
	public function register_settings() {
		register_setting( 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_options', array( $this, 'validate_options' ) );

		// configuration
		add_settings_section( 'adsimple_cookie_notice_configuration', __( 'Configuration', 'adsimple-cookie-notice' ), array( $this, 'acn_section_configuration' ), 'adsimple_cookie_notice_options' );

                add_settings_field( 'acn_id_key', __( 'Do you have adsimple.at account?', 'adsimple-cookie-notice' ), array( $this, 'acn_id_key_field' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_configuration' );

		add_settings_field( 'acn_currently_enabled', __( 'Cookie Notice is currently', 'adsimple-cookie-notice' ), array( $this, 'acn_currently_enabled' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_configuration' );
		//add_settings_field( 'acn_on_load', __( 'On load', 'adsimple-cookie-notice' ), array( $this, 'acn_on_load' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_configuration' );
		//add_settings_field( 'acn_on_hide', __( 'On hide', 'adsimple-cookie-notice' ), array( $this, 'acn_on_hide' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_configuration' );
		add_settings_field( 'acn_autohide_notice', __( 'Auto-hide cookie notice after delay?', 'adsimple-cookie-notice' ), array( $this, 'acn_autohide_notice' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_configuration' );
		add_settings_field( 'acn_autohide_delay', __( 'Auto-hide milliseconds until hidden', 'adsimple-cookie-notice' ), array( $this, 'acn_autohide_delay' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_configuration' );
		add_settings_field( 'acn_message_text', __( 'Message', 'adsimple-cookie-notice' ), array( $this, 'acn_message_text' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_configuration' );
		add_settings_field( 'acn_accept_text', __( 'Button text', 'adsimple-cookie-notice' ), array( $this, 'acn_accept_text' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_configuration' );
		add_settings_field( 'acn_see_more', __( 'More info link', 'adsimple-cookie-notice' ), array( $this, 'acn_see_more' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_configuration' );
		add_settings_field( 'acn_link_target', __( 'Link target', 'adsimple-cookie-notice' ), array( $this, 'acn_link_target' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_configuration' );
		add_settings_field( 'acn_refuse_opt', __( 'Refuse button', 'adsimple-cookie-notice' ), array( $this, 'acn_refuse_opt' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_configuration' );
		add_settings_field( 'acn_refuse_code', __( 'Script blocking', 'adsimple-cookie-notice' ), array( $this, 'acn_refuse_code' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_configuration' );
		add_settings_field( 'acn_redirection', __( 'Reloading', 'adsimple-cookie-notice' ), array( $this, 'acn_redirection' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_configuration' );
		add_settings_field( 'acn_on_scroll', __( 'On scroll', 'adsimple-cookie-notice' ), array( $this, 'acn_on_scroll' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_configuration' );
		add_settings_field( 'acn_time', __( 'Cookie expiry', 'adsimple-cookie-notice' ), array( $this, 'acn_time' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_configuration' );
		add_settings_field( 'acn_script_placement', __( 'Script placement', 'adsimple-cookie-notice' ), array( $this, 'acn_script_placement' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_configuration' );
		add_settings_field( 'acn_deactivation_delete', __( 'Deactivation', 'adsimple-cookie-notice' ), array( $this, 'acn_deactivation_delete' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_configuration' );

		add_settings_field( 'acn_see_more_opt_tab', __( 'Tab label', 'adsimple-cookie-notice' ), array( $this, 'acn_see_more_tab' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_configuration' );

		// design
		add_settings_section( 'adsimple_cookie_notice_design', __( 'Design', 'adsimple-cookie-notice' ), array( $this, 'acn_section_design' ), 'adsimple_cookie_notice_options' );
		add_settings_field( 'acn_position', __( 'Position', 'adsimple-cookie-notice' ), array( $this, 'acn_position' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_design' );
		add_settings_field( 'acn_hide_effect', __( 'Animation', 'adsimple-cookie-notice' ), array( $this, 'acn_hide_effect' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_design' );
		add_settings_field( 'acn_colors', __( 'Colors', 'adsimple-cookie-notice' ), array( $this, 'acn_colors' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_design' );
		add_settings_field( 'acn_buttons_style', __( 'Buttons Style', 'adsimple-cookie-notice' ), array( $this, 'acn_buttons_style' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_design' );
		add_settings_field( 'acn_font', __( 'Font', 'adsimple-cookie-notice' ), array( $this, 'acn_font' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_design' );
		add_settings_field( 'acn_font_size', __( 'Font Size', 'adsimple-cookie-notice' ), array( $this, 'acn_font_size' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_design' );
		add_settings_field( 'acn_custom_css', __( 'Custom CSS', 'adsimple-cookie-notice' ), array( $this, 'acn_custom_css' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_design' );

		add_settings_field( 'acn_copyright', __( 'Show copyright', 'adsimple-cookie-notice' ), array( $this, 'acn_copyright' ), 'adsimple_cookie_notice_options', 'adsimple_cookie_notice_design' );
	}

	/**
	 * Section callback: fix for WP < 3.3
	 */
	public function acn_section_configuration() {}
	public function acn_section_design() {}


        public function acn_id_key_field() {
            $options = get_option( 'adsimple_cookie_notice_options');
            $id_key = null;
            if(!empty($options['id_key'])) {
                $id_key = $options['id_key'];
            }
            ob_start();
            ?>
            <label><input id="acn_id_key_yes" class="js-acn_id-radio" type="radio" name="adsimple_cookie_notice_options[is_id_key]" value="1"<?= ($id_key)?'  checked':''?>/>Yes</label>
            <label><input id="acn_id_key_yes" class="js-acn_id-radio" type="radio" name="adsimple_cookie_notice_options[is_id_key]" value="0"<?= ($id_key)?'  ':' checked'?>/>No</label>

            <div class="js-acn_id <?= ($id_key)?'acn_id--active':'acn_id'; ?>">
                <label><span>Adsimple ID</span><br/><input type="text" class="regular-text code" name="adsimple_cookie_notice_options[id_key]" value="<?= esc_html($id_key); ?>"/></label>
            </div>
            <?php
            echo ob_get_clean();
        }

	/**
	 * Delete plugin data on deactivation.
	 */
	public function acn_deactivation_delete() {
		echo '
		<label><input id="acn_deactivation_delete" type="checkbox" name="adsimple_cookie_notice_options[deactivation_delete]" value="1" ' . checked( 'yes', $this->options['general']['deactivation_delete'], false ) . '/>' . __( 'Enable if you want all plugin data to be deleted on deactivation.', 'adsimple-cookie-notice' ) . '</label>';
	}

	/**
	 * Cookie message option.
	 */
	public function acn_message_text() {
		echo '
		<div id="acn_message_text">
			<textarea name="adsimple_cookie_notice_options[message_text]" class="large-text" cols="50" rows="5">' . esc_textarea( $this->options['general']['message_text'] ) . '</textarea>
			<p class="description">' . __( 'Enter the cookie notice message.', 'adsimple-cookie-notice' ) . '</p>
		</div>';
	}

	/**
	 * Accept cookie label option.
	 */
	public function acn_accept_text() {
		echo '
		<div id="acn_accept_text">
			<input type="text" class="regular-text" name="adsimple_cookie_notice_options[accept_text]" value="' . esc_attr( $this->options['general']['accept_text'] ) . '" />
			<p class="description">' . __( 'The text of the option to accept the usage of the cookies and make the notification disappear.', 'adsimple-cookie-notice' ) . '</p>
		</div>';
	}

	/**
	 * Enable/Disable third party non functional cookies option.
	 */
	public function acn_refuse_opt() {
		echo '
		<fieldset>
			<label><input id="acn_refuse_opt" type="checkbox" name="adsimple_cookie_notice_options[refuse_opt]" value="1" ' . checked( 'yes', $this->options['general']['refuse_opt'], false ) . ' />' . __( 'Give to the user the possibility to refuse third party non functional cookies.', 'adsimple-cookie-notice' ) . '</label>';
		echo '<div id="acn_refuse_opt_container"' . ($this->options['general']['refuse_opt'] === 'no' ? ' style="display: none;"' : '') . '>';
		echo '
				<div id="acn_refuse_text">
					<input type="text" class="regular-text" name="adsimple_cookie_notice_options[refuse_text]" value="' . esc_attr( $this->options['general']['refuse_text'] ) . '" />
					<p class="description">' . __( 'The text of the option to refuse the usage of the cookies.', 'adsimple-cookie-notice' ) . '</p>
				</div>';
		echo '
			</div>
		</fieldset>';
	}

	/**
	 * Non functional cookies code.
	 */
	public function acn_refuse_code() {
		$allowed_html = apply_filters( 'acn_refuse_code_allowed_html', array_merge( wp_kses_allowed_html( 'post' ), array(
			'script' => array(
				'type'		 => array(),
				'src'		 => array(),
				'charset'	 => array(),
				'async'		 => array()
			),
			'noscript' => array()
		) ) );

		echo '
			<div id="acn_refuse_code">
				<textarea name="adsimple_cookie_notice_options[refuse_code]" class="large-text" cols="50" rows="8">' . html_entity_decode( trim( wp_kses( $this->options['general']['refuse_code'], $allowed_html ) ) ) . '</textarea>
				<p class="description">' . __( 'Enter non functional cookies Javascript code here (for e.g. Google Analitycs) to be used after cookies are accepted.', 'adsimple-cookie-notice' ) . '</p>
			</div>';
	}

	/**
	 * Custom css code.
	 */
	public function acn_custom_css() {
		$allowed_html = apply_filters( 'acn_custom_css_allowed_html', array(
			'style' => array(),
		));

		echo '<input id="acn_custom_css_value" type="hidden" name="adsimple_cookie_notice_options[custom_css]">
			<div id="acn_custom_css">' . html_entity_decode( trim( wp_kses( $this->options['general']['custom_css'], $allowed_html ) ) ) . '</div>
			<p class="description">' . __( 'Enter custom CSS code here.', 'adsimple-cookie-notice' ) . '</p>';
	}

	/**
	 * Redirection on cookie accept.
	 */
	public function acn_redirection() {
		echo '
			<label><input id="acn_redirection" type="checkbox" name="adsimple_cookie_notice_options[redirection]" value="1" ' . checked( true, $this->options['general']['redirection'], false ) . ' />' . __( 'Enable to reload the page after cookies are accepted.', 'adsimple-cookie-notice' ) . '</label>';
	}

	/**
	 * Read more link option.
	 */
	public function acn_see_more() {

		$pages = get_pages(
			array(
				'sort_order'	=> 'ASC',
				'sort_column'	=> 'post_title',
				'hierarchical'	=> 0,
				'child_of'		=> 0,
				'parent'		=> -1,
				'offset'		=> 0,
				'post_type'		=> 'page',
				'post_status'	=> 'publish'
			)
		);

		echo '
			<label><input id="acn_see_more" type="checkbox" name="adsimple_cookie_notice_options[see_more]" value="1" ' . checked( 'yes', $this->options['general']['see_more'], false ) . ' />' . __( 'Enable Read more link.', 'adsimple-cookie-notice' ) . '</label>';

		echo '
		<fieldset>
		<div id="acn_see_more_opt"' . ($this->options['general']['see_more'] === 'no' ? ' style="display: none;"' : '') . '>
			<input type="text" class="regular-text" name="adsimple_cookie_notice_options[see_more_opt][text]" value="' . esc_attr( $this->options['general']['see_more_opt']['text'] ) . '" />
			<p class="description">' . __( 'The text of the more info button.', 'adsimple-cookie-notice' ) . '</p>
			<div id="acn_see_more_opt_custom_link">';

		if ( $pages ) {
			foreach ( $this->links as $value => $label ) {
				$value = esc_attr( $value );

				echo '
					<label><input id="acn_see_more_link-' . $value . '" type="radio" name="adsimple_cookie_notice_options[see_more_opt][link_type]" value="' . $value . '" ' . checked( $value, $this->options['general']['see_more_opt']['link_type'], false ) . ' />' . esc_html( $label ) . '</label>';
			}
		}

		echo '
			</div>
			<p class="description">' . __( 'Select where to redirect user for more information about cookies.', 'adsimple-cookie-notice' ) . '</p>
			<div id="acn_see_more_opt_page"' . ($this->options['general']['see_more_opt']['link_type'] === 'custom' ? ' style="display: none;"' : '') . '>
				<select name="adsimple_cookie_notice_options[see_more_opt][id]">
					<option value="empty" ' . selected( 'empty', $this->options['general']['see_more_opt']['id'], false ) . '>' . __( '-- select page --', 'adsimple-cookie-notice' ) . '</option>';

		if ( $pages ) {
			foreach ( $pages as $page ) {
				echo '
						<option value="' . $page->ID . '" ' . selected( $page->ID, $this->options['general']['see_more_opt']['id'], false ) . '>' . esc_html( $page->post_title ) . '</option>';
			}
		}

		echo '
				</select>
				<p class="description">' . __( 'Select from one of your site\'s pages', 'adsimple-cookie-notice' ) . '</p>
			</div>
			<div id="acn_see_more_opt_link"' . ($this->options['general']['see_more_opt']['link_type'] === 'page' ? ' style="display: none;"' : '') . '>
				<input type="text" class="regular-text" name="adsimple_cookie_notice_options[see_more_opt][link]" value="' . esc_attr( $this->options['general']['see_more_opt']['link'] ) . '" />
				<p class="description">' . __( 'Enter the full URL starting with http://', 'adsimple-cookie-notice' ) . '</p>
			</div>
		</div>
		</fieldset>';
	}


        public function acn_see_more_tab() {
            echo '
		<div id="acn_see_more_tab">
			<input type="text" class="regular-text" name="adsimple_cookie_notice_options[see_more_opt_tab][text]" value="' . esc_attr( $this->options['general']['see_more_opt_tab']['text'] ) . '" />
			<p class="description">' . __( 'The text of the tab, which you will in case of accept', 'adsimple-cookie-notice' ) . '</p>
		</div>';
        }

        /**
	 * Link target option.
	 */
	public function acn_link_target() {
		echo '
		<div id="acn_link_target">
			<select name="adsimple_cookie_notice_options[link_target]">';

		foreach ( $this->link_target as $target ) {
			echo '<option value="' . $target . '" ' . selected( $target, $this->options['general']['link_target'] ) . '>' . esc_html( $target ) . '</option>';
		}

		echo '
			</select>
			<p class="description">' . __( 'Select the link target for more info page.', 'adsimple-cookie-notice' ) . '</p>
		</div>';
	}

	/**
	 * Expiration time option.
	 */
	public function acn_time() {
		echo '
		<div id="acn_time">
			<select name="adsimple_cookie_notice_options[time]">';

		foreach ( $this->times as $time => $arr ) {
			$time = esc_attr( $time );

			echo '<option value="' . $time . '" ' . selected( $time, $this->options['general']['time'] ) . '>' . esc_html( $arr[0] ) . '</option>';
		}

		echo '
			</select>
			<p class="description">' . __( 'The ammount of time that cookie should be stored for.', 'adsimple-cookie-notice' ) . '</p>
		</div>';
	}

	/**
	 * Script placement option.
	 */
	public function acn_script_placement() {
		foreach ( $this->script_placements as $value => $label ) {
			echo '
			<label><input id="acn_script_placement-' . $value . '" type="radio" name="adsimple_cookie_notice_options[script_placement]" value="' . esc_attr( $value ) . '" ' . checked( $value, $this->options['general']['script_placement'], false ) . ' />' . esc_html( $label ) . '</label>';
		}

		echo '
			<p class="description">' . __( 'Select where all the plugin scripts should be placed.', 'adsimple-cookie-notice' ) . '</p>';
	}

	/**
	 * Position option.
	 */
	public function acn_position() {
		echo '
		<div id="acn_position">';

		foreach ( $this->positions as $value => $label ) {
			$value = esc_attr( $value );

			echo '
			<label><input id="acn_position-' . $value . '" type="radio" name="adsimple_cookie_notice_options[position]" value="' . $value . '" ' . checked( $value, $this->options['general']['position'], false ) . ' />' . esc_html( $label ) . '</label>';
		}

		echo '
			<p class="description">' . __( 'Select location for your cookie notice.', 'adsimple-cookie-notice' ) . '</p>
		</div>';
	}

	/**
	 * Animation effect option.
	 */
	public function acn_hide_effect() {
		echo '
		<div id="acn_hide_effect">';

		foreach ( $this->effects as $value => $label ) {
			$value = esc_attr( $value );

			echo '
			<label><input id="acn_hide_effect-' . $value . '" type="radio" name="adsimple_cookie_notice_options[hide_effect]" value="' . $value . '" ' . checked( $value, $this->options['general']['hide_effect'], false ) . ' />' . esc_html( $label ) . '</label>';
		}

		echo '
			<p class="description">' . __( 'Cookie notice acceptance animation.', 'adsimple-cookie-notice' ) . '</p>
		</div>';
	}

	/**
	 * On scroll option.
	 */
	public function acn_on_scroll() {
		echo '
		<fieldset>
			<label><input id="acn_on_scroll" type="checkbox" name="adsimple_cookie_notice_options[on_scroll]" value="1" ' . checked( 'yes', $this->options['general']['on_scroll'], false ) . ' />' . __( 'Enable cookie notice acceptance when users scroll.', 'adsimple-cookie-notice' ) . '</label>';
		echo '
			<div id="acn_on_scroll_offset"' . ( $this->options['general']['on_scroll'] === 'no' || $this->options['general']['on_scroll'] == false ? ' style="display: none;"' : '' ) . '>
				<input type="text" class="text" name="adsimple_cookie_notice_options[on_scroll_offset]" value="' . esc_attr( $this->options['general']['on_scroll_offset'] ) . '" /> <span>px</span>
				<p class="description">' . __( 'Number of pixels user has to scroll to accept the usage of the cookies and make the notification disappear.', 'adsimple-cookie-notice' ) . '</p>
			</div>
		</fieldset>';
	}

	/**
	 * Colors option.
	 */
	public function acn_colors()  {
		$register_link = sprintf('<a href="%s" target="_blank">%s</a>', 'https://www.adsimple.at/anmelden/', __('register', 'adsimple-cookie-notice') );
		$notification = sprintf(__('If you want to change the colors of the cookie notice popup, please, %s.', 'adsimple-cookie-notice'), $register_link);
		?>
		<fieldset id="acn-colors">
			<span class="acn-colors-notification"><?= $notification ?></span>
			<?php foreach ( $this->colors as $value => $label ) { ?>
				<div id="acn-colors-<?= esc_attr($value) ?>">
					<span><?= esc_html($label) ?></span>
					<span class="acn-color-value" style="background-color: <?= esc_attr($this->defaults['general']['colors'][$value]) ?>;"></span>
				</div>
			<?php } ?>
		</fieldset>
		<?php
	}

	/**
	 * Buttons Style option.
	 */
	public function acn_buttons_style() {
		echo '
		<div id="acn_buttons_style">';

		foreach ( $this->buttons_styles as $value => $label ) {
			$value = esc_attr( $value );

			echo '
			<label><input id="acn_buttons_style-' . $value . '" type="radio" name="adsimple_cookie_notice_options[buttons_style]" value="' . $value . '" ' . checked( $value, $this->options['general']['buttons_style'], false ) . ' />' . esc_html( $label ) . '</label>';
		}

		echo '
			<p class="description">' . __( 'Select style for your buttons.', 'adsimple-cookie-notice' ) . '</p>
		</div>';
	}

	/**
	 * Currently enabled option.
	 */
	public function acn_currently_enabled () {
		foreach ( $this->currently_enabled as $value => $label ) {
			echo '
			<label><input id="acn_currently_enabled-' . $value . '" type="radio" name="adsimple_cookie_notice_options[currently_enabled]" value="' . esc_attr( $value ) . '" ' . checked( $value, $this->options['general']['currently_enabled'], false ) . ' />' . esc_html( $label ) . '</label>';
		}
	}

	/**
	 * On load option.
	 */
	public function acn_on_load () {
		foreach ( $this->on_load as $value => $label ) {
			echo '
			<label><input id="acn_on_load-' . $value . '" type="radio" name="adsimple_cookie_notice_options[on_load]" value="' . esc_attr( $value ) . '" ' . checked( $value, $this->options['general']['on_load'], false ) . ' />' . esc_html( $label ) . '</label>';
		}
	}

	/**
	 * On hide option.
	 */
	public function acn_on_hide () {
		foreach ( $this->on_hide as $value => $label ) {
			echo '
			<label><input id="acn_on_hide-' . $value . '" type="radio" name="adsimple_cookie_notice_options[on_hide]" value="' . esc_attr( $value ) . '" ' . checked( $value, $this->options['general']['on_hide'], false ) . ' />' . esc_html( $label ) . '</label>';
		}
	}

	/**
	 * Autohide notice option.
	 */
	public function acn_autohide_notice () {
		echo '
			<label><input id="acn_redirection" type="checkbox" name="adsimple_cookie_notice_options[autohide_notice]" value="1" ' . checked( true, $this->options['general']['autohide_notice'], false ) . ' />' . __( 'Yes', 'adsimple-cookie-notice' ) . '</label>';
	}

	/**
	 * Autohide delay option.
	 */
	public function acn_autohide_delay () {
		echo '
		<div id="acn_autohide_delay">
			<input type="text" class="regular-text" name="adsimple_cookie_notice_options[autohide_delay]" value="' . esc_attr( $this->options['general']['autohide_delay'] ) . '" />
			<p class="description">' . __( 'Specify milliseconds (not seconds) e.g. 8000 = 8 seconds', 'adsimple-cookie-notice' ) . '</p>
		</div>';
	}

	/**
	 * Font option.
	 */
	public function acn_font() {
		echo '
		<div id="acn_font">
			<select name="adsimple_cookie_notice_options[font]">';

		foreach ( $this->fonts as $value => $target ) {
			echo '<option value="' . $value . '" ' . selected( $value, $this->options['general']['font'] ) . '>' . esc_html( $target ) . '</option>';
		}
	}

	/**
	 * Font size option.
	 */
	public function acn_font_size() {
		echo '
		<div id="acn_font_size">
			<select name="adsimple_cookie_notice_options[font_size]">';

		foreach ( $this->font_sizes as $value => $target ) {
			echo '<option value="' . $value . '" ' . selected( $value, $this->options['general']['font_size'] ) . '>' . esc_html( $target ) . '</option>';
		}
	}


        public function acn_copyright() {
            echo '
			<label><input id="acn_copyright" type="checkbox" name="adsimple_cookie_notice_options[copyright]" value="1" ' . checked( true, $this->options['general']['copyright'], false ) . ' /></label>';
        }

    /**
	 * Validate options.
	 *
	 * @param array $input
	 * @return array
	 */
	public function validate_options( $input ) {

		if ( ! check_admin_referer( 'adsimple_cookie_notice_options-options') )
			return $input;

		if ( ! current_user_can( apply_filters( 'acn_manage_adsimple_cookie_notice_cap', 'manage_options' ) ) )
			return $input;

		if ( isset( $_POST['save_adsimple_cookie_notice_options'] ) ) {
            $this->get_acn_script($input['id_key']);

			// position
			$input['position'] = sanitize_text_field( isset( $input['position'] ) && in_array( $input['position'], array_keys( $this->positions ) ) ? $input['position'] : $this->defaults['general']['position'] );

			// colors
			$input['colors'] = $this->defaults['general']['colors'];

			// texts
			$input['message_text'] = wp_kses_post( isset( $input['message_text'] ) && $input['message_text'] !== '' ? $input['message_text'] : $this->defaults['general']['message_text'] );
			$input['accept_text'] = sanitize_text_field( isset( $input['accept_text'] ) && $input['accept_text'] !== '' ? $input['accept_text'] : $this->defaults['general']['accept_text'] );
			$input['refuse_text'] = sanitize_text_field( isset( $input['refuse_text'] ) && $input['refuse_text'] !== '' ? $input['refuse_text'] : $this->defaults['general']['refuse_text'] );
			$input['refuse_opt'] = (bool) isset( $input['refuse_opt'] ) ? 'yes' : 'no';

			// autohide notice
			$input['autohide_notice'] = isset( $input['autohide_notice'] ) ? $input['autohide_notice'] : false;

			$allowed_html = apply_filters( 'acn_refuse_code_allowed_html', array_merge( wp_kses_allowed_html( 'post' ), array(
				'script' => array(
					'type'		 => array(),
					'src'		 => array(),
					'charset'	 => array(),
					'async'		 => array()
				),
				'noscript' => array()
			) ) );

			$input['refuse_code'] = wp_kses( isset( $input['refuse_code'] ) && $input['refuse_code'] !== '' ? $input['refuse_code'] : $this->defaults['general']['refuse_code'], $allowed_html );

			// link target
			$input['link_target'] = sanitize_text_field( isset( $input['link_target'] ) && in_array( $input['link_target'], array_keys( $this->link_target ) ) ? $input['link_target'] : $this->defaults['general']['link_target'] );

			// time
			$input['time'] = sanitize_text_field( isset( $input['time'] ) && in_array( $input['time'], array_keys( $this->times ) ) ? $input['time'] : $this->defaults['general']['time'] );

			// script placement
			$input['script_placement'] = sanitize_text_field( isset( $input['script_placement'] ) && in_array( $input['script_placement'], array_keys( $this->script_placements ) ) ? $input['script_placement'] : $this->defaults['general']['script_placement'] );

			// hide effect
			$input['hide_effect'] = sanitize_text_field( isset( $input['hide_effect'] ) && in_array( $input['hide_effect'], array_keys( $this->effects ) ) ? $input['hide_effect'] : $this->defaults['general']['hide_effect'] );

			// buttons style
			$input['buttons_style'] = sanitize_text_field( isset( $input['buttons_style'] ) && in_array( $input['buttons_style'], array_keys( $this->buttons_styles ) ) ? $input['buttons_style'] : $this->defaults['general']['buttons_style'] );

			// on scroll
			$input['on_scroll'] = (bool) isset( $input['on_scroll'] ) ? 'yes' : 'no';

			// on scroll
			$input['redirection'] = isset( $input['redirection'] );

			// on scroll offset
			$input['on_scroll_offset'] = absint( isset( $input['on_scroll_offset'] ) && $input['on_scroll_offset'] !== '' ? $input['on_scroll_offset'] : $this->defaults['general']['on_scroll_offset'] );

			// deactivation
			$input['deactivation_delete'] = (bool) isset( $input['deactivation_delete'] ) ? 'yes' : 'no';

			// read more
			$input['see_more'] = (bool) isset( $input['see_more'] ) ? 'yes' : 'no';
			$input['see_more_opt']['text'] = sanitize_text_field( isset( $input['see_more_opt']['text'] ) && $input['see_more_opt']['text'] !== '' ? $input['see_more_opt']['text'] : $this->defaults['general']['see_more_opt']['text'] );
			$input['see_more_opt']['link_type'] = sanitize_text_field( isset( $input['see_more_opt']['link_type'] ) && in_array( $input['see_more_opt']['link_type'], array_keys( $this->links ) ) ? $input['see_more_opt']['link_type'] : $this->defaults['general']['see_more_opt']['link_type'] );

			if ( $input['see_more_opt']['link_type'] === 'custom' )
				$input['see_more_opt']['link'] = esc_url( $input['see_more'] === 'yes' ? $input['see_more_opt']['link'] : 'empty' );
			elseif ( $input['see_more_opt']['link_type'] === 'page' )
				$input['see_more_opt']['id'] = ( $input['see_more'] === 'yes' ? (int) $input['see_more_opt']['id'] : 'empty' );

			$input['translate'] = false;

			// WPML >= 3.2
			if ( defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '3.2', '>=' ) ) {
				do_action( 'wpml_register_single_string', 'Adsimple Cookie Notice', 'Message in the notice', $input['message_text'] );
				do_action( 'wpml_register_single_string', 'Adsimple Cookie Notice', 'Button text', $input['accept_text'] );
				do_action( 'wpml_register_single_string', 'Adsimple Cookie Notice', 'Refuse button text', $input['refuse_text'] );
				do_action( 'wpml_register_single_string', 'Adsimple Cookie Notice', 'Read more text', $input['see_more_opt']['text'] );

				if ( $input['see_more_opt']['link_type'] === 'custom' )
					do_action( 'wpml_register_single_string', 'Adsimple Cookie Notice', 'Custom link', $input['see_more_opt']['link'] );
			}
		} elseif ( isset( $_POST['reset_adsimple_cookie_notice_options'] ) ) {

			$input = $this->defaults['general'];

			add_settings_error( 'reset_adsimple_cookie_notice_options', 'reset_adsimple_cookie_notice_options', __( 'Settings restored to defaults.', 'adsimple-cookie-notice' ), 'updated' );

		}

		return $input;
	}


    protected function get_inline_style($data) {
       $str = '';
        if(is_array($data)) {
            $arr = array();
            foreach ($data as $style => $value) {
                $arr[] = $style . ': ' . $value . ';';
            }


            $str = implode(' ', $arr);
	if($str) {
                $str = ' style="' . $str . '"';
	}
        }
        return $str;
    }


    public function add_adsimple_script(){
        $options = get_option( 'adsimple_cookie_notice_options');
        $scriptCode = '';
        if(!empty($options['id_key'])) {
            $scriptCode = get_option('acn_data_script');

            if(!$scriptCode) {
            	$scriptCode = $this->get_acn_script($options['id_key']);
            }
        }
        echo $scriptCode;
    }


    public function get_acn_script( $id_key )
    {
    	$id_key = trim( $id_key );
	    $id_key = preg_replace('/[^0-9a-z]/iu', '', $id_key );
	    $scriptCode = '';

	    if(!empty($id_key)) {
	    	if(self::ACN_DEV) {
    		    $url = 'https://dev.adsimple.at/wp-json/cookienotice/v1/code?id_key='.$id_key.'&domain='.get_home_url();
    			$args = array(
    			  'headers' => array(
    			    'Authorization' => 'Basic ' . base64_encode(self::ACN_DEV_USER . ':' . self::ACN_DEV_PASS)
    			  )
    			);
    			$res = wp_remote_request( $url, $args );
	    	} else {
			    $res = wp_remote_get('https://adsimple.at/wp-json/cookienotice/v1/code?id_key='.$id_key.'&domain='.get_home_url());
	    	}

		    $raw = $res['body'];
		    $json = @json_decode($raw,true);
		    if($json && !empty($json['result'])) {
		    	$scriptCode = $json['result'];
		    }
	    }

	    update_option('acn_data_script', $scriptCode);

	    return $scriptCode;
    }

    /**
	 * Cookie notice output.
	 *
	 * @return mixed
	 */
	public function add_adsimple_cookie_notice() {

		if($this->cookies_set()) {
			return;
		}

        $options = get_option( 'adsimple_cookie_notice_options');
        if(!empty($options['id_key'])) {
            $scriptCode = get_option('acn_data_script');
            if($scriptCode) {
                return;
            }
        }


		// WPML >= 3.2
		if ( defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '3.2', '>=' ) ) {
			$this->options['general']['message_text'] = apply_filters( 'wpml_translate_single_string', $this->options['general']['message_text'], 'Adsimple Cookie Notice', 'Message in the notice' );
			$this->options['general']['accept_text'] = apply_filters( 'wpml_translate_single_string', $this->options['general']['accept_text'], 'Adsimple Cookie Notice', 'Button text' );
			$this->options['general']['refuse_text'] = apply_filters( 'wpml_translate_single_string', $this->options['general']['refuse_text'], 'Adsimple Cookie Notice', 'Refuse button text' );
			$this->options['general']['see_more_opt']['text'] = apply_filters( 'wpml_translate_single_string', $this->options['general']['see_more_opt']['text'], 'Adsimple Cookie Notice', 'Read more text' );
			$this->options['general']['see_more_opt']['link'] = apply_filters( 'wpml_translate_single_string', $this->options['general']['see_more_opt']['link'], 'Adsimple Cookie Notice', 'Custom link' );
		// WPML and Polylang compatibility
		} elseif ( function_exists( 'icl_t' ) ) {
			$this->options['general']['message_text'] = icl_t( 'Adsimple Cookie Notice', 'Message in the notice', $this->options['general']['message_text'] );
			$this->options['general']['accept_text'] = icl_t( 'Adsimple Cookie Notice', 'Button text', $this->options['general']['accept_text'] );
			$this->options['general']['refuse_text'] = icl_t( 'Adsimple Cookie Notice', 'Refuse button text', $this->options['general']['refuse_text'] );
			$this->options['general']['see_more_opt']['text'] = icl_t( 'Adsimple Cookie Notice', 'Read more text', $this->options['general']['see_more_opt']['text'] );
			$this->options['general']['see_more_opt']['link'] = icl_t( 'Adsimple Cookie Notice', 'Custom link', $this->options['general']['see_more_opt']['link'] );
		}

		if ( function_exists( 'icl_object_id' ) )
			$this->options['general']['see_more_opt']['id'] = icl_object_id( $this->options['general']['see_more_opt']['id'], 'page', true );

		// get cookie container args
		$options = apply_filters( 'acn_adsimple_cookie_notice_args', array(
			'position'		   => $this->options['general']['position'],
			'colors'		   => $this->defaults['general']['colors'],
			'buttons_style'	   => $this->options['general']['buttons_style'],
			'message_text'	   => $this->options['general']['message_text'],
			'accept_text'	   => $this->options['general']['accept_text'],
			'refuse_text'	   => $this->options['general']['refuse_text'],
			'refuse_opt'	   => $this->options['general']['refuse_opt'],
			'see_more'		   => $this->options['general']['see_more'],
			'see_more_opt'	   => $this->options['general']['see_more_opt'],
			'see_more_opt_tab' => $this->options['general']['see_more_opt_tab'],
			'link_target'	   => $this->options['general']['link_target'],
			'font'			   => $this->options['general']['font'],
			'font_size'		   => $this->options['general']['font_size'],
			'hide_effect'	   => $this->options['general']['hide_effect'],
			'copyright'		   => $this->options['general']['copyright'],
		) );

		// message output

		$bar_styles = array();
		$buttons_styles = array();
		$buttons_classes = array('fusion-button', 'button-flat', 'button-small');
		$bar_classes = array();
		$readmore_styles = array();

		if (isset($options['colors']['text']) && !empty($options['colors']['text'])) {
			$bar_styles['color'] = $options['colors']['text'];
		}
		if (isset($options['colors']['bar']) && !empty($options['colors']['bar'])) {
			$bar_styles['background-color'] = $options['colors']['bar'];
		}
		if (isset($options['colors']['border']) && !empty($options['colors']['border'])) {
			$pos = $options['position'] == 'top' ? 'bottom' : 'top';
			$bar_styles['border-' . $pos] = '3px solid ' . $options['colors']['border'];
		}
		if (isset($options['font']) && !empty($options['font'])) {
			$bar_styles['font-family'] = $options['font'];
		}
		if (isset($options['font_size']) && !empty($options['font_size'])) {
			if($options['font_size'] != 'default') {
				$bar_styles['font-size'] = $options['font_size'] . 'px';
			}
		}


		if (isset($options['colors']['button']) && !empty($options['colors']['button'])) {
			$buttons_styles['background-color'] = $options['colors']['button'];
		}
		if (isset($options['colors']['button_text']) && !empty($options['colors']['button_text'])) {
			$buttons_styles['color'] = $options['colors']['button_text'];
		}
		if (isset($options['colors']['button_border']) && !empty($options['colors']['button_border'])) {
			$buttons_styles['border'] = '2px solid ' . $options['colors']['button_border'];
		}


		if(isset($options['position']) && $options['position'] != 'before_content') {
			$bar_classes[] = 'acn-' . $options['position'];
		}

		if(isset($options['hide_effect']) && $options['hide_effect'] == 'css_slide') {
			$bar_classes[] = 'adsimple-cookie-notice-slide';
		}

		if(isset($options['buttons_style']) && $options['buttons_style']) {
			$buttons_classes[] = 'fusion-button-' . $options['buttons_style'];
		}

		$bar_class = implode(' ', $bar_classes);
		$buttons_class = implode(' ', $buttons_classes);

		$readmore_styles = $bar_styles;

		if (isset($options['colors']['readmore_border']) && !empty($options['colors']['readmore_border'])) {
			$readmore_styles['border'] = '2px solid ' . $options['colors']['readmore_border'];
		} else {
			unset($readmore_styles['border']);
			unset($readmore_styles['border-top']);
		}

                $bar_style = $this->get_inline_style($bar_styles);
                $buttons_style = $this->get_inline_style($buttons_styles);
                $readmore_style = $this->get_inline_style($readmore_styles);


		$output = sprintf('<div id="adsimple-cookie-notice" role="banner" class="%s"%s>', $bar_class, $bar_style);

		$output .= sprintf('<div class="adsimple-cookie-notice-container">
								<div class="adsimple-cookie-notice-content">
								<div class="adsimple-cookie-notice-content__item adsimple-cookie-notice-content__item-text" id="acn-notice-text"><div class="adsimple-cookie-notice-content__item-text-inner">%s</div></div>', $options['message_text']);
		$output .= '<div class="adsimple-cookie-notice-content__item adsimple-cookie-notice-content__links"><div class="adsimple-cookie-notice-content__links-inner">';
		$output .= sprintf('<a%s href="#" id="acn-accept-cookie" data-cookie-set="accept" class="acn-set-cookie%s">%s</a>', $buttons_style, $buttons_class ? ' ' . $buttons_class : '', $options['accept_text']);

		if(isset($options['refuse_opt']) && $options['refuse_opt'] == 'yes') {
			$output .= sprintf('<a%s href="#" id="acn-refuse-cookie" data-cookie-set="refuse" class="acn-set-cookie%s">%s</a>', $buttons_style, $buttons_class ? ' ' . $buttons_class : '', $options['refuse_text']);
		}

		if(isset($options['see_more']) && $options['see_more'] == 'yes') {
			$href = $options['see_more_opt']['link_type'] === 'custom' ? $options['see_more_opt']['link'] : get_permalink( $options['see_more_opt']['id'] );
			if(!$href) $href = '#';
			$output .= sprintf('<a%s href="%s" target="%s" id="acn-more-info" class="acn-more-info%s">%s</a>', $buttons_style, $href, $options['link_target'], $buttons_class ? ' ' . $buttons_class : '', $options['see_more_opt']['text']);
		}
		$output .= '</div></div>';

                //acn_copyright
                if(isset($options['copyright']) && $options['copyright'] == 1) {
                $output .= '</div><span class="powered-link"><a title ="AdSimple Content Marketing Agentur" href="https://www.adsimple.at/" target="_blank" rel="follow">Powered by AdSimple</a></span></div>';
                } else {
                    $output .= '</div></div>';
                }
		$output .= '</div>';

		if(isset($options['see_more']) && $options['see_more'] == 'yes') {
			$output .= sprintf('<div id="adsimple-readmore-tab"%s><span>%s</span></div>', $readmore_style, $options['see_more_opt_tab']['text']);
		}

		echo apply_filters( 'acn_adsimple_cookie_notice_output', $output, $options );

	}

	/**
	 * Checks if cookie is setted
	 *
	 * @return bool
	 */
	public function cookies_set() {
		return apply_filters( 'acn_is_cookie_set', isset( $_COOKIE[self::$cookie['name']] ) );
	}

	/**
	 * Checks if third party non functional cookies are accepted
	 *
	 * @return bool
	 */
	public static function cookies_accepted() {
		return apply_filters( 'acn_is_cookie_accepted', isset( $_COOKIE[self::$cookie['name']] ) && strtoupper( $_COOKIE[self::$cookie['name']] ) === self::$cookie['value'] );
	}

	/**
	 * Get default settings.
	 */
	public function get_defaults() {
		return $this->defaults;
	}

	/**
	 * Add links to settings page.
	 *
	 * @param array $links
	 * @param string $file
	 * @return array
	 */
	public function plugin_action_links( $links, $file ) {
		if ( ! current_user_can( apply_filters( 'acn_manage_adsimple_cookie_notice_cap', 'manage_options' ) ) )
			return $links;

		$plugin = plugin_basename( __FILE__ );

		if ( $file == $plugin )
			array_unshift( $links, sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php?page=adsimple-cookie-notice' ), __( 'Settings', 'adsimple-cookie-notice' ) ) );

		return $links;
	}

	/**
	 * Add ACN cookie to dynamic caches.
	 *
	 * @param $cookies
	 * @return array
	 */
	public function rocket_cache_dynamic_cookies( $cookies ) {
		$cookies[] = self::$cookie['name'];
		return $cookies;
	}

	protected function wp_rocket_housekeeping() {
		if (
			! function_exists( 'flush_rocket_htaccess' ) ||
			! function_exists( 'rocket_generate_config_file' ) ||
			! function_exists( 'rocket_clean_domain' )
		) {
			return false;
		}
		flush_rocket_htaccess();
		rocket_generate_config_file();
		rocket_clean_domain();
		return true;
	}

	/**
	 * Activate the plugin.
	 */
	public function activation() {
		$this->wp_rocket_housekeeping();
		add_option( 'adsimple_cookie_notice_options', $this->defaults['general'], '', 'no' );
	}

	/**
	 * Deactivate the plugin.
	 */
	public function deactivation() {
		remove_filter( 'rocket_cache_dynamic_cookies', array( $this, 'rocket_cache_dynamic_cookies' ));

		$this->wp_rocket_housekeeping();

		if ( $this->options['general']['deactivation_delete'] === 'yes' )
			delete_option( 'adsimple_cookie_notice_options' );
	}

	/**
	 * Load scripts and styles - admin.
	 */
	public function admin_enqueue_scripts( $page ) {
		if ( $page !== 'settings_page_adsimple-cookie-notice' )
			return;

		wp_enqueue_script(
			'adsimple-cookie-notice-admin', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery', 'wp-color-picker' ),
                        $this->defaults['version']
		);

		wp_enqueue_script( 'ace', 'https://cdnjs.cloudflare.com/ajax/libs/ace/1.3.1/ace.js', array('jquery'), '1.3.1', true);
		$init_ace_js = '
			var editor = ace.edit("acn_custom_css");
		    editor.session.setMode("ace/mode/css");

		    var input = jQuery("#acn_custom_css_value");
            editor.getSession().on("change", function () {
	            input.val(editor.getSession().getValue());
	        });
		';
		wp_add_inline_script( 'ace', $init_ace_js );

		wp_localize_script(
			'adsimple-cookie-notice-admin', 'acnArgs', array(
				'resetToDefaults'	=> __( 'Are you sure you want to reset these settings to defaults?', 'adsimple-cookie-notice' )
			)
		);

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'adsimple-cookie-notice-admin', plugins_url( 'css/admin.css', __FILE__ ),array(), time());
	}

	/**
	 * Load scripts and styles - frontend.
	 */
	public function wp_enqueue_scripts() {
		wp_enqueue_script(
			'adsimple-cookie-notice-front', plugins_url( 'js/front.js', __FILE__ ), array( 'jquery' ), $this->defaults['version'], isset( $this->options['general']['script_placement'] ) && $this->options['general']['script_placement'] === 'footer' ? true : false
		);

		wp_localize_script(
			'adsimple-cookie-notice-front',
			'acnArgs',
			array(
				'ajaxurl'			=> admin_url( 'admin-ajax.php' ),
				'hideEffect'		=> $this->options['general']['hide_effect'],
				'onScroll'			=> $this->options['general']['on_scroll'],
				'onScrollOffset'	=> $this->options['general']['on_scroll_offset'],
				'onLoad'			=> $this->options['general']['on_load'],
				'onHide'			=> $this->options['general']['on_hide'],
				'autoHide'			=> $this->options['general']['autohide_notice'] && $this->options['general']['autohide_delay'] ? $this->options['general']['autohide_delay'] : null,
				'cookieName'		=> self::$cookie['name'],
				'cookieValue'		=> self::$cookie['value'],
				'cookieTime'		=> $this->times[$this->options['general']['time']][1],
				'cookiePath'		=> ( defined( 'COOKIEPATH' ) ? COOKIEPATH : '' ),
				'cookieDomain'		=> ( defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '' ),
				'redirection'		=> $this->options['general']['redirection'],
				'cache'				=> defined( 'WP_CACHE' ) && WP_CACHE
			)
		);

		wp_enqueue_style( 'adsimple-cookie-notice-front', plugins_url( 'css/front.css', __FILE__ ),array(),'1.0.9' );

		$allowed_html = apply_filters( 'acn_custom_css_allowed_html', array(
			'style' => array()
		));

		$styles = html_entity_decode( trim( wp_kses( $this->options['general']['custom_css'], $allowed_html ) ) );

		if(!empty($styles)) {
			wp_add_inline_style( 'adsimple-cookie-notice-front', $styles);
		}
	}

	/**
	 * Print non functional javascript.
	 *
	 * @return mixed
	 */
	public function wp_print_footer_scripts() {
		$allowed_html = apply_filters( 'acn_refuse_code_allowed_html', array_merge( wp_kses_allowed_html( 'post' ), array(
			'script' => array(
				'type'		 => array(),
				'src'		 => array(),
				'charset'	 => array(),
				'async'		 => array()
			),
			'noscript' => array()
		) ) );

		$scripts = apply_filters( 'acn_refuse_code_scripts_html', html_entity_decode( trim( wp_kses( $this->options['general']['refuse_code'], $allowed_html ) ) ) );

		if ( $this->cookies_accepted() && ! empty( $scripts ) ) {
			echo $scripts;
		}
	}

}

/**
 * Get the cookie notice status
 *
 * @return boolean
 */
function acn_cookies_accepted() {
	return (bool) Adsimple_Cookie_Notice::cookies_accepted();
}
