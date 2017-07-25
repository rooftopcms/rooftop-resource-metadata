<?php
/**
 * Class SampleTest
 *
 * @package Rooftop_Resource_Metadata
 */

/**
 * Sample test case.
 */
class TestResponseMetaAttributes extends WP_UnitTestCase {

    public function setUp() {
        parent::setUp();

        global $wp_rest_server;
        $this->server = $wp_rest_server = new \WP_REST_Server;
        do_action( 'rest_api_init' );

        // add the post and some post meta
        $this->post = $this->factory->post->create_and_get( array( 'post_title' => 'foo', 'post_meta' => array( 'some_key' => 'the value') ) );
        add_post_meta( $this->post->ID, 'post_meta', array( 'the_key', 'the value' ) );
    }

	/**
	 * Test that the REST API is returning post-type specific meta attributes
	 */
	function test_post_meta_is_in_response() {
        $response = $this->get_post_response();

		$this->assertTrue( array_key_exists( 'post_meta', $response->data ) );
	}

	function test_post_meta_has_expected_keys() {
        $response = $this->get_post_response();


        $this->assertTrue( array_key_exists( 'the_key', array_flip( array_values( $response->data['post_meta'] ) ) ) );
    }

    private function get_post_response() {
        $request  = new WP_REST_Request( 'GET', "/wp/v2/posts/{$this->post->ID}" );
        return $this->server->dispatch( $request );
    }
}
