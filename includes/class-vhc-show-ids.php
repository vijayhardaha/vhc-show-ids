<?php
/**
 *  WP Show IDs setup
 *
 * @package VHC_Show_Ids
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main VHC_Show_Ids Class.
 *
 * @class VHC_Show_Ids
 */
class VHC_Show_Ids {
	/**
	 * Admin notices to add.
	 *
	 * @var array Array of admin notices.
	 * @since 1.0.0
	 */
	private $notices = array();

	/**
	 * VHC_Show_Ids Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->define_constants();

		register_activation_hook( VHC_SHOW_IDS_PLUGIN_FILE, array( $this, 'activation_check' ) );

		register_shutdown_function( array( $this, 'log_errors' ) );

		add_action( 'admin_init', array( $this, 'check_environment' ) );
		add_action( 'admin_init', array( $this, 'add_plugin_notices' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );

		// If the environment check fails, initialize the plugin.
		if ( $this->is_environment_compatible() ) {
			add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
		}
	}

	/**
	 * Cloning instances is forbidden due to singleton pattern.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, sprintf( 'You cannot clone instances of %s.', esc_html( get_class( $this ) ) ), '1.0.0' );
	}

	/**
	 * Unserializing instances is forbidden due to singleton pattern.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, sprintf( 'You cannot unserialize instances of %s.', esc_html( get_class( $this ) ) ), '1.0.0' );
	}

	/**
	 * Ensures fatal errors are logged so they can be picked up in the status report.
	 *
	 * @since 1.0.0
	 */
	public function log_errors() {
		$error = error_get_last();
		if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ), true ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
				/* translators: 1: error message 2: file name and path 3: line number */
				$error_message = sprintf( __( '%1$s in %2$s on line %3$s', 'vhc-show-ids' ), $error['message'], $error['file'], $error['line'] ) . PHP_EOL;
				// phpcs:disable WordPress.PHP.DevelopmentFunctions
				error_log( $error_message );
				// phpcs:enable WordPress.PHP.DevelopmentFunctions
			}
		}
	}

	/**
	 * Define WC Constants.
	 *
	 * @since 1.0.0
	 */
	private function define_constants() {
		$plugin_data = get_plugin_data( VHC_SHOW_IDS_PLUGIN_FILE );
		$this->define( 'VHC_SHOW_IDS_ABSPATH', dirname( VHC_SHOW_IDS_PLUGIN_FILE ) . '/' );
		$this->define( 'VHC_SHOW_IDS_PLUGIN_BASENAME', plugin_basename( VHC_SHOW_IDS_PLUGIN_FILE ) );
		$this->define( 'VHC_SHOW_IDS_PLUGIN_NAME', $plugin_data['Name'] );
		$this->define( 'VHC_SHOW_IDS_VERSION', $plugin_data['Version'] );
		$this->define( 'VHC_SHOW_IDS_MIN_PHP_VERSION', $plugin_data['RequiresPHP'] );
		$this->define( 'VHC_SHOW_IDS_MIN_WP_VERSION', $plugin_data['RequiresWP'] );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 *
	 * @since 1.0.0
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Checks the server environment and other factors and deactivates plugins as necessary.
	 *
	 * Based on http://wptavern.com/how-to-prevent-wordpress-plugins-from-activating-on-sites-with-incompatible-hosting-environments
	 *
	 * @since 1.0.0
	 */
	public function activation_check() {
		if ( ! $this->is_environment_compatible() ) {
			$this->deactivate_plugin();
			wp_die(
				sprintf(
					/* translators: %s Plugin Name */
					esc_html__(
						'%1$s could not be activated. %2$s',
						'vhc-show-ids'
					),
					esc_html( VHC_SHOW_IDS_PLUGIN_NAME ),
					esc_html( $this->get_environment_message() )
				)
			);
		}
	}

	/**
	 * Checks the environment on loading WordPress, just in case the environment changes after activation.
	 *
	 * @since 1.0.0
	 */
	public function check_environment() {
		if ( ! $this->is_environment_compatible() && is_plugin_active( VHC_SHOW_IDS_PLUGIN_BASENAME ) ) {
			$this->deactivate_plugin();
			$this->add_admin_notice(
				'bad_environment',
				'error',
				sprintf(
					/* translators: %s Plugin Name */
					__( '%s has been deactivated.', 'vhc-show-ids' ),
					VHC_SHOW_IDS_PLUGIN_NAME
				) . ' ' . $this->get_environment_message()
			);
		}
	}

	/**
	 * Adds notices for out-of-date WordPress and/or WooCommerce versions.
	 *
	 * @since 1.0.0
	 */
	public function add_plugin_notices() {
		if ( ! $this->is_wp_compatible() ) {
			$this->add_admin_notice(
				'update_wordpress',
				'error',
				sprintf(
					/* translators: 1: Plugin Name 2: Minimum WP Version 3: Update Url */
					__( '%1$s requires WordPress version %2$s or higher. Please %3$supdate WordPress &raquo;%4$s', 'vhc-show-ids' ),
					VHC_SHOW_IDS_PLUGIN_NAME,
					VHC_SHOW_IDS_MIN_WP_VERSION,
					'<a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">',
					'</a>'
				)
			);
		}
	}

	/**
	 * Determines if the required plugins are compatible.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function plugins_compatible() {
		return $this->is_wp_compatible();
	}

	/**
	 * Determines if the WordPress compatible.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function is_wp_compatible() {
		if ( ! VHC_SHOW_IDS_MIN_WP_VERSION ) {
			return true;
		}
		return version_compare( get_bloginfo( 'version' ), VHC_SHOW_IDS_MIN_WP_VERSION, '>=' );
	}

	/**
	 * Deactivates the plugin.
	 *
	 * @since 1.0.0
	 */
	protected function deactivate_plugin() {
		deactivate_plugins( VHC_SHOW_IDS_PLUGIN_FILE );

		if ( isset( $_GET['activate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			unset( $_GET['activate'] ); // phpcs:ignore WordPress.Security.NonceVerification
		}
	}

	/**
	 * Adds an admin notice to be displayed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug    The slug for the notice.
	 * @param string $class   The css class for the notice.
	 * @param string $message The notice message.
	 */
	private function add_admin_notice( $slug, $class, $message ) {
		$this->notices[ $slug ] = array(
			'class'   => $class,
			'message' => $message,
		);
	}

	/**
	 * Displays any admin notices added with VHC_Show_Ids::add_admin_notice()
	 *
	 * @since 1.0.0
	 */
	public function admin_notices() {
		foreach ( (array) $this->notices as $notice_key => $notice ) {
			?>
			<div class="<?php echo esc_attr( $notice['class'] ); ?>">
				<p>
				<?php
				echo wp_kses(
					$notice['message'],
					array(
						'strong' => array(),
						'a'      => array(
							'href'   => array(),
							'target' => array(),
						),
					)
				);
				?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Determines if the server environment is compatible with this plugin.
	 *
	 * Override this method to add checks for more than just the PHP version.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function is_environment_compatible() {
		return version_compare( phpversion(), VHC_SHOW_IDS_MIN_PHP_VERSION, '>=' );
	}

	/**
	 * Gets the message for display when the environment is incompatible with this plugin.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_environment_message() {
		return sprintf(
			/* translators: 1: Minimum PHP Version 2: Current PHP Version */
			__( 'The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'vhc-show-ids' ),
			VHC_SHOW_IDS_MIN_PHP_VERSION,
			phpversion()
		);
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/vhc-show-ids/vhc-show-ids-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/vhc-show-ids-LOCALE.mo
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_textdomain() {
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			$locale = is_admin() ? get_user_locale() : get_locale();
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'vhc-show-ids' );

		unload_textdomain( 'vhc-show-ids' );
		load_textdomain( 'vhc-show-ids', WP_LANG_DIR . '/vhc-show-ids/vhc-show-ids-' . $locale . '.mo' );
		load_plugin_textdomain( 'vhc-show-ids', false, plugin_basename( dirname( VHC_SHOW_IDS_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0.0
	 */
	public function init_plugin() {
		if ( ! $this->plugins_compatible() ) {
			return;
		}

		// Set up localisation.
		$this->load_plugin_textdomain();

		if ( apply_filters( 'vhc_show_ids_enable_copy', true ) === true ) {
			// Enqueue scripts and styles.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );
		}

		// Hook the action with admin init.
		add_action( 'admin_init', array( $this, 'add_ids_to_row_actions' ) );
	}

	/**
	 * Get the plugin url.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', VHC_SHOW_IDS_PLUGIN_FILE ) );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts_and_styles() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register admin styles.
		wp_enqueue_style( 'vhc-show-ids-admin', $this->plugin_url() . '/assets/css/admin' . $suffix . '.css', array(), VHC_SHOW_IDS_VERSION );

		// Register scripts.
		wp_enqueue_script( 'vhc-show-ids-admin', $this->plugin_url() . '/assets/js/admin' . $suffix . '.js', array( 'clipboard', 'jquery' ), VHC_SHOW_IDS_VERSION, true );
	}

	/**
	 * Add IDs to row actions.
	 *
	 * @since 1.0.0
	 */
	public function add_ids_to_row_actions() {
		if ( apply_filters( 'vhc_show_ids_enable_for_posts', true ) === true ) {
			// Show ids in posts.
			add_filter( 'post_row_actions', array( __CLASS__, 'show_post_id' ), 99, 2 );
		}

		if ( apply_filters( 'vhc_show_ids_enable_for_pages', true ) === true ) {
			// Show ids in pages.
			add_filter( 'page_row_actions', array( __CLASS__, 'show_post_id' ), 99, 2 );
		}

		if ( apply_filters( 'vhc_show_ids_enable_for_medias', true ) === true ) {
			// Show ids in media.
			add_filter( 'media_row_actions', array( __CLASS__, 'show_media_id' ), 99, 2 );
		}

		if ( apply_filters( 'vhc_show_ids_enable_for_terms', true ) === true ) {
			// Show ids in tags.
			add_filter( 'tag_row_actions', array( __CLASS__, 'show_term_id' ), 99, 2 );
		}

		if ( apply_filters( 'vhc_show_ids_enable_for_comments', true ) === true ) {
			// Show ids in comments.
			add_filter( 'comment_row_actions', array( __CLASS__, 'show_comment_id' ), 99, 2 );
		}

		if ( apply_filters( 'vhc_show_ids_enable_for_users', true ) === true ) {
			// Show ids in users.
			add_filter( 'user_row_actions', array( __CLASS__, 'show_user_id' ), 99, 2 );
		}
	}

	/**
	 * Display ID is posts, and custom post types.
	 *
	 * @param array   $actions Row actions.
	 * @param WP_Post $post Post object.
	 * @return array Modified row actions.
	 */
	public static function show_post_id( $actions, $post ) {
		if ( apply_filters( 'vhc_show_ids_enable_for_post_' . $post->post_type, true ) === true ) {
			return self::prepend_to_row_actions( $actions, $post->ID );
		}

		return $actions;
	}

	/**
	 * Display ID is media lists.
	 *
	 * @param array   $actions Row actions.
	 * @param WP_Post $media Media post object.
	 * @return array Modified row actions.
	 */
	public static function show_media_id( $actions, $media ) {
		return self::prepend_to_row_actions( $actions, $media->ID );
	}

	/**
	 * Display ID is categories, tags and custom taxonomies term.
	 *
	 * @param array   $actions Row actions.
	 * @param WP_Term $term Term object.
	 * @return array Modified row actions.
	 */
	public static function show_term_id( $actions, $term ) {
		if ( apply_filters( 'vhc_show_ids_enable_for_taxonomy_' . $term->taxonomy, true ) === true ) {
			return self::prepend_to_row_actions( $actions, $term->term_id );
		}
		return $actions;
	}

	/**
	 * Display ID is comments row actions.
	 *
	 * @param array       $actions Row actions.
	 * @param WP_Comments $comment Term object.
	 * @return array Modified row actions.
	 */
	public static function show_comment_id( $actions, $comment ) {
		return self::prepend_to_row_actions( $actions, $comment->comment_ID );
	}

	/**
	 * Display ID is users row actions.
	 *
	 * @param array   $actions Row actions.
	 * @param WP_User $user User object.
	 * @return array Modified row actions.
	 */
	public static function show_user_id( $actions, $user ) {
		return self::prepend_to_row_actions( $actions, $user->ID );
	}

	/**
	 * Prepend ID in row actions.
	 *
	 * @param array $actions Row actions.
	 * @param int   $id Post ID | Term ID | User ID | Comment ID.
	 * @return array Modified row actions.
	 */
	private static function prepend_to_row_actions( $actions, $id ) {
		// Check if actions is empty.
		if ( ! empty( $actions ) ) {
			// Check if id key already exists.
			// Some plugins add id in row actions by default so we override it.
			if ( isset( $actions['id'] ) ) {
				unset( $actions['id'] );
			}

			// Check if id key doesn't exists in action anymore.
			if ( ! isset( $actions['id'] ) ) {
				// Prepare display text.
				$id_text = sprintf(
					/* translators: %s Object ID */
					__( 'ID: %s', 'vhc-show-ids' ),
					esc_html( $id )
				);

				$classes   = array( 'vhc-column-id' );
				$classes[] = apply_filters( 'vhc_show_ids_enable_copy', true ) ? 'vhc-has-copy' : '';
				$classes   = array_filter( $classes );

				// Prepare the action array.
				$id_action = array(
					'id' => sprintf(
						/* translators: 1: Object ID. 2: Object ID*/
						'<span class="' . esc_attr( join( ' ', $classes ) ) . '" data-clipboard-text="%1$s" aria-label="' . esc_attr__( 'Click to copy', 'vhc-show-ids' ) . '" data-success-text="' . esc_attr__( 'Copied!', 'vhc-show-ids' ) . '">%2$s</span>',
						esc_attr( $id ),
						esc_html( $id_text ),
					),
				);

				// Merge the $actions with $id_action so that ID will be at first.
				$actions = array_merge( $id_action, $actions );
			}
		}

		return $actions;
	}
}
