<?php
/*
Plugin Name: Safe Attachment Names
Plugin URI: http://www.nuagelab.com/wordpress-plugins/safe-attachment-names
Description: Automatically detect and change the name of attachments containing special characters such as accented letters.
Author: NuageLab <wordpress-plugins@nuagelab.com>
Version: 0.0.1
License: GPLv2 or later
Author URI: http://www.nuagelab.com/wordpress-plugins
*/

// --

/**
 * Safe Attachment Name class
 *
 * @author	Tommy Lacroix <tlacroix@nuagelab.com>
 */
class safe_attachment_names {
	private static $_instance = null;

	/**
	 * Bootstrap
	 *
	 * @author	Tommy Lacroix <tlacroix@nuagelab.com>
	 * @access	public
	 */
	public static function boot()
	{
		if (self::$_instance === null) {
			self::$_instance = new safe_attachment_names();
			self::$_instance->setup();
			return true;
		}
		return false;
	} // boot()


	/**
	 * Setup plugin
	 *
	 * @author	Tommy Lacroix <tlacroix@nuagelab.com>
	 * @access	public
	 */
	public function setup()
	{
		global $current_blog;

		// Add admin menu
		add_action('admin_menu', array(&$this, 'add_admin_menu'));

		// Set wp_handle_upload_prefilter
		add_filter('wp_handle_upload_prefilter', array($this, 'upload_prefilter'));

		// Load text domain
		load_plugin_textdomain('safe-attachment-name', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
	} // setup()


	/**
	 * Add admin menu action; added by setup()
	 *
	 * @author	Tommy Lacroix <tlacroix@nuagelab.com>
	 * @access	public
	 */
	public function add_admin_menu()
	{
		// This part is not ready yet
		//add_management_page(__("Sanitize attachment names",'safe-attachment-name'), __("Sanitize attachment names",'safe-attachment-name'), 'update_core', basename(__FILE__), array(&$this, 'admin_page'));
	} // add_admin_menu()


	/**
	 * Admin page action; added by add_admin_menu()
	 *
	 * @author	Tommy Lacroix <tlacroix@nuagelab.com>
	 * @access	public
	 */
	public function admin_page()
	{
		if (isset($_POST['action'])) {
			if (wp_verify_nonce($_POST['nonce'],$_POST['action'])) {
				$parts = explode('+',$_POST['action']);
				switch ($parts[0]) {
					case 'sanitize':
						if (!$_POST['accept-terms']) {
							$error_terms = true;
						} else {
							return $this->do_change();
						}
						break;
				}
			}
		}

		if (!isset($error_terms)) $error_terms = false;

		echo '<div class="wrap">';

		echo '<div id="icon-tools" class="icon32"><br></div>';
		echo '<h2>'.__('Sanitize Attachment Names','safe-attachment-name').'</h2>';
		echo '<form method="post">';

		$action = 'sanitize+'.uniqid();
		wp_nonce_field($action,'nonce');

		echo '<input type="hidden" name="action" value="'.$action.'" />';

		echo '<table class="form-table">';
		echo '<tbody>';

		echo '<tr valign="top">';
		echo '<td colspan="2"><input type="checkbox" name="accept-terms" id="accept-terms" value="1" /> <label for="accept-terms"'.($error_terms?' style="color:red;font-weight:bold;"':'').'>'.__('I have backed up my database and will assume the responsability of any data loss or corruption.','safe-attachment-name').'</label></td>';
		echo '</tr>';

		echo '</tbody></table>';

		echo '<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="'.esc_html(__('Sanitize','safe-attachment-name')).'"></p>';

		echo '</form>';

		echo '</div>';
	} // admin_page()


	/**
	 * Change domain. This is where the magic happens.
	 * Called by admin_page() upon form submission.
	 *
	 * @author	Tommy Lacroix <tlacroix@nuagelab.com>
	 * @access	private
	 */
	private function do_change()
	{
		global $wpdb;

		@set_time_limit(0);

		echo '<div class="wrap">';

		echo '<div id="icon-tools" class="icon32"><br></div>';
		echo '<h2>Sanitizing attachment names</h2>';
		echo '<pre>';

		mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
		mysql_select_db(DB_NAME);
		mysql_query('SET NAMES '.DB_CHARSET);
		if (function_exists('mysql_set_charset')) mysql_set_charset(DB_CHARSET);

		$upload_dir = wp_upload_dir();
		
		$ret = mysql_query('SELECT * FROM '.$wpdb->prefix.'posts WHERE post_type="attachment";');
		while ($row = mysql_fetch_assoc($ret)) {
			$ret2 = mysql_query('SELECT * FROM '.$wpdb->prefix.'postmeta WHERE post_id='.intval($row['ID']).';');
			$metas = array();
			while ($row2 = mysql_fetch_assoc($ret2)) {
				$metas[$row2['meta_key']] = $row2;
			}
			
			// Check if filename is problematic
			$fn1 = $metas['_wp_attached_file']['meta_value'];
			$fn2 = htmlentities($fn1, null, 'UTF-8');

			$fns = array($fn1, utf8_encode($fn1), utf8_decode($fn1));

			if ($fn1 != $fn2) {
				$error = false;
				$fn4 = $this->sanitizeFilename($fn1);
				
				echo $fn1 . ' -> '.$fn4;

				$fnx = false;
				foreach ($fns as $x) {
					if (file_exists($upload_dir['basedir'].'/'.$x)) {
						$fnx = $x;
						break;
					}
				}
				if (!$fnx) {
					_e(': error, cannot find source file.');
					echo PHP_EOL;
					$error = true;
				} else {
					_e( ': OK.' );
					echo PHP_EOL;
				}

				// Rename file
				$renames = array();
				$renames[$upload_dir['basedir'].'/'.$fnx] = $upload_dir['basedir'].'/'.$fn4;
				
				// Update _wp_attachment_metadata
				$info = unserialize($metas['_wp_attachment_metadata']['meta_value']);
				$info['file'] = $fn4;

				if ($info['sizes']) {
					foreach ( $info['sizes'] as &$size ) {
						$sf1 = dirname( $fn1 ) . '/' . $size['file'];
						$sf4 = $this->sanitizeFilename( $sf1 );
						if ( $sf1 != $sf4 ) {
							echo $sf1 . ' -> ' . $sf4;

							$fns = array( $sf1, utf8_encode( $sf1 ), utf8_decode( $sf1 ) );
							$sfx = false;
							foreach ( $fns as $x ) {
								if ( file_exists( $upload_dir['basedir'] . '/' . $x ) ) {
									$sfx = $x;
									break;
								}
							}
							if ( ! $sfx ) {
								_e( ': error, cannot find source file.' );
								echo PHP_EOL;
								$error = true;
							} else {
								_e( ': OK.' );
								echo PHP_EOL;
							}

							// Rename file
							$renames[ $upload_dir['basedir'] . '/' . $sfx ] = $upload_dir['basedir'] . '/' . $sf4;

							$size['file'] = basename( $sf4 );
						}
					}
				}

				if (!$error) {
					// Rename all
					foreach ( $renames as $f1 => $f2 ) {
						rename( $f1, $f2 );
					}

					// Update _wp_attachment
					mysql_query( 'UPDATE ' . $wpdb->prefix . 'postmeta SET meta_value="' . mysql_real_escape_string( $fn4 ) . '" WHERE meta_id=' . $metas['_wp_attached_file']['meta_id'] . ';' );

					mysql_query( 'UPDATE ' . $wpdb->prefix . 'postmeta SET meta_value="' . mysql_real_escape_string( serialize( $info ) ) . '" WHERE meta_id=' . $metas['_wp_attachment_metadata']['meta_id'] . ';' );

					// Update guid
					mysql_query( 'UPDATE ' . $wpdb->prefix . 'posts SET guid="' . mysql_real_escape_string( $upload_dir['baseurl'] . '/' . $fn4 ) . '" WHERE post_id=' . $row['ID'] . ';' );
				}
			} else {
				//echo $fn1 . ' [ok]'.PHP_EOL;
			}
		}

		echo '</pre>';
		echo '<hr>';
		echo '<form method="post"><input type="submit" value="'.esc_html(__('Back','safe-attachment-name')).'" />';
	} // do_change()


	/**
	 * Prefilter uploaded files to remove accents
	 *
	 * array    $file
	 * return   array
	 */
	public function upload_prefilter($file)
	{
		$file['name'] = $this->sanitizeFilename($file['name']);
		return $file;
	} // upload_prefilter()


	/**
	 * Sanitize a file name
	 *
	 * @param   string    $fn1    File name
	 * @return  string
	 */
	private function sanitizeFilename($fn1) {
		$upload_dir = wp_upload_dir();
		
		if (!file_exists($upload_dir['basedir'].'/'.$fn1)) {
			//die('not found: '.$upload_dir['basedir'].'/'.$fn1);
		}
		
		$fn3 = $this->stripAccents($fn1);
		$parts = explode('/', $fn3);
		foreach ($parts as &$part) $part = preg_replace('/[^a-zA-Z0-9\-_\.]+/','_',$part);
		
		$fn4_old = $fn4 = implode('/', $parts);
		$i = 1;
		while (file_exists($upload_dir['basedir'].'/'.$fn4)) {
			$pi = pathinfo($fn4_old);
			$fn4 = $pi['dirname'].'/'.$pi['filename'].'-'.$i.'.'.$pi['extension'];
			$i++;
		}
		
		return $fn4;
	}

	/**
	 * Strip accents from string and replace by the unaccented letter
	 *
	 * @param $stripAccents
	 * @return string
	 */
	private function stripAccents($stripAccents){
		return utf8_encode(strtr(utf8_decode($stripAccents),utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'),'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY'));
	} // stripAccents()

} // safe_attachment_names class


// Initialize
safe_attachment_names::boot();
