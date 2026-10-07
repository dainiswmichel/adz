<?php
class WP_REST_Categories_Controller {

	
    // Here initialize our namespace and resource name.
    public function __construct() {
        $this->namespace     = '/adz_server/v1';
        $this->resource_name = 'categories';

    }

    // Register our routes.
    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->resource_name, array(
            // Here we register the readable endpoint for collections.
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
            ),
			array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'post_items' ),
                'permission_callback' => array( $this, 'post_items_permissions_check' ),
            ),
            // Register our schema callback.
            'schema' => array( $this, 'get_item_schema' ),
        ) );
        register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<id>[\d]+)', array(
            // Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'get_item' ),
                'permission_callback' => array( $this, 'get_item_permissions_check' ),
            ),

            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'post_items' ),
                'permission_callback' => array( $this, 'post_items_permissions_check' ),
            ),
            // Register our schema callback.
            'schema' => array( $this, 'get_item_schema' ),
        ) );
    }

    /**
     * Check permissions for the posts.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_items_permissions_check( $request ) {
			  // Can always read
        return true;
    }

		/**
     * Check permissions for the posts.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function post_items_permissions_check( $request ) {
			  // Can always read
        return true;
    }


    /**
     * Grabs the five most recent posts and outputs them as a rest response.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_items( $request ) {


        $posts = get_terms(array('taxonomy' => 'ad_taxonomy','hide_empty' => false));
        
           $max_posts = count($posts);
        $data = array();

        if ( empty( $posts ) ) {
            return rest_ensure_response( $data );
        }
				/*$index = rand ( 0, $max_posts - 1 );
				$post = $posts[ $index ];
				$response = $this->prepare_item_for_response( $post, $request );
				$data[] = $this->prepare_response_for_collection( $response );*/

        
        foreach ( $posts as $post ) {
            $response = $this->prepare_item_for_response( $post, $request );
            $data[] = $this->prepare_response_for_collection( $response );
        }
				

        // Return all of our comment response data.
        return rest_ensure_response( $data );
    }

		/*public function post_items( $data ) {
           
		  $data = $this->extract_post_data( $data );
          
			$return = array();
			$return['css_response'] = $data;
            $data['css'];
            $publisher_css_file = 'publisher-'.$data['id'].'.css';
            $handle = fopen(ABSPATH . 'wp-content/plugins/adz_ad_network/publisher_css/'.$publisher_css_file, 'w') or die('Cannot open file:  '.$publisher_css_file);
            $css = $data['css'];
            fwrite($handle, $css);

			$response = rest_ensure_response( $return );
		    $response->set_status( 201 );
		    $response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace , $this->resource_name , $return['css_response'] ) ) );

			return $response;

		}*/

        public function post_items( $data ) {
           
          $data = $this->extract_post_data( $data );
          
            $return = array();
            $return['premium_setting'] = $data;
            $data['adz_serving_from'];
            $publisher_css_file = 'publisher-'.$data['id'].'.css';
            update_post_meta($data['id'],'adz_serving_from',$data['adz_serving_from']);

            $response = rest_ensure_response( $return );
            $response->set_status( 201 );
            $response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace , $this->resource_name , $return['css_response'] ) ) );

            return $response;

        }

    /**
     * Check permissions for the posts.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_item_permissions_check( $request ) {
        return true;
    }

    /**
     * Grabs the five most recent posts and outputs them as a rest response.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_item( $request ) {

	 $parameters = $request->get_params();
     $id = (int) $request['id'];
        $post = get_post( $id );
        
        if ( empty( $post ) ) {
            return rest_ensure_response( array() );
        }

				$post = $this->prepare_item_for_response( $post, $request );


				$response = rest_ensure_response( $post );
				return $response;


    }

		protected function ad_network_filter($content) {
			return $content;
		}

    /**
     * Matches the post data to the schema we want.
     *
     * @param WP_Post $post The comment object whose response is being prepared.
     */
    public function prepare_item_for_response( $post, $request ) {
        $post_data = array();

        $schema = $this->get_item_schema( $request );

        // We are also renaming the fields to more understandable names.
        if ( isset( $schema['properties']['id'] ) ) {
            $post_data['id'] = (int) $post->term_id;
        }

        if ( isset( $schema['properties']['name'] ) ) {
            $post_data['name'] = $post->name;
						// apply_filters( 'the_content', $post->post_content, $post );
        }


        if ( isset( $schema['properties']['date'] ) ) {
            $post_data['slug'] = $post->slug;
						// apply_filters( 'the_content', $post->post_content, $post );
        }

        return rest_ensure_response( $post_data );
    }

    /**
     * Prepare a response for inserting into a collection of responses.
     *
     * This is copied from WP_REST_Controller class in the WP REST API v2 plugin.
     *
     * @param WP_REST_Response $response Response object.
     * @return array Response data, ready for insertion into collection data.
     */
    public function prepare_response_for_collection( $response ) {
        if ( ! ( $response instanceof WP_REST_Response ) ) {
            return $response;
        }

        $data = (array) $response->get_data();
        $server = rest_get_server();

        if ( method_exists( $server, 'get_compact_response_links' ) ) {
            $links = call_user_func( array( $server, 'get_compact_response_links' ), $response );
        } else {
            $links = call_user_func( array( $server, 'get_response_links' ), $response );
        }

        if ( ! empty( $links ) ) {
            $data['_links'] = $links;
        }

        return $data;
    }

    /**
     * Get our sample schema for a post.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_item_schema( $request ) {
        $schema = array(
            // This tells the spec of JSON Schema we are using which is draft 4.
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            // The title property marks the identity of the resource.
            'title'                => 'register',
            'type'                 => 'object',
            // In JSON Schema you can specify object properties in the properties attribute.
            'properties'           => array(
                'id' => array(
                    'description'  => esc_html__( 'Unique identifier for the object.', 'my-textdomain' ),
                    'type'         => 'integer',
                    'context'      => array( 'view', 'edit', 'embed' ),
                    'readonly'     => true,
                ),
                'name' => array(
                    'description'  => esc_html__( 'Name of the publisher', 'my-textdomain' ),
                    'type'         => 'string',
                ),
								'date' => array(
										'description'  => esc_html__( 'Date publisher registered', 'my-textdomain' ),
										'type'         => 'string',
								),
            ),
        );

        return $schema;
    }

    protected function extract_post_data( $req ) {
        if ( $req instanceof WP_REST_Request ) {
            $data = json_decode( $req->get_body(), true );
            if (! is_array($data) ) {
                $data = $req->get_params();
            }
        } else {
            $data = new WP_Error( 'Invalid Data', __( 'Expecting WP_REST_Request' ) );
        }
        return $data;
    }

    // Sets up the proper HTTP status code for authorization.
    public function authorization_status_code() {

        $status = 401;

        if ( is_user_logged_in() ) {
            $status = 403;
        }

        return $status;
    }
}

// Function to register our new routes from the controller.
function adz_cpt_categories_my_rest_routes() {
   
    $controller = new WP_REST_Categories_Controller();
    $controller->register_routes();
}

add_action( 'rest_api_init', 'adz_cpt_categories_my_rest_routes' );
