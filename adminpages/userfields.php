<?php
	global $msg, $msgt;
	
	// Only admins can get this.
	if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_userfields' ) ) ) {
		die( __( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}

	// Get all levels regardless of visibility.	
	$levels = pmpro_getAllLevels( false, true );

	/**
	 * Save fields if form was submitted.
	 */
	if ( ! empty( $_REQUEST['savesettings'] ) ) {
		// Check nonce.
		check_admin_referer( 'savesettings', 'pmpro_userfields_nonce' );
				
		$groups = json_decode( stripslashes( $_REQUEST['pmpro_user_fields_settings'] ) );

		// TODO: Do we want to sanitize the input here before being added to the DB?
		update_option( 'pmpro_user_fields_settings', $groups, false );
		
		// Assume success.
		$msg = true;
		$msgt = __( 'Your user field settings have been updated.', 'paid-memberships-pro' );
	}

	/**
	 * Get the user fields from options.
	 */
	$user_fields_settings = pmpro_get_user_fields_settings();

	/**
	 * Load the common header for admin pages.
	 *
	 */
	require_once( dirname(__FILE__) . '/admin_header.php' );

	/**
	 * Meta boxes for User Fields admin page.
	 *
	 */
	add_meta_box(
		'pmpro_userfields_save',
		esc_html( 'Save', 'paid-memberships-pro' ),
		'pmpro_userfields_save_widget',
		'memberships_page_pmpro-userfields',
		'side'
	);
	add_meta_box(
		'pmpro_userfields_help',
		esc_html( 'User Fields Help', 'paid-memberships-pro' ),
		'pmpro_userfields_help_widget',
		'memberships_page_pmpro-userfields',
		'side'
	);

	/**
	 * Meta box to show a save button and other data.
	 *
	 */
	function pmpro_userfields_save_widget() { ?>
		<p class="submit">
			<input id="pmpro_userfields_savesettings" name="savesettings" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save All Settings', 'paid-memberships-pro' ); ?>" />
		</p>
		<?php
	}

	/**
	 * Meta box to show help information.
	 *
	 */
	function pmpro_userfields_help_widget() { ?>
		<p><?php esc_html_e( 'User fields can be added to the membership checkout form, the frontend user profile edit page, and for admins only on the Edit Users Screen in the WordPress admin.', 'paid-memberships-pro' ); ?></p>
		<p><?php esc_html_e( 'Groups are used to define a collection of fields that should be displayed together under a common heading. Group settings control field locations and membership level visibility.', 'paid-memberships-pro' ); ?></p>
		<?php
	}

	?>		
	<h1 class="wp-heading-inline"><?php esc_html_e( 'User Fields', 'paid-memberships-pro' ); ?></h1>
	<hr class="wp-header-end">

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">

			<div id="post-body-content">
				<div class="inside">

					<?php						
						foreach( $user_fields_settings as $group ) {
							echo pmpro_get_field_group_html( $group );
						}
					?>

					<p class="text-center">
						<button id="pmpro_userfields_add_group" name="pmpro_userfields_add_group" class="button button-primary button-hero">
							<?php
								/* translators: a plus sign dashicon */
								printf( esc_html__( '%s Add Field Group', 'paid-memberships-pro' ), '<span class="dashicons dashicons-plus"></span>' ); ?>
						</button>
					</p>

				</div> <!-- end inside -->
			</div> <!-- end post-body-content -->

			<div id="postbox-container-1" class="postbox-container">
				<form action="" method="post" enctype="multipart/form-data">
					<?php wp_nonce_field('savesettings', 'pmpro_userfields_nonce');?>
					<?php do_meta_boxes( 'memberships_page_pmpro-userfields', 'side', '' ); ?>
					<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
					<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>
					<input type="hidden" id="pmpro_user_fields_settings" name="pmpro_user_fields_settings" value="<?php echo esc_attr( json_encode( $user_fields_settings ) );?>" />
				</form>
			</div> <!-- end postbox-container-1 -->

		</div> <!-- end post-body -->
	</div> <!-- end poststuff -->		
	<script type="text/javascript">
	  //<![CDATA[
	  jQuery(document).ready( function($) {
		  // close postboxes that should be closed
		  $('.if-js-closed').removeClass('if-js-closed').addClass('closed');
		  // postboxes setup
		  postboxes.add_postbox_toggles('admin_page_pmpro-userfields');
	  });
	  //]]>
	</script>

<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");