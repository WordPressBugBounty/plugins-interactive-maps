<?php 
/*
Plugin Name: SimpleMaps
Plugin URI: https://simplemaps.com/docs/wordpress-install
Description:  Easily add a JavaScript-powered HTML5 interactive map to your WordPress site.<br /> Free World Continent Map. Also, premium commercial World, US, Canada, County, North America, Europe, UK maps available.
Author:  Simplemaps.com
Author URI: https://simplemaps.com
Text Domain: interactive-maps
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Version: 0.99.2
Tested up to: 6.8.1
*/

add_action( 'admin_menu', 'my_plugin_menu' );
function my_plugin_menu() {
	add_options_page( 'SimpleMaps', 'SimpleMaps', 'manage_options', 'simplemaps', 'my_plugin_options' );
}

function my_plugin_options() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'interactive-maps' ) );
	}

	// Check for POST and verify nonce
	if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
		$nonce = isset( $_POST['simplemaps_upload_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['simplemaps_upload_nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'simplemaps_upload_action' ) ) {
			wp_die( esc_html__( 'Security check failed. Please refresh the page and try again.', 'interactive-maps' ) );
		}
	}

	/* Start of Options */
	add_action( 'admin_init', 'sm_register_settings' );

	function sm_register_settings() {
		register_setting( 
			'sm_settings_group',      // Option group
			'sm_settings',            // Option name
			array(
				'sanitize_callback' => 'simplemaps_sanitize_settings',
				'default' => array()
			)
		);
	}

	/**
	 * Sanitize settings before saving to database
	 */
	function simplemaps_sanitize_settings( $input ) {
		$sanitized = array();
		
		if ( isset( $input ) && is_array( $input ) ) {
			foreach ( $input as $key => $value ) {
				$sanitized[$key] = sanitize_text_field( $value );
			}
		}
		
		return $sanitized;
	}

	$mapdata_url = get_option( 'simplemaps_mapdata_url' );
	$mapfile_url = get_option( 'simplemaps_mapfile_url' );

	$filename = ( isset( $_FILES['file'] ) && isset( $_FILES['file']['name'] ) ) ? sanitize_file_name( $_FILES['file']['name'] ) : '';
	$file_exists = $filename ? $filename : 0;
	$tmp_name = isset( $_FILES['file']['tmp_name'] ) ? sanitize_text_field( $_FILES['file']['tmp_name'] ) : '';
	if ( $file_exists && $tmp_name ) {
		$upload_mapfile = wp_upload_bits( $filename, null, file_get_contents( $tmp_name ) );
		if ( isset( $mapfile_url ) ) {
			update_option( 'simplemaps_mapfile_url', $upload_mapfile['url'] );
		} else {
			add_option( 'simplemaps_mapfile_url', $upload_mapfile['url'], '', 'yes' );
		}
		$mapfile_url = get_option( 'simplemaps_mapfile_url' );
	}

	$data_filename = ( isset( $_FILES['data'] ) && isset( $_FILES['data']['name'] ) ) ? sanitize_file_name( $_FILES['data']['name'] ) : '';
	$data_exists = $data_filename ? $data_filename : 0;
	$data_tmp_name = isset( $_FILES['data']['tmp_name'] ) ? sanitize_text_field( $_FILES['data']['tmp_name'] ) : '';
	if ( $data_exists && $data_tmp_name ) {
		$upload_mapdata = wp_upload_bits( $data_filename, null, file_get_contents( $data_tmp_name ) );
		if ( isset( $mapdata_url ) ) {
			update_option( 'simplemaps_mapdata_url', $upload_mapdata['url'] );
		} else {
			add_option( 'simplemaps_mapdata_url', $upload_mapdata['url'], '', 'yes' );
		}
		$mapdata_url = get_option( 'simplemaps_mapdata_url' );
	}

	if ( isset( $_POST['replace'] ) ) {
		if ( $_POST['replace'] == 'data' ) {
			$replace_data = true;
		} else {
			$replace_data = false;
		}

		if ( $_POST['replace'] == 'file' ) {
			$replace_file = true;
		} else {
			$replace_file = false;
		}
	} else {
		$replace_data = false;
		$replace_file = false;
	}

	// Start of page HTML
	echo '<div id="icon-edit-pages" class="icon32"></div><div class="wrap">';
	echo '<h1>SimpleMaps Interactive Maps Plugin</h1>';
	echo '<h3>Provided by: <a href="https://simplemaps.com">Simplemaps.com</a></h3>';

	echo '<ol>
	<li>Download the <strong>free</strong> World Continent map (<a href="http://simplemaps.com/resources/free-continent-map">here</a>). Also available, <strong>paid</strong> <a href="http://simplemaps.com/us">US</a>, <a href="http://simplemaps.com/world">World</a>, <a href="http://simplemaps.com/europe">Europe</a>, <a href="http://simplemaps.com/canada">Canada</a>, <a href="http://simplemaps.com/north-america">North America</a>, <a href="http://simplemaps.com/county">County</a>, <a href="http://simplemaps.com/uk">UK</a> maps.</li>
	<li>Unzip the map and open the folder.</li>
	<li><a href="http://simplemaps.com/docs/">Customize</a> the map using our online tool or by editing the mapdata.js file and refreshing the test.html file. <strong>For help</strong> see our <a href="http://simplemaps.com/docs">Documentation</a> or <a href="http://simplemaps.com/contact">contact us</a>.</li>
	<li>Upload the files for your map <span style="color: red">(one at a time)</span>:</li>';

	if ( isset( $mapdata_url ) && $mapdata_url != null ) {
		$mapdata_exists = true;
	} else {
		$mapdata_exists = false;
	}
	if ( isset( $mapfile_url ) && $mapfile_url != null ) {
		$mapfile_exists = true;
	} else {
		$mapfile_exists = false;
	}

	// Form functions
	function file_form() {
		?>
		<form action="" method="post" enctype="multipart/form-data">
			<b>Upload the map file</b> <i>(continentmap.js/usmap.js/worldmap.js etc)</i><br />
			<input type="file" name="file">
			<?php wp_nonce_field( 'simplemaps_upload_action', 'simplemaps_upload_nonce' ); ?>
			<input type="submit" class="button" value="Submit">
		</form><br />
		<?php
	}

	function data_form() {
		?>
		<form action="" method="post" enctype="multipart/form-data">
			<b>Upload the data file</b> <i>(mapdata.js)</i><br />
			<input type="file" name="data">
			<?php wp_nonce_field( 'simplemaps_upload_action', 'simplemaps_upload_nonce' ); ?>
			<input type="submit" class="button" value="Submit">
		</form><br />
		<?php
	}

	function replace_form( $value ) {
		?>
		<form action="" method="post" enctype="multipart/form-data">
			<input type="hidden" name="replace" value="<?php echo esc_attr( $value ); ?>">
			<?php wp_nonce_field( 'simplemaps_upload_action', 'simplemaps_upload_nonce' ); ?>
			<input type="submit" class="button-secondary" value="Replace this File">
		</form><br />
		<?php
	}

	// Show forms or uploaded file info
	if ( ! $mapdata_exists || $replace_data ) {
		data_form();
	}
	if ( $mapdata_exists && ! $replace_data ) {
		echo 'You have uploaded your data file to: ' . esc_html( $mapdata_url );
		replace_form( 'data' );
	}

	if ( ! $mapfile_exists || $replace_file ) {
		file_form();
	}
	if ( $mapfile_exists && ! $replace_file ) {
		echo 'You have uploaded your map file to: ' . esc_html( $mapfile_url );
		replace_form( 'file' );
	}

	echo '<li>Paste <span style="color: red;">[simplemaps]</span> into the post/page where you want the map to be located.</li>
	<li>Publish/Preview your post and your map should be visible and look something like:<br />';
	
	// Display the screenshot image
	$image_path = plugin_dir_path( __FILE__ ) . 'includes/screenshot.png';
	if (file_exists($image_path)) {
		$image_url = plugins_url( '/includes/screenshot.png', __FILE__ );
		
		// Use a filter to provide a custom URL for the image
		add_filter('wp_get_attachment_image_src', function($image, $attachment_id, $size, $icon) use ($image_url) {
			if ($attachment_id === 0) {
				return array($image_url, 600, 400, false);
			}
			return $image;
		}, 10, 4);
		
		// Use wp_get_attachment_image with ID 0
		echo wp_get_attachment_image(0, 'medium', false, array(
			'alt' => __('Map Preview', 'interactive-maps'),
			'class' => 'simplemaps-screenshot',
			'style' => 'max-width: 100%; height: auto;'
		));
	}
	
	echo '</li>
	</ol></div>';

	/* End of options */
}

function insert_code( $atts ) {
	$mapdata_url = get_option( 'simplemaps_mapdata_url' );
	$mapfile_url = get_option( 'simplemaps_mapfile_url' );
	
	// Get file paths from URLs to check modification times
	$upload_dir = wp_upload_dir();
	$upload_base_url = $upload_dir['baseurl'];
	$upload_base_path = $upload_dir['basedir'];
	
	// Convert URLs to local file paths
	$mapdata_path = str_replace($upload_base_url, $upload_base_path, $mapdata_url);
	$mapfile_path = str_replace($upload_base_url, $upload_base_path, $mapfile_url);
	
	// Use file modification time as version parameter to prevent caching
	$mapdata_version = file_exists($mapdata_path) ? filemtime($mapdata_path) : time();
	$mapfile_version = file_exists($mapfile_path) ? filemtime($mapfile_path) : time();
	
	// Enqueue the scripts with dynamic version numbers based on file modification time
	wp_enqueue_script( 'simplemaps-mapdata', $mapdata_url, array(), $mapdata_version, true );
	wp_enqueue_script( 'simplemaps-mapfile', $mapfile_url, array('simplemaps-mapdata'), $mapfile_version, true );
	
	return '<div id="map"></div>';
}

add_shortcode( 'simplemap', 'insert_code' );
add_shortcode( 'simplemaps', 'insert_code' );
?>