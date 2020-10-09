<?php
/**
 * Registers meta boxes and handles admin-side functionality.
 *
 * @package Jeremys_Friend
 * @since 1.0.0
 */

if ( ! class_exists( 'Jeremys_Friend_Admin' ) ) :
class Jeremys_Friend_Admin {
	/**
	 * The unique identifier of this plugin.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $plugin_name
	 */
	private $plugin_name;

	/**
	 * The current version of the plugin as a semver string.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string $version
	 */
	private $version;
	
	/**
	 * Whether to register custom post types and related functionality.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var bool $use_cpt
	 */
	private $use_cpt;
	
	private $cpt_meta;
	
	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 * 
	 * @param string $plugin_name 	The name of the plugin.
	 * @param string $version    		The version of this plugin.
	 * @param bool 	 $use_cpt				Whether to use custom post types.
	 * @param array  $cpt_meta			Array of registered CPT custom meta keys.
	 */
	public function __construct( $plugin_name, $version, $use_cpt ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->use_cpt = $use_cpt;
		
		$this->cpt_meta = array(
			'link',
			'fineprint',
			'expiry',
			'code',
			'phone',
			'cost',
		);
		
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post_meta' ) );
	}
	
	/**
	 * Register the meta boxes to be used for the job post type
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {
		if ( $this->use_cpt ) {
			add_meta_box( 'jeremysfriend_deal',
				__( 'Details', 'jeremys-friend' ),
				array( $this, 'render_meta_deal' ),
				'jeremy_deal',
				'side',
				'low'
			);
			
			add_meta_box( 'jeremysfriend_job',
				__( 'Details', 'jeremys-friend' ),
				array( $this, 'render_meta_job' ),
				'jeremy_job',
				'side',
				'low'
			);
		}
		
		if ( defined( 'EVENT_ORGANISER_VER' ) && ! function_exists( 'eo_get_event_tickets' ) ) {
			// Don't bother if user has Event Organiser pro or another plug-in.
			
			add_meta_box( 'jeremysfriend_event',
				__( 'Details', 'jeremys-friend' ),
				array( $this, 'render_meta_event' ),
				'event',
				'side',
				'low'
			);
		}
	}
	
	/**
	 * Prints the HTML for the Jeremy Deal post page meta box.
	 * 
	 * @since 1.0.0
	 * 
	 * @param object $post The current WP_Post object.
	 */
	public function render_meta_deal( $post ) {
		$fields = $this->get_post_meta();
		wp_nonce_field( basename(__FILE__), "jeremysfriend_nonce" );
		?>
		
		<p>
			<label for="_jeremysfriend_code">
				<?php esc_html_e( 'Coupon Code', 'jeremys-friend' ); ?>
			</label>
		</p>
		<input type="text" name="_jeremysfriend_code" maxlength="100" value="<?php echo esc_attr( $fields['code'] ); ?>">
		
		<p>
			<label for="_jeremysfriend_expiry">
				<?php esc_html_e( 'Expiration Date', 'jeremys-friend' ); ?>
			</label>
		</p>
		<input type="date" name="_jeremysfriend_expiry" value="<?php echo esc_attr( $fields['expiry'] ); ?>">

		<p>
			<label for="_jeremysfriend_fineprint">
				<?php esc_html_e( 'Fine Print', 'jeremys-friend' ); ?>
			</label>
		</p>
		<textarea name="_jeremysfriend_fineprint" rows="4" maxlength="2000"><?php echo wp_kses_post( $fields['fineprint'] ); ?></textarea>
		
		<p>
			<label for="_jeremysfriend_link">
				<?php esc_html_e( 'Website', 'jeremys-friend' ); ?>
			</label>
		</p>
		<input type="url" name="_jeremysfriend_link" value="<?php echo esc_url( $fields['link'] ); ?>">
		<?php
	}
	
	public function render_meta_event( $post ) {		
		$fields = $this->get_post_meta();
		wp_nonce_field( basename(__FILE__), "jeremysfriend_nonce" );
		?>
		
		<p>
			<label for="_jeremysfriend_cost">
				<?php esc_html_e( 'Cost', 'jeremys-friend' ); ?>
			</label>
		</p>
		<input type="text" id="_jeremysfriend_cost" name="_jeremysfriend_cost" value="<?php echo esc_html( $fields['cost'] ); ?>">
		
		<p>
			<label for="_jeremysfriend_link">
				<?php esc_html_e( 'Website', 'jeremys-friend' ); ?>
			</label>
		</p>
		<input type="url" id="_jeremysfriend_link" name="_jeremysfriend_link" value="<?php echo esc_url( $fields['link'] ); ?>">
		
		<p>
			<label for="_jeremysfriend_phone">
				<?php esc_html_e( 'Phone Number', 'jeremys-friend' ); ?>
			</label>
		</p>
		<input type="tel" id="_jeremysfriend_phone" name="_jeremysfriend_phone" value="<?php echo esc_html( $fields['phone'] ); ?>">
		<?php
	}
	
	/**
	 * Prints the HTML for the Jeremy Job post page meta box.
	 * 
	 * @since 1.0.0
	 * 
	 * @param object $post The current WP_Post object.
	 */
	public function render_meta_job( $post ) {
		$fields = $this->get_post_meta();
		wp_nonce_field( basename(__FILE__), "jeremysfriend_nonce" );
		?>
		
		<p>
			<label for="_jeremysfriend_link">
				<?php esc_html_e( 'Link', 'jeremys-friend' ); ?>
			</label>
		</p>
		<input type="url" id="_jeremysfriend_link" name="_jeremysfriend_link" value="<?php echo esc_url( $fields['link'] ); ?>">
		
		<p>
			<label for="_jeremysfriend_expiry">
				<?php esc_html_e( 'Expiration Date', 'jeremys-friend' ); ?>
			</label>
		</p>
		<input type="date" id="_jeremysfriend_expiry" name="_jeremysfriend_expiry" value="<?php echo esc_attr( $fields['expiry'] ); ?>">
		<?php
	}
	
	/**
	 * Populates an array with meta data for the given fields for a post. It can
	 * be used to avoid checking if a meta key exists repeatedly.
	 *
	 * @since 1.0.0
	 * 
	 * @return array 							 The {$field_names} array but with possible meta
	 * 														 values assigned to each key.
	 */
	private function get_post_meta() {
		global $post;
		
		$meta = get_post_custom( $post->ID );
		
		$fields = array();
		
		foreach( $this->cpt_meta as $field_slug ) {
			$field = '_jeremysfriend_' . $field_slug;
			// e.g. $fields['link'] = meta value of '_jeremysfriend_link'
			if ( array_key_exists( $field, $meta ) ) {
				$fields[$field_slug] = $meta[$field][0];
			} else {
				$fields[$field_slug] = null;
			}
		}
		return $fields;
	}
	
	/**
	 * Checks to make sure it's safe to save meta, then updates post meta from the
	 * $_POST values for the Deal post type.
	 *
	 * @since 1.0.0
	 * 
	 * @param int $post_id The ID of the current post.
	 */
	function save_post_meta( $post_id ) {
		global $post;

		if ( ! isset( $_POST["jeremysfriend_nonce"] ) ||
				 ! wp_verify_nonce( $_POST['jeremysfriend_nonce'], basename(__FILE__) ) ||
			 	 ! current_user_can( 'edit_post', $post->ID ) ) {
			return $post_id;
		}
		
		if ( ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) ||
				 ( defined('DOING_AJAX') && DOING_AJAX ) ||
				 isset( $_REQUEST['bulk_edit'] ) ||
			 	 ( isset( $post->post_type ) && $post->post_type == 'revision' ) ) {
			return $post_id;
		}
		
		// Counting on register_post_meta to handle our sanitization...
		foreach ( $this->cpt_meta as $field_slug ) {
			$field = '_jeremysfriend_' . $field_slug;
			if ( ! isset( $_POST[$field] ) || empty( $_POST[$field] ) ) {
				delete_post_meta( $post->ID, $field );
			} else {
				update_post_meta( $post->ID, $field, $_POST[$field] );
			}
		}
	}
}
endif;