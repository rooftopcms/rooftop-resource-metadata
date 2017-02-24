<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://error.agency
 * @since             1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       Rooftop Resource Metadata
 * Plugin URI:        http://errorstudio.co.uk
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Error
 * Author URI:        http://errorstudio.co.uk
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       rooftop-resource-metadata
 * Domain Path:       /languages
 */


function add_update_attribute_hooks( $prepared_post, $request ) {
  if ( isset($request['status'] ) ) {
      $prepared_post->post_status = $request['status'];
  }

  $content_attributes = $request['content'];
  if( $content_attributes && array_key_exists( 'content', $content_attributes['basic'] ) ) {
      $prepared_post->post_content = array_key_exists( 'content', $content_attributes['basic'] ) ? $content_attributes['basic']['content'] : $request['content'];
  }

  return $prepared_post;
}

function add_insert_attribute_hooks( $prepared_post, $request, $success ) {
  if( $prepared_post->post_type ) {
      $meta_data_key = $prepared_post->post_type."_meta";
      $meta_data = $request[$meta_data_key];

      if( $meta_data ) {
          $current_meta_data = get_post_meta( $prepared_post->ID, $meta_data_key, true );
          $current_meta_data = ( is_array($current_meta_data) ? $current_meta_data : Array() );

          $custom_attributes = Array();

          foreach($meta_data as $key => $value) {
              if( empty( $value ) ) {
                  unset( $current_meta_data[$key] ); //value has been deleted, so remove that key
              }else {
                  if( is_array( $value ) ) {
                      $old_value = @$current_meta_data[$key];
                      $old_value = ( $old_value && is_array( $old_value ) ) ? $old_value : [];
                      $value = array_merge( $old_value, $value );

                      if( empty( array_filter( $value ) ) ) {
                          unset( $current_meta_data[$key] );
                          continue;
                      }else {
                          foreach( $value as $k => $v ) {
                              if( empty( $v ) ) unset( $value[$k] );
                          }
                      }
                  }

                  $custom_attributes[$key] = $value;
              }

              unset($key);
              unset($value);
          }

          $updated_custom_attributes = array_merge( $current_meta_data, $custom_attributes );
          update_post_meta( $prepared_post->ID, $prepared_post->post_type."_meta", $updated_custom_attributes );
      }

      do_action( "rooftop_".$prepared_post->post_type."_rest_insert_post", $prepared_post, $request, $success );
  }
}

function set_meta_attributes( $response, $post, $request ) {
  // Make sure meta is added to the post, not a revision.
  $post_id = $post->ID;

  /*
   * if the post we've been given is a revision, it wont have any meta-data saved
   * against it; switch the post id and fetch the relevant post meta.
   */
  if ( $the_post = wp_is_post_revision( $post->ID ) ) {
      $post_id = $the_post;
  }

  $custom_attributes = get_post_meta( $post_id, $post->post_type."_meta", false );

  if( $custom_attributes && count( $custom_attributes ) ) {
      foreach( $custom_attributes[0] as $key => $value ) {
          $response->data[$post->post_type.'_meta'][$key] = $value;
      }
  }

  do_action( "rooftop_".$post->post_type."_rest_presentation_filters" );

  return $response;
}

function prepare_metadata_init_hooks() {
    $post_types = get_post_types( array( 'public' => true, 'show_in_rest' => true ) );
    foreach( $post_types as $post_type ) {
        add_filter( "rest_pre_insert_{$post_type}", 'add_update_attribute_hooks', 10, 2);
        add_action( "rest_insert_{$post_type}",     'add_insert_attribute_hooks', 10, 3);
        add_filter( "rest_prepare_{$post_type}",    'set_meta_attributes', 10, 3);
    }
}

add_action( "rest_api_init", "prepare_metadata_init_hooks", 10 );


?>
