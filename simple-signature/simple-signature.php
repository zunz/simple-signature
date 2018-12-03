<?php
/*
Plugin Name:  Simple Signature
Plugin URI:   
Description:  Ini adalah plugin untuk menambahkan tanda tangan di akhir post / page
Version:      0.1
Author:       Zunan Arif - 15111131
Author URI:   
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

define ( 'SSN_PLUGIN_URL', plugins_url ( '', __FILE__ ) );

function tambah_tanda_tangan_func($content) {
	if(is_single() || is_page()):
		$output = '<div class="simple-signature"><img src="'.SSN_PLUGIN_URL.'/sign.png" alt="" class="alignright"></div>';
		$content .= $output;
	endif;
    return $content;
}

add_filter('the_content', 'tambah_tanda_tangan_func');




class SSN_Options_Page {
	function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_options' ) );
		add_action( 'admin_init', array( $this, 'ssn_options_init' ) );
    }
	
	function ssn_options_init() {
	
		register_setting( 'ssn_optgroup', 'ssn_settings' );	
		
		/* Set Default Value */
		if(!get_option('ssn_settings')) {
			update_option('ssn_settings', array(
				'align' => 'alignleft',
				'image' => '',
			));
		}
		
		add_settings_section(
			'ssn_section_demo',
			__( 'Settings', 'simple-signature' ),
			'__return_false',
			'ssn_optgroup'
		);
		
		add_settings_field(
			'ssn_align_field',
			__( 'Alignment', 'simple-signature' ),
			array( $this, 'ssn_align_cb' ),
			'ssn_optgroup',
			'ssn_section_demo',
			array(
				'slug' => 'align'
			)
		);
		
		add_settings_field(
			'ssn_image_field',
			__( 'Signature Image', 'simple-signature' ),
			array( $this, 'ssn_img_cb' ),
			'ssn_optgroup',
			'ssn_section_demo',
			array(
				'slug' => 'image'
			)
		);
		
		
	}
	
	function ssn_align_cb($args) {
		$settings = get_option( 'ssn_settings' );
		$slug = $args['slug'];
		$name = 'ssn_settings['.$slug.']';
		$value = $settings[$slug];
		
		$alignments = array(
			'alignleft' => 'Left',
			'aligncenter' => 'Center',
			'alignright' => 'Right',
		);
		
		?>
		<select name="<?php echo $name; ?>" id="<?php echo $slug; ?>">
			
			<?php foreach($alignments as $key => $label): ?>
				<option value="<?php echo $key; ?>" <?php selected($key, $value); ?>><?php echo $label; ?></option>
			<?php endforeach; ?>
			
		</select>			
		<?php
		
	}
	
	function ssn_img_cb($args) {
		$settings = get_option( 'ssn_settings' );
		$slug = $args['slug'];
		$name = 'ssn_settings['.$slug.']';
		$value = $settings[$slug];		
		
		
		// WordPress library
		wp_enqueue_media();
		?>
		<div class='image-preview-wrapper'>
			<img id='image-preview' src='<?php echo wp_get_attachment_url( $value ); ?>' style='max-width: 140px;'>
		</div>
		<input id="upload_image_button" type="button" class="button" value="<?php _e( 'Upload image' ); ?>" />
		<input type='hidden' name='<?php echo $name; ?>' id='<?php echo $slug; ?>' value='<?php echo $value; ?>'>
		<?php
		
	}
	
	function settings_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
			<form action="options.php" method="post">
				<?php            
				settings_fields('ssn_optgroup');
				do_settings_sections('ssn_optgroup');           
				submit_button('Update Settings');
				?>
			</form>
		</div>
		<?php
    }
	
	function admin_options() {
        add_options_page(
			__( 'Simple Signature Settings', 'simple-signature' ),
			'Simple Signature',
			'manage_options',
			'ssn-options',
			array( $this, 'settings_page' )
        );		
    }
	
}

if ( is_admin() ){
	new SSN_Options_Page;
}


add_action( 'admin_footer', 'media_selector_print_scripts' );

function media_selector_print_scripts() {

	$my_saved_attachment_post_id = get_option( 'media_selector_attachment_id', 0 );

	?><script type='text/javascript'>
		jQuery( document ).ready( function( $ ) {
			// Uploading files
			var file_frame;
			var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id
			var set_to_post_id = <?php echo $my_saved_attachment_post_id; ?>; // Set this
			jQuery('#upload_image_button').on('click', function( event ){
				event.preventDefault();
				// If the media frame already exists, reopen it.
				if ( file_frame ) {
					// Set the post ID to what we want
					file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
					// Open frame
					file_frame.open();
					return;
				} else {
					// Set the wp.media post id so the uploader grabs the ID we want when initialised
					wp.media.model.settings.post.id = set_to_post_id;
				}
				// Create the media frame.
				file_frame = wp.media.frames.file_frame = wp.media({
					title: 'Select a image to upload',
					button: {
						text: 'Use this image',
					},
					multiple: false	// Set to true to allow multiple files to be selected
				});
				// When an image is selected, run a callback.
				file_frame.on( 'select', function() {
					// We set multiple to false so only get one image from the uploader
					attachment = file_frame.state().get('selection').first().toJSON();
					// Do something with attachment.id and/or attachment.url here
					$( '#image-preview' ).attr( 'src', attachment.url ).css( 'width', 'auto' );
					$( '#image' ).val( attachment.id );
					// Restore the main post ID
					wp.media.model.settings.post.id = wp_media_post_id;
				});
					// Finally, open the modal
					file_frame.open();
			});
			// Restore the main ID when the add media button is pressed
			jQuery( 'a.add_media' ).on( 'click', function() {
				wp.media.model.settings.post.id = wp_media_post_id;
			});
		});
	</script><?php
}