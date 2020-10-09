<?php
/**
 * Integrations with other plug-ins.
 * 
 * @package Jeremys_Friend
 * @since 1.0.0
 */

if ( ! class_exists( 'Jeremys_Friend_Integrations' ) ) :
class Jeremys_Friend_Integrations {
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
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 * 
	 * @param string $plugin_name 	The name of the plugin.
	 * @param string $version    		The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		
		if ( defined( 'EVENT_ORGANISER_VER' ) ) {
			add_action( 'eventorganiser_save_event', array( $this, 'eo_add_date_to_slug' ) );
			
			if ( defined( 'EVENT_ORGANISER_VER' ) && ! function_exists( 'eo_get_event_tickets' ) ) {
				add_action( 'init', array( $this, 'eo_register_meta' ) );
			}
		}
	}
	
	public function eo_register_meta() {
		register_post_meta(
			'event',
			'_jeremysfriend_cost',
			array(
				'type'              => 'string',
				'description'       => __( 'The cost to attend this event.', 'jeremy' ),
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_text_field' ),
				'show_in_rest'      => true,
			),
		);
		register_post_meta(
			'event',
			'_jeremysfriend_link',
			array(
				'type'              => 'string',
				'description'       => __( 'An external link for this event.', 'jeremy' ),
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_url' ),
				'show_in_rest'      => true,
			),
		);
		register_post_meta(
			'event',
			'_jeremysfriend_phone',
			array(
				'type'              => 'string',
				'description'       => __( 'An RSVP phone number for this event.', 'jeremy' ),
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => array( $this, 'sanitize_text_field' ),
				'show_in_rest'      => true,
			),
		);
	}
	
	/**
	 * Adds a number corresponding with the date to the event slug when it's
	 * inserted. This allows you to add annual events with the same name without 
	 * needing to manually distinguish their slugs.
	 *
	 * @see eventorganiser_save_event
	 *
	 * @since 1.0.0
	 * 
	 * @param string $post_id ID of the event being inserted.
	 */
	public function eo_add_date_to_slug( $event_id ) {
		$event = get_post( $event_id );
		if ( ! wp_is_post_revision( $event_id ) ) {
			// Prevent infinite loop
			remove_action( 'eventorganiser_save_event', array( $this, 'eo_add_date_to_slug' ) );
			
			// Let the user customize the slug if they keep some ID on the end.
			if ( ! preg_match( '~(\S+)-(\d+)~', $event->post_name ) ) {
				wp_update_post( array(
					'ID' => $event_id,
					'post_name' => sanitize_title( $event->post_title ) . '-' . eo_get_schedule_start( 'Ydm', $event_id ),
				) );
			}
			
			add_action( 'eventorganiser_save_event', array( $this, 'eo_add_date_to_slug' ) );
		}
	}
}
endif;