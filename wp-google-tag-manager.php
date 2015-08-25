<?php
/**
 *
 * Plugin Name: WP Google Tag Manager
 * Plugin URI: http://wordpress.org/extend/plugins/wp-google-tag-manager/
 * Description: Include code snippets from Google Tag Manager (google.com/tagmanager)
 * Version: 1.1
 * Author: conlabzgmbh
 * Author URI: http://conlabz.de
 * License: GPLv2 or later
 *
 * Installation:
 *
 * 1. Download and unzip the latest release zip file.
 * 2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
 * 3. Upload the entire plugin directory to your `/wp-content/plugins/` directory.
 * 4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
 *
 * Usage:
 *
 * 1. Go to Settings -> WP Google Tag Manager
 * 2. Put your Google Tag Manager Container ID in the input field
 * 3. Save
 *
 */

/*  Copyright 2012  conlabzgmbh  (email : "WP Google Tag Manager" conlabzgmbh wp-extension@conlabz.de)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


class WpGoogleTagManager {

  private $container_id;
  private $called = false;

  /**
   * @return void
   *
   * register settings subpage<br>
   * register footer output
   */
  public function WpGoogleTagManager() {
    load_plugin_textdomain('wp_google_tag_manager', false, basename( dirname( __FILE__ ) ) . '/languages');
    $this->container_id = get_option('wp_google_tag_manager_container_id','');
    add_action('admin_init', array(&$this,"admin_init"));
    add_action('admin_menu', array(&$this,"admin_menu"));
    add_action( 'wp_footer', array(&$this,"output_container") );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

  /**
   * @param $message
   * @param bool $errormsg
   * @return void
   *
   * Shows Errormessage in Backend
   */
  private function show_message($message, $errormsg = false)
  {
    if ($errormsg) {
      echo '<div id="message" class="error">';
    }
    else {
      echo '<div id="message" class="updated fade">';
    }
    echo "<p><strong>$message</strong><a style=\"float:right;\" href=\"?wp_google_tag_manager_ignore_notice=0\">".__('Dismiss','wp_google_tag_manager')."</a></p></div>";
  }

  /**
   * @return void
   *
   * Calls show_message with specific warning, missing id
   */
  public function show_missing_id_warning()
  {
    global $current_user ;
    $user_id = $current_user->ID;
    /* Check that the user hasn't already clicked to ignore the message */
    if ( ! get_user_meta($user_id, 'wp_google_tag_manager_ignore_notice') &&
        current_user_can( 'manage_options' )
    ) {
      $this->show_message(__('WP Google Tag Manager is missing a container id. <a href="/wp-admin/options-general.php?page=wp_google_tag_manager">Fix this!</a>','wp_google_tag_manager'), true);
    }
  }

  /**
   * @return void
   *
   * admin_init hook
   */
  public function admin_init() {

		$this->register_settings();

    global $current_user;
    $user_id = $current_user->ID;
    /* If user clicks to ignore the notice, add that to their user meta */
    if ( isset($_GET['wp_google_tag_manager_ignore_notice']) && '0' == $_GET['wp_google_tag_manager_ignore_notice'] ) {
      add_user_meta($user_id, 'wp_google_tag_manager_ignore_notice', 'true', true);
    }
  }

	/**
	 * Enqueue Admin Scripts & Styles
	 *
	 * @param  string  $hook  Admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {

		if ( 'settings_page_wp_google_tag_manager' == $hook ) {
			wp_enqueue_script( 'thickbox', null,  array( 'jquery' ) );
			wp_enqueue_style( 'thickbox.css', includes_url( '/js/thickbox/thickbox.css' ), null, '1.0' );
		}

	}

  /**
   * @return void
   *
   * admin_menu hook
   */
  public function admin_menu() {

    global $pagenow;

    if($this->container_id == '' && $pagenow == 'index.php') {
      add_action('admin_notices', array(&$this,'show_missing_id_warning'));

    }
    add_options_page(__('WP Google Tag Manager','wp_google_tag_manager'),__('WP Google Tag Manager','wp_google_tag_manager'),"manage_options","wp_google_tag_manager",array(&$this,"settings_menu"));
  }

  /**
   * @return void
   *
   * Prints the Backend Settings Page
   */
  public function settings_menu() {
    ?>
      <div class="wrap">
        <h2><?php echo __('WP Google Tag Manager','wp_google_tag_manager'); ?></h2>
        <form method="post" action="options.php">
				<?php

				settings_fields( 'wp-google-tag-manager' );
				do_settings_sections( 'wp-google-tag-manager' );

				?>
				<p class="submit"><input type="submit" id="wp_google_tag_manager_submit" class="button button-primary" value="<?php _e( 'Save Changes' ); ?>"></p>
        </form>
        <p>
          <?php echo __('You can find your Google tag manager container id in','wp_google_tag_manager').' <br>"Container" --> "Container Settings" --> "Container Snippet" --> iframe src="//www.googletagmanager.com/ns.html?id=<strong>GTM-1A23</strong>"'; ?><br>
          <a class="thickbox" href="<?php echo plugin_dir_url(__FILE__)."/img/container1.jpg" ?>" title="Where you can find your container id 1"><?php echo __('Image','wp_google_tag_manager');?></a>
        </p><p>
          <?php echo __('Or in','wp_google_tag_manager').' <br>"Versions" --> highestNumber --> "Container Public ID"<br>'; ?>
          <a class="thickbox" href="<?php echo plugin_dir_url(__FILE__)."/img/container2.jpg" ?>" title="Where you can find your container id 2"><?php echo __('Image','wp_google_tag_manager');?></a>
        </p>
      </div>
    <?php
  }

	/**
	 * Register Settings
	 */
	public function register_settings() {

		// Add Code Setting
		add_settings_section(
			'wp_google_tag_manager_code',
			'',
			create_function( '', 'return "";' ),
			'wp-google-tag-manager'
		);

		// Add Code Field
		add_settings_field(
			'wp_google_tag_manager_container_id',
			__( 'Container ID', 'wp_google_tag_manager' ),
			array( $this, 'container_id_field' ),
			'wp-google-tag-manager',
			'wp_google_tag_manager_code'
		);

		register_setting( 'wp-google-tag-manager', 'wp_google_tag_manager_container_id', 'sanitize_text_field' );

	}

	/**
	 * Container ID Field
	 */
	public function container_id_field() {

		printf( '<input type="text" id="wp_google_tag_manager_container_id" name="wp_google_tag_manager_container_id" placeholder="GTM-ABCD" value="%s">', esc_attr( $this->container_id ) );

	}

  public function output_manual() {
    if(!$this->called) {
      $this->output_container();
    }
  }

  /**
   * @return void
   *
   * print the google code into footer
   */
  public function output_container() {
    if($this->called) { // do not output again
      return;
    }
    else {
      $this->called = true;
    }
    ?>
<!-- Google Tag Manager -->
<noscript><iframe src="//www.googletagmanager.com/ns.html?id=<?php echo $this->container_id; ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo $this->container_id; ?>');</script>
<!-- End Google Tag Manager -->
    <?php
  }
}

global $wp_google_tag_manager;
$wp_google_tag_manager = new WpGoogleTagManager();
