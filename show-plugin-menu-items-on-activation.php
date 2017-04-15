<?php
/**
 * Plugin Name: Show Plugin Menu Items on Activation
 * Plugin URI:  https://github.com/kellenmace/show-plugin-menu-items-on-activation
 * Description: Identify any newly added menu items on plugin activation.
 * Version:     1.0.0
 * Author:      Kellen Mace
 * Author URI:  https://kellenmace.com/
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Main Show Plugin Menu Items on Activation class.
 *
 * @since  1.0.0
 */
final class SPMIOA_Show_Plugin_Menu_Items {

	/**
	 * Current version.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	const VERSION = '1.0.0';

	/**
	 * Singleton instance of class.
	 *
	 * @var    SPMIOA_Show_Plugin_Menu_Items
	 * @since  1.0.0
	 */
	protected static $single_instance = null;

	/**
	 * Whether the menu items have been saved before plugin activation.
	 *
	 * @var bool
	 */
	protected $have_before_menu_items_been_saved = false;

	/**
	 * The top-level menu items before plugin activation.
	 *
	 * @var array
	 */
	protected $menu_before_activation = array();

	/**
	 * The submenu items before plugin activation.
	 *
	 * @var array
	 */
	protected $submenu_before_activation = array();

	/**
	 * The new top-level menu items in a formatted array.
	 *
	 * @var array
	 */
	protected $new_menu_items_formatted = array();

	/**
	 * The new submenu items in a formatted array.
	 *
	 * @var array
	 */
	protected $new_submenu_items_formatted = array();

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since   1.0.0
	 * @return  SPMIOA_Show_Plugin_Menu_Items A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Sets up our plugin.
	 *
	 * @since  1.0.0
	 */
	protected function __construct() {
		$this->load_textdomain();
	}

	/**
	 * Load translated strings.
	 *
	 * @since  1.0.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'show-plugin-menu-items', false, __DIR__ . '/languages/' );
	}

	/**
	 * Add hooks.
	 *
	 * @since  1.0.0
	 */
	public function hooks() {
		add_action( 'activate_plugin',       array( $this, 'save_menus_before_plugin_activation' ) );
		add_action( 'admin_init',            array( $this, 'set_before_activation_menu_properties' ) );
		add_action( 'admin_init',            array( $this, 'set_new_menu_item_properties' ), 11 );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_display_admin_pointers' ) );
		add_action( 'admin_notices',         array( $this, 'maybe_display_admin_notice' ) );
	}

	/**
	 * Save the menu and submenu data as properties before plugin activation.
	 *
	 * @since  1.0.0
	 */
	public function save_menus_before_plugin_activation() {

		global $menu;
		global $submenu;

		// Bail if we've already saved the menus before plugin activation.
		if ( $this->have_before_menu_items_been_saved ) {
			return;
		}

		if ( $menu && is_array( $menu ) ) {
			update_option( 'spmioa_menu_before_plugin_activation', $this->remove_invalid_menu_items( $menu ), false );
		}

		if ( $submenu && is_array( $submenu ) ) {
			update_option( 'spmioa_submenu_before_plugin_activation', $submenu, false );
		}

		$this->have_before_menu_items_been_saved = true;
	}

	/**
	 * Remove any menu items that are missing the required values (like separator menu items).
	 *
	 * @since  1.0.0
	 * @param  array $menu The menu.
	 * @return array       The menu, with any invalid menu items removed.
	 */
	private function remove_invalid_menu_items( array $menu ) {

		return array_filter( $menu, function( $menu_item ) {
			return ! empty( $menu_item[0] ) && ! empty( $menu_item[2] ) && ! empty( $menu_item[5] );
		} );
	}

	/**
	 * Get the options for the menu and submenu items before plugin activation & set as class properties.
	 *
	 * @since  1.0.0
	 */
	public function set_before_activation_menu_properties() {
		$this->menu_before_activation    = get_option( 'spmioa_menu_before_plugin_activation', array() );
		$this->submenu_before_activation = get_option( 'spmioa_submenu_before_plugin_activation', array() );

		// Delete the options since they're no longer needed.
		$this->delete_menu_before_plugin_activation_options();
	}

	/**
	 * Delete the new menu items options.
	 *
	 * @since  1.0.0
	 */
	public function delete_menu_before_plugin_activation_options() {
		delete_option( 'spmioa_menu_before_plugin_activation' );
		delete_option( 'spmioa_submenu_before_plugin_activation' );
	}

	/**
	 * Set formatted arrays of the newly added menu items as class properties.
	 *
	 * @since  1.0.0
	 */
	public function set_new_menu_item_properties() {
		$this->set_new_menu_items_formatted();
		$this->set_new_submenu_items_formatted();
	}

	/**
	 * Set newly added top-level menu items as a class property.
	 *
	 * @since  1.0.0
	 */
	private function set_new_menu_items_formatted() {

		global $menu;

		if ( ! is_array( $menu ) ) {
			$this->new_menu_items_formatted = array();
			return;
		}

		// Remove any invalid menu items, such as separators.
		$menu = $this->remove_invalid_menu_items( $menu );

		$new_menu_items = $this->get_new_menu_items( $menu );

		foreach ( $new_menu_items as $new_menu_item ) {

			// Store new menu items in class property.
			$this->new_menu_items_formatted[] = array(
				'menu_title'   => $new_menu_item[0],
				'menu_slug'    => $new_menu_item[2],
				'list_item_id' => sanitize_title_with_dashes( $new_menu_item[5] ),
			);
		}
	}

	/**
	 * Get the new menu items that were added during plugin activation.
	 *
	 * @since  1.0.0
	 * @param  array $menu All current menu items.
	 * @return array       The newly added menu items.
	 */
	private function get_new_menu_items( array $menu ) {

		$before_menu_slugs = $this->extract_slugs_from_menu_items( $this->menu_before_activation );

		return array_filter( $menu, function( $menu_item ) use ( $before_menu_slugs ) {

			$menu_item_slug = $menu_item[2];

			return ! in_array( $menu_item_slug, $before_menu_slugs );
		} );
	}

	/**
	 * Extract just the slugs from top-level menu items. Format: [menu position] => 'slug'.
	 *
	 * @since  1.0.0
	 * @param  array $menu The menu items.
	 * @return array       The menu item slugs.
	 */
	private function extract_slugs_from_menu_items( array $menu ) {
		return wp_list_pluck( $menu, 2 );
	}

	/**
	 * Set newly added submenu items as a class property.
	 *
	 * @since  1.0.0
	 */
	private function set_new_submenu_items_formatted() {

		global $submenu;

		if ( ! is_array( $submenu ) ) {
			$this->new_submenu_items_formatted = array();
			return;
		}

		// Loop through each submenu.
		foreach ( $submenu as $parent_slug => $submenu_items ) {

			// If this submenu item's parent is a new menu item, don't show an admin pointer for it.
			if ( $this->is_submenu_items_parent_a_new_menu_item( $parent_slug ) ) {
				continue;
			}

			$new_submenu_items = $this->get_new_submenu_items( $submenu_items, $parent_slug );

			if ( ! $new_submenu_items ) {
				continue;
			}

			// Loop through and add each new submenu item to the formatted array.
			foreach ( $new_submenu_items as $position => $submenu_item ) {

				$this->new_submenu_items_formatted[] = array(
					'menu_title'          => $submenu_item[0],
					'parent_menu_title'   => $this->get_parent_menu_title( $parent_slug ),
					'parent_list_item_id' => $this->get_parent_list_item_id( $parent_slug ),
				);
			}
		}
	}

	/**
	 * Is this submenu item's parent a new top-level menu item?
	 *
	 * @since  1.0.0
	 * @param  string $parent_slug The submenu item's parent's slug.
	 * @return bool                Whether this submenu item's parent is a new top-level menu item.
	 */
	private function is_submenu_items_parent_a_new_menu_item( $parent_slug ) {

		$new_menu_item_slugs = wp_list_pluck( $this->new_menu_items_formatted, 'menu_slug' );

		return in_array( $parent_slug, $new_menu_item_slugs );
	}

	/**
	 * Get newly added items within a submenu.
	 *
	 * @since  1.0.0
	 * @param  array  $submenu_items The submenu's current menu items.
	 * @param  string $parent_slug   The submenu's parent menu item slug.
	 * @return array                 New menu items inside of this submenu.
	 */
	private function get_new_submenu_items( array $submenu_items, $parent_slug ) {

		return array_filter( $submenu_items, function( $submenu_item ) use ( $parent_slug ) {

			$submenu_item_slug = $submenu_item[2];

			return ! $this->was_submenu_item_in_submenu_before_plugin_activation( $parent_slug, $submenu_item_slug ) && ! $this->is_customizer_submenu_item_with_dynamic_slug( $parent_slug, $submenu_item_slug );
		} );
	}

	/**
	 * Was this submenu item in this submenu before plugin activation?
	 *
	 * @since  1.0.0
	 * @param  string $parent_slug       The submenu's parent menu item slug.
	 * @param  string $submenu_item_slug The submenu item's slug.
	 * @return bool                      Whether this submenu item existed before plugin activation.
	 */
	private function was_submenu_item_in_submenu_before_plugin_activation( $parent_slug, $submenu_item_slug ) {

		// If we can't find the submenu item's parent at all (which shouldn't be possible, but just in case).
		if ( ! isset( $this->submenu_before_activation[ $parent_slug ] ) ) {
			return false;
		}

		$submenu_before_activation_slugs = wp_list_pluck( $this->submenu_before_activation[ $parent_slug ], 2 );

		return in_array( $submenu_item_slug, $submenu_before_activation_slugs );
	}

	/**
	 * The Customize and Header submenu items within the Themes top-level menu item have dynamic slugs.
	 * Return true if this menu item is one of those.
	 *
	 * @since  1.0.0
	 * @param  string $submenu_item_slug The submenu item slug.
	 * @return bool                      Whether this is a Customize submenu item with a dynamic slug.
	 */
	private function is_customizer_submenu_item_with_dynamic_slug( $parent_slug, $submenu_item_slug ) {
		return 'themes.php' === $parent_slug && $this->does_string_contain_substring( $submenu_item_slug, 'customize.php?return=%2Fwp-admin%2Fplugins.php' );
	}

	/**
	 * Check if a string contains another string.
	 *
	 * @since  1.0.0
	 * @param  string $string    The string.
	 * @param  string $substring The substring.
	 * @return bool              Whether $string contains $substring.
	 */
	private function does_string_contain_substring( $string, $substring ) {
		return false !== strpos( $string, $substring );
	}

	/**
	 * Get a parent menu item's title.
	 *
	 * @since  1.0.0
	 * @param  string $parent_slug The parent menu item's slug.
	 * @return string              The parent menu item's title, or empty string if none.
	 */
	private function get_parent_menu_title( $parent_slug ) {

		// Get an array of menu items in the format: menu_slug => menu_title
		$menu_titles = wp_list_pluck( $this->menu_before_activation, 0, 2 );

		return isset( $menu_titles[ $parent_slug ] ) ? $this->remove_html_from_menu_title( $menu_titles[ $parent_slug ] ) : '';
	}

	/**
	 * Removes all HTML markup that exists after the menu item title.
	 * Example: Plugins <span class='update-plugins count-0'><span class='plugin-count'>0</span></span>
	 *
	 * @since  1.0.0
	 * @param  string $menu_title The menu title.
	 * @return string             The menu title, with HTML markup possibly removed.
	 */
	private function remove_html_from_menu_title( $menu_title ) {

		if ( ! $this->does_string_contain_substring( $menu_title, '<' ) ) {
			return $menu_title;
		}

		$html_start_position     = strpos( $menu_title, '<' );
		$menu_title_without_html = substr( $menu_title, 0, $html_start_position );

		return trim( $menu_title_without_html );
	}

	/**
	 * Use its parent menu slug to find it's parent (array key [2] => menu_slug). get it's parent's list item ID using: sanitize_title_with_dashes( [5] )
	 *
	 * @since  1.0.0
	 */
	private function get_parent_list_item_id( $parent_slug ) {

		// Get an array of menu items in the format: menu_slug => menu slug for list item ID.
		$menu_list_item_ids = wp_list_pluck( $this->menu_before_activation, 5, 2 );

		return isset( $menu_list_item_ids[ $parent_slug ] ) ? sanitize_title_with_dashes( $menu_list_item_ids[ $parent_slug ] ) : '';
	}

	/**
	 * Enqueue the JS and styles necessary to display admin pointers.
	 *
	 * @since  1.0.0
	 */
	public function maybe_display_admin_pointers() {

		if ( ! $this->requirements_met() ) {
			return;
		}

		// Bail if no new menu items were added.
		if ( ! $this->were_any_new_menu_items_added() ) {
			return;
		}

		// Bail if more than three menu items were added - would result in too many pointers.
		if ( $this->were_more_than_three_menu_items_added() ) {
			return;
		}

		$pointers = $this->get_pointers();

		// Bail if there are no pointers to display.
		if ( ! $pointers ) {
			return;
		}

		// Enqueue pointer styles.
		wp_enqueue_style( 'wp-pointer' );

		// Enqueue pointer JS.
		wp_enqueue_script( 'spmioa_admin_pointers', plugin_dir_url( __FILE__ ) . '/assets/scripts/spmioa-admin-pointers.js', array( 'jquery', 'wp-pointer' ), self::VERSION );

		// Send the admin pointer data to the front end.
		wp_localize_script( 'spmioa_admin_pointers', 'SPMIOANewMenuItemPointers', $pointers );
	}

	/**
	 * Verify that the requirements are met before proceeding to identify new menu items.
	 *
	 * @since  1.0.0
	 * @return bool Whether the requirements are met.
	 */
	private function requirements_met() {
		return $this->is_this_the_plugin_list_screen() && $this->were_any_plugins_activated() && $this->is_correct_wordpress_version();
	}

	/**
	 * Are we curretly on the plugin list screen?
	 *
	 * @since  1.0.0
	 * @return bool Whether this is the plugin list screen.
	 */
	private function is_this_the_plugin_list_screen() {

		global $pagenow;

		return 'plugins.php' === $pagenow;
	}

	/**
	 * Were any plugins activated?
	 *
	 * @since  1.0.0
	 * @return bool Whether any plugins were activated.
	 */
	private function were_any_plugins_activated() {
		return $this->was_a_single_plugin_activated() || $this->were_multiple_plugins_activated();
	}

	/**
	 * Was a single plugin activated?
	 *
	 * @since  1.0.0
	 * @return bool Whether a plugin was activated.
	 */
	private function was_a_single_plugin_activated() {
		return isset( $_GET['activate'] ) && 'true' ===  $_GET['activate'];
	}

	/**
	 * Were multiple plugins activated?
	 *
	 * @since  1.0.0
	 * @return bool Whether two or more plugins were activated.
	 */
	private function were_multiple_plugins_activated() {
		return isset( $_GET['activate-multi'] ) && 'true' ===  $_GET['activate-multi'];
	}

	/**
	 * Admin pointers are only supported in WordPress 3.3+.
	 *
	 * @since  1.0.0
	 * @return bool Whether the version of WordPress is high enough.
	 */
	private function is_correct_wordpress_version() {
		return get_bloginfo( 'version' ) >= '3.3';
	}

	/**
	 * Were any new menu items added?
	 *
	 * @since  1.0.0
	 * @return bool Whether any new menu items have been added.
	 */
	private function were_any_new_menu_items_added() {
		return $this->new_menu_items_formatted || $this->new_submenu_items_formatted;
	}

	/**
	 * Were more than three new new menu items added?
	 *
	 * @since  1.0.0
	 * @return bool Whether more than three new menu items have been added.
	 */
	private function were_more_than_three_menu_items_added() {
		return count( $this->new_menu_items_formatted ) + count( $this->new_submenu_items_formatted ) > 3;
	}

	/**
	 * Get the pointers data to display.
	 *
	 * @since  1.0.0
	 * @return array $pointers The pointers data.
	 */
	private function get_pointers() {

		$pointers = array();

		foreach ( $this->get_all_new_menu_items() as $new_menu_item ) {
			$pointers = $this->add_pointer_to_pointers_array( $pointers, $new_menu_item );
		}

		return $pointers;
	}

	/**
	 * Get all new menu items, both top-level and submenu.
	 *
	 * @since  1.0.0
	 * @return array all new menu items.
	 */
	private function get_all_new_menu_items() {
		return array_merge( $this->new_menu_items_formatted, $this->new_submenu_items_formatted );
	}

	/**
	 * Add a single pointer to the pointers array.
	 *
	 * @since  1.0.0
	 * @param  array $pointers      The array of pointers to display.
	 * @param  array $new_menu_item The newly added menu item's data.
	 * @return array $pointers      The array of pointers to display, with $new_menu_item's data maybe added.
	 */
	private function add_pointer_to_pointers_array( $pointers, $new_menu_item ) {

		$target = $this->get_menu_item_list_item_id_to_target( $new_menu_item );

		// If unable to get the target for some reason, don't add to pointers array.
		if ( ! $target ) {
			return $pointers;
		}

		$pointers[] = array(
			'target'  => '#' . $target,
			'options' => array(
				'content'  => sprintf( '<h3>%s</h3><p>%s</p>',
					__( 'New Menu Item Added', 'plugindomain' ),
					$this->get_pointer_text( $new_menu_item )
				),
				'position' => array( 'edge' => 'left', 'align' => 'middle' ),
			),
		);

		return $pointers;
	}

	/**
	 * Get the paragraph text for a pointer.
	 *
	 * @since  1.0.0
	 * @param  array  $new_menu_item The new menu item.
	 * @return string                The pointer text.
	 */
	private function get_pointer_text( $new_menu_item ) {

		if ( isset( $new_menu_item['parent_menu_title'] ) ) {
			return $new_menu_item['parent_menu_title'] . ' &rarr; ' . $new_menu_item['menu_title'];
		}

		return $new_menu_item['menu_title'];
	}

	/**
	 * Get the list item id to use as the DOM element to target using JS.
	 *
	 * @since  1.0.0
	 * @param  array $new_menu_item The newly added menu item's data.
	 * @return string               The list item id, or empty string if not set.
	 */
	private function get_menu_item_list_item_id_to_target( $new_menu_item ) {

		if ( isset( $new_menu_item['list_item_id'] ) ) {
			return $new_menu_item['list_item_id'];
		}

		if ( isset( $new_menu_item['parent_list_item_id'] ) ) {
			return $new_menu_item['parent_list_item_id'];
		}

		return '';
	}

	/**
	 * Display an admin notice if no new menu items were added, or if more than three were added.
	 *
	 * @since  1.0.0
	 */
	public function maybe_display_admin_notice() {

		if ( ! $this->requirements_met() ) {
			return;
		}

		if ( ! $this->were_any_new_menu_items_added() ) {
			$this->display_admin_notice( __( 'No new plugin menu items were added.', 'show-plugin-menu-items' ) );
			return;
		}

		if ( $this->were_more_than_three_menu_items_added() ) {
			$this->display_admin_notice( __( 'Many new plugin menu items were added.', 'show-plugin-menu-items' ) );
		}
	}

	/**
	 * Display an admin notice.
	 *
	 * @since  1.0.0
	 * @param  string $notice_message The admin notice message.
	 */
	private function display_admin_notice( $notice_message ) {
		?>
		<div class="updated notice is-dismissible">
			<p><?php echo esc_html( $notice_message ); ?></p>
		</div>
		<?php
	}
}

// Kick it off.
add_action( 'plugins_loaded', array( SPMIOA_Show_Plugin_Menu_Items::get_instance(), 'hooks' ) );

// Register deactivation hook.
register_deactivation_hook( __FILE__, array( SPMIOA_Show_Plugin_Menu_Items::get_instance(), 'delete_menu_before_plugin_activation_options' ) );
