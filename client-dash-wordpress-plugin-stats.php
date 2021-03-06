<?php
/*
Plugin Name: Client Dash - WordPress Plugin Stats
Description: Creates a widget which is useable on the dashboard for displaying stats for a specified plugin that is hosted on wordpress. It also creates an optional tab on the Reports page for rendering information about plugins hosted on wordpress.org.
Version: 0.1
Author: Kyle Maurer
Author URI: http://clientdash.io
*/

if ( ! function_exists( 'client_dash_wordpress.org_plugin_stats_wrapper' ) ) {

	/**
	 * The function to launch our plugin.
	 *
	 * This entire class is wrapped in this function because we have to ensure that Client Dash has been loaded before our
	 * extension.
	 */
	function client_dash_wordpress_plugin_stats_wrapper() {
		if ( ! class_exists( 'ClientDash' ) ) {

			// Change me! Change me to the name of the notice function at the bottom
			add_action( 'admin_notices', '_client_dash_wordpress_plugin_stats_notice' );

			return;
		}

		class ClientDashWordPressPluginStats extends ClientDash {

			/**
			 * Your unique ID.
			 *
			 * This will be prefixed on many things throughout the plugin.
			 */
			public static $ID = 'cdwpps';

			/**
			 * This is the page that you want your new tab to reside in.
			 */
			private static $page = 'Reports';

			/**
			 * Your tab name.
			 *
			 * This is the name of the tab that your plugin's content section will reside in.
			 */
			private static $tab = 'Plugins';


			/**
			 * This is the settings tab name.
			 *
			 * All of your plugin settings will reside here. This may also be the name of an existing tab.
			 */
			public static $settings_tab = 'Display';

			/**
			 * This is the section name of your boilerplate.
			 *
			 * This will be the display name of the content section that this plugin's content resides in. If there is only
			 * one content section within the tab, the name will not show.
			 */
			private static $section_name = 'Plugin Stats';

			/**
			 * This is the current version of your plugin. Keep it up to do date!
			 */
			public static $extension_version = '0.1';

			/**
			 * This is the path to the plugin.
			 *
			 * Private.
			 */
			public $_path;

			/**
			 * This is the url to the plugin.
			 *
			 * Private.
			 */
			public $_url;

			/**
			 * This constructor function sets up what happens when the plugin is activated. It is where you'll place all your
			 * actions, filters and other setup components.
			 */
			public function __construct() {

				// Register our styles
				add_action( 'admin_init', array( $this, 'register_styles' ) );

				// Add our styles conditionally
				add_action( 'admin_enqueue_scripts', array( $this, 'add_styles' ) );

				// Add our new content section
				$this->add_content_section(
					array(
						'name'     => self::$section_name,
						'tab'      => self::$tab,
						'page'     => self::$page,
						'callback' => array( $this, 'section_output' )
					)
				);

				// Set the plugin path
				$this->_path = plugin_dir_path( __FILE__ );

				// Set the plugin url
				$this->_url = plugins_url( '', __FILE__ );
			}

			/**
			 * Register our styles.
			 */
			public function register_styles() {

				wp_register_style(
					self::$ID . '-style',
					$this->_url . '/style.css',
					null,
					self::$extension_version
				);
			}

			/**
			 * Add our styles.
			 *
			 * If you want the styles to show up on the entire back-end, simply remove all but:
			 * wp_enqueue_style( "$this->$ID-style" );
			 */
			public function add_styles() {

				$page_ID         = self::translate_name_to_id( self::$page );
				$tab_ID          = self::translate_name_to_id( self::$tab );
				$settings_tab_ID = self::translate_name_to_id( self::$settings_tab );

				// Only add style if on extension tab or on extension settings tab
				if ( self::is_cd_page( $page_ID, $tab_ID ) || self::is_cd_page( 'cd_settings', $settings_tab_ID ) ) {
					wp_enqueue_style( self::$ID . '-style' );
				}
			}

			public function get_plugins() {

				$plugins = array();
				$plugin_slugs = array( 'betterify', 'client-dash', 'display-post-meta' );

				foreach ( $plugin_slugs as $plugin ) {

					$args        = (object) array( 'slug' => $plugin );
					$request     = array(
						'action'  => 'plugin_information',
						'timeout' => 15,
						'request' => serialize( $args )
					);
					$url         = 'http://api.wordpress.org/plugins/info/1.0/';
					$response    = wp_remote_post( $url, array( 'body' => $request ) );
					$plugin_info = unserialize( $response['body'] );
					$plugins[]   = $plugin_info;

				}

				return $plugins;
			}

			/**
			 * Our section output.
			 *
			 * This is where all of the content section content goes! Add anything you like to this function.
			 */
			public function section_output() {
				//print_r($this->get_plugins());
				?>

				<h2>Plugin Stats</h2>

				<!--Actual list table-->
				<table class="wp-list-table widefat fixed posts">
				<!--Table header row-->
				<thead>
				<tr>
					<th scope='col' id='title' class='manage-column column-title'>Title</th>
					<th scope='col' id='author' class='manage-column column-author'>Author</th>
					<th scope='col' id='downloads' class='manage-column column-downloads'>Downloads</th>
					<th scope='col' id='categories' class='manage-column column-ratings'>Ratings</th>
					<th scope='col' id='tags' class='manage-column column-score'>Score</th>
				</tr>
				</thead>

				<tfoot>
				<!--Table footer-->
				<tr>
					<th scope='col' class='manage-column column-title'>Title</th>
					<th scope='col' class='manage-column column-author'>Author</th>
					<th scope='col' class='manage-column column-downloads'>Downloads</th>
					<th scope='col' class='manage-column column-ratings'>Ratings</th>
					<th scope='col' class='manage-column column-score'>Score</th>
				</tr>
				</tfoot>

				<tbody id="the-list">
				<?php if ( $this->get_plugins() != null ) {
					foreach ( $this->get_plugins() as $plugin ) {
						?>
						<tr class="post-0 type-post status-publish format-standard hentry category-uncategorized alternate iedit author-self level-0">
							<td class="post-title page-title column-title">
								<strong><?php echo $plugin->name; ?></strong>
							</td>
							<td class="downloads column-author">
								<?php echo $plugin->author; ?>
							</td>
							<td class="downloads column-downloads">
								<?php echo $plugin->downloaded; ?>
							</td>
							<td class="ratings column-ratings">
								<?php echo $plugin->num_ratings; ?>
							</td>
							<td class="score column-score">
								<?php echo $plugin->rating; ?>
							</td>
						</tr>
					<?php }
				} else {
					echo 'No results';
				} ?>
				</tbody>
				</table><?php
			}
		}

		// Instantiate the class
		$ClientDashWordPressPluginStats = new ClientDashWordPressPluginStats();

		// Include the file for your plugin settings.
		include_once( $ClientDashWordPressPluginStats->_path . 'inc/settings.php' );

		// Include the file for your plugin widget.
		include_once( $ClientDashWordPressPluginStats->_path . 'inc/widgets.php' );
	}

	add_action( 'plugins_loaded', 'client_dash_wordpress_plugin_stats_wrapper' );
}

if ( ! function_exists( '_client_dash_wordpress_plugin_stats_notice' ) ) {
	/**
	 * Notices for if CD is not active.
	 */
	function _client_dash_wordpress_plugin_stats_notice() {

		?>
		<div class="error">
			<p>You have activated a plugin that requires <a href="http://w.org/plugins/client-dash">Client Dash</a>
				version 1.6 or greater.
				Please install and activate <strong>Client Dash</strong> to continue using.</p>
		</div>
	<?php
	}
}