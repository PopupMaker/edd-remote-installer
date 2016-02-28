<?php

/**
 * Class EDD_RI_Client_Admin
 */
class EDD_RI_Client_Admin {

	/**
	 * @var EDD_RI_Client
	 */
	private $edd_ri;

	/**
	 * @var string
	 */
	private static $options_page;


	/**
	 * EDD_RI_Client_Admin constructor.
	 *
	 * @param EDD_RI_Client $client
	 */
	public function __construct( EDD_RI_Client $client ) {

		$this->edd_ri = $client;

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add options page.
	 */
	function admin_menu() {

		self::$options_page = add_options_page(
			'EDD Remote Installer Demo',
			'EDD Remote Installer Demo',
			'install_plugins',
			'edd-ri-demo',
			array( $this, 'settings_page' )
		);
	}

	/**
	 * @param $hook
	 */
	public function enqueue_scripts( $hook ) {

		if ( self::$options_page !== $hook ) {
			return;
		}

		wp_register_script( 'edd_ri_script', EDD_RI_PLUGIN_URL . 'assets/js/edd-ri.js', array( 'jquery' ) );
		wp_enqueue_script( 'edd_ri_script' );

		wp_register_style( 'edd_ri_css', EDD_RI_PLUGIN_URL . 'assets/css/style.css', false );
		wp_enqueue_style( 'edd_ri_css' );

		add_thickbox();
	}

	/**
	 * @return array|mixed|object|void
	 */
	private function get_downloads() {

		$domain = parse_url( $this->edd_ri->api_url );
		$domain = str_replace( '.', '', $domain[ 'host' ] );
		$domain = sanitize_key( $domain );

		// Get the cache from the transient.
		$cache = get_transient( 'remote_installer_' . $domain );

		// If the cache does not exist, get the json and save it as a transient.
		if ( ! $cache ) {

			$api_params = array( 'edd_action' => 'get_downloads' );
			$request    = wp_remote_post( esc_url_raw( add_query_arg( $api_params, $this->edd_ri->api_url ) ) );

			if ( is_wp_error( $request ) ) {
				return null;
			}

			$request = json_decode( wp_remote_retrieve_body( $request ), true );

			set_transient( 'remote_installer_' . $domain, $request, HOUR_IN_SECONDS );

			$cache = $request;
		}

		return $cache;
	}

	/**
	 *
	 */
	public function settings_page() { ?>

		<div class="wrap metabox-holder">
			<h2><?php _e( 'EDD Remote Installer', 'edd_ri' ); ?></h2>

			<?php
			$downloads = $this->get_downloads();

			$i = 0;

			$plugins = $downloads[ 'plugins' ];
			$themes  = $downloads[ 'themes' ];
			?>

			<?php foreach ( $plugins as $download ) : ?>

				<?php if ( ! $download[ 'bundle' ] ) : ?>

					<?php
					$data_free   = (int) $download[ 'free' ];
					$disabled    = $this->edd_ri->is_plugin_installed( $download[ 'title' ] ) ? ' disabled="disabled" ' : '';
					$button_text = $this->edd_ri->is_plugin_installed( $download[ 'title' ] ) ? __( 'Installed', 'edd_ri' ) : __( 'Install', 'edd_ri' );

					$i = $i == 3 ? 0 : $i;
					?>

					<?php if ( $i == 0 ) : ?>
						<div style="clear:both; display: block; float: none;"></div>
					<?php endif; ?>

					<div id="<?php echo sanitize_title( $download[ 'title' ] ); ?>" class="edd-ri-item postbox plugin">
						<h3 class="hndle"><span><?php echo $download[ 'title' ]; ?></span></h3>
						<div class="inside">
							<div class="main">
								<?php if ( '' != $download[ 'thumbnail' ] ) : ?>
									<img class="edd-ri-item-image" src="<?php echo $download[ 'thumbnail' ][ 0 ]; ?>">
								<?php endif; ?>

								<?php if ( '' != $download[ 'description' ] ) : ?>
									<p class="edd-ri-item-description"><?php echo $download[ 'description' ]; ?></p>
								<?php endif; ?>

								<p class="edd-ri-actions">
									<span class="spinner"></span>
									<button class="button button-primary"
									        data-free="<?php echo $data_free; ?>"<?php echo $disabled; ?>
									        data-edd-ri="<?php echo $download[ 'title' ]; ?>"><?php echo $button_text; ?></button>
									<a class="button" target="_blank"
									   href="<?php echo esc_url( add_query_arg( array( 'p' => $download[ 'id' ] ), $this->edd_ri->api_url ) ); ?>"><?php _e( 'Details', 'edd_ri' ); ?></a>
								</p>
							</div>
						</div>
					</div>

					<?php $i ++; ?>

				<?php endif; ?>

			<?php endforeach; ?>

			<div id="edd_ri_license_thickbox" style="display:none;">
				<h3><?php _e( 'Enter your license', 'edd_ri' ); ?></h3>
				<form action="" method="post" id="edd_ri_license_form">
					<input style="width: 100%" type="text" id="edd_ri_license"/>
					<button style="margin-top: 10px" type="submit"
					        class="button button-primary"><?php esc_attr__( 'Submit', 'edd_ri' ); ?></button>
				</form>
			</div>
			<div class="message-popup" id="MessagePopup" style="display:none;"></div>
		</div>
		<?php
	}
}
