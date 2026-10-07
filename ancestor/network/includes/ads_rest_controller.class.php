<?php
class WP_REST_Ads_Controller {

    // Here initialize our namespace and resource name.
    public function __construct() {
        $this->namespace     = '/adz_server/v1';
        $this->resource_name = 'ads';
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
            // Register our schema callback.
            'schema' => array( $this, 'get_item_schema' ),
        ) );

        register_rest_route( $this->namespace, '/' . $this->resource_name.'/is_visitor_logged', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'get_visitor' ),
                'permission_callback' => '',
            ),
            // Register our schema callback.
            'schema' => array( $this, 'get_item_schema' ),
        ) );

        register_rest_route( $this->namespace, '/' . $this->resource_name.'/is_manager_can_edit', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'check_manage_can_edit' ),
                'permission_callback' => '',
            ),
            // Register our schema callback.
            'schema' => array( $this, 'get_item_schema' ),
        ) );

        register_rest_route( $this->namespace, '/' . $this->resource_name.'/is_premium_user/(?P<id>[\d]+)', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'check_for_premium_user' ),
                'permission_callback' => '',
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
            // Register our schema callback.
            'schema' => array( $this, 'get_item_schema' ),
        ) );

                register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<id>[\d]+)', array(
            // Notice how we are registering multiple endpoints the 'schema' equates to an OPTIONS request.
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'post_item' ),
                'permission_callback' => array( $this, 'post_item_permissions_check' ),
            ),
                        array(
                                'methods'   => 'DELETE',
                                'callback'  => array( $this, 'delete_item' ),
                                'permission_callback' => array( $this, 'post_item_permissions_check' ),
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
              if (isset($_GET['id'],$_GET['token'], $_GET['nettoken'])) {
                    $id = $_GET['id'];
                    $meta = get_post_meta($id);
                    if (is_array($meta) ) {
                            if (is_array($meta) ) {
                                if ( $_GET['token'] == $meta['post_token'][0]) {
                                    $valid_token = md5($id.'salt');
                                    if ($_GET['token'] == $valid_token) {
                                        if ($_GET['nettoken'] == $meta['network_token'][0]) {
                                            return true;
                                        }
                                    }
                                }
                        }
                    }
                }
                return false;

    }

        public function post_item_permissions_check( $request ) {
              if (isset($request['id']) && isset($_GET['token']) && isset($_GET['nettoken'])) {
                    $id = $request['id'];
                    $meta = get_post_meta($id);
                    if (is_array($meta) ) {
                        if ( $_GET['token'] == $meta['post_token'][0]) {
                            $valid_token = md5($id.'salt');
                            if ($_GET['token'] == $valid_token) {
                                if ($_GET['nettoken'] == $meta['network_token'][0]) {
                                    return true;
                                }
                            }
                        }
                    }
                }
                return false;

    }

    public function network_adz($publisher_id,$referrer_id){
        $network_ad_content;
        $adz_types = wp_get_post_terms($publisher_id,'ad_taxonomy');
        $preference = array();
        if(!empty($adz_types)){
            foreach ($adz_types as $adz_type){
                $preference[] = $adz_type->term_id;
            }
        }
        
        $preference_args = array(
            'post_per_page' => -1,
            'post_status' => 'publish',
            'post_type' => 'adz_ad',
            'tax_query' => array(
                array(
                    'taxonomy' => 'ad_taxonomy',
                    'terms' => $preference,
                    'field' => 'id',
                    'include_children' => true,
                    'operator' => 'IN'
                ),
            ),
            'meta_query' => array(
                'relation' => 'AND',
                
                array( 
                    'key' => 'is_network_adz', 
                    'value' => 'yes', 
                    'compare' => '='
                ),
                array( 
                    'key' => 'network_adz_type', 
                    'value' => 'referrer_ad', 
                    'compare' => '!='
                ),
                array( 
                    'key' => 'network_adz_type', 
                    'value' => 'mimicked_ad', 
                    'compare' => '!='
                ),
                array( 
                    'key' => 'network_adz_type', 
                    'value' => 'network_wide_ad', 
                    'compare' => '!='
                ),
            ),

        );
        $preference_adz = get_posts( $preference_args );
        $preference_ad = $this->fetch_adz_id($preference_adz);

        $referrer_args = array(
            'post_per_page' => -1,
            'post_status' => 'publish',
            'post_type' => 'adz_ad',
            'tax_query' => array(
                array(
                    'taxonomy' => 'ad_taxonomy',
                    'terms' => $preference,
                    'field' => 'id',
                    'include_children' => true,
                    'operator' => 'IN'
                ),
            ),
            'meta_query' => array(
                'relation' => 'AND',
                array( 
                    'key' => 'is_network_adz', 
                    'value' => 'yes', 
                    'compare' => '='
                ),
                
                array( 
                    'key' => 'network_adz_type', 
                    'value' => 'referrer_ad', 
                    'compare' => '='
                ),

                array( 
                    'key' => 'network_adz_type_meta_value', 
                    'value' => $referrer_id, 
                    'compare' => '='
                )
            ),
        );
        $referrer_adz = get_posts( $referrer_args );
        $referrer_ad = $this->fetch_adz_id($referrer_adz);

        $network_wide_args = array(
            'post_per_page' => -1,
            'post_status' => 'publish',
            'post_type' => 'adz_ad',
            
            'meta_query' => array(
                'relation' => 'AND',
                array( 
                    'key' => 'is_network_adz', 
                    'value' => 'yes', 
                    'compare' => '='
                ),
                
                array( 
                    'key' => 'network_adz_type', 
                    'value' => 'network_wide_ad', 
                    'compare' => '='
                ),
            ),
        );
        $network_wide_adz = get_posts( $network_wide_args ); 
              
        $network_wide_ad = $this->fetch_adz_id($network_wide_adz);
        $unshiffted_network_adz = array_merge($preference_ad,$referrer_ad,$network_wide_ad);
        $network_adz = array_merge($preference_ad,$referrer_ad,$network_wide_ad);

        $network_adz_stats = get_post_meta($publisher_id,'network_adz_stats',true);
       
        if(empty($network_adz_stats['served_adz'])){

            $adz_need_to_served = array_shift($network_adz);
            $args = array(
                'post_per_page' => 1,
                'post_status' => 'publish',
                'post_type' => 'adz_ad',
                'post__in' => array($adz_need_to_served),

            );
            $network_ad_content = get_posts( $args );

            $stats['served_adz'] = array($adz_need_to_served);
            $stats['un_served_adz'] = $network_adz;
            update_post_meta($publisher_id,'network_adz_stats',$stats);

            if(count($unshiffted_network_adz) == 1){

                 $stats['served_adz'] = array();
                 $stats['un_served_adz'] = array();
                 update_post_meta($publisher_id,'network_adz_stats',$stats);
            }

            
        }else{

            $adz_need_to_served = array_shift($network_adz_stats['un_served_adz']);
            $args = array(
                'post_per_page' => 1,
                'post_status' => 'publish',
                'post_type' => 'adz_ad',
                'post__in' => array($adz_need_to_served),

            );
            $network_ad_content = get_posts( $args );
            if(empty($network_adz_stats['un_served_adz'])){

                 $stats['served_adz'] = array();
                 $stats['un_served_adz'] = array();
            }else{
                $stats['served_adz'] = array_push($network_adz_stats['served_adz'],$adz_need_to_served);
                $stats['un_served_adz'] = $network_adz_stats['un_served_adz'];
            }
            update_post_meta($publisher_id,'network_adz_stats',$stats);
        }

        return $network_ad_content;       

    }

    public function get_visitor_adz($publisher_id,$visitor_id){

        $args = array(
            'author'        =>  $visitor_id,
            'post_per_page' => -1,
            'post_status' => 'publish',
            'post_type' => 'visitor_preference',
            'meta_query' => array( 
                'relation' => 'AND',
                array(
                    'key' => 'show_adz_starting_on', 
                    'value' => date("Y-m-d"),
                    'compare' => '<=', 
                    'type' => 'DATE' 
                    ),
                array(
                    'key' => 'stop_showing_adz_on', 
                    'value' => date("Y-m-d"), 
                    'compare' => '>=', 
                    'type' => 'DATE' 
                    )
                )

            );
        

        $visitors_adz = get_posts( $args );
        
        $unshiffted_visitors_ad = $this->fetch_adz_id($visitors_adz);
        $visitors_ad = $this->fetch_adz_id($visitors_adz); 
        $visitor_adz_stats = get_post_meta($publisher_id,'visitor_adz_stats',true);
        
        if(empty($visitor_adz_stats['served_adz'])){

            $adz_need_to_served = array_shift($visitors_ad);
            
            $visitor_ad_id = $adz_need_to_served;

            $stats['served_adz'] = array($adz_need_to_served);
            $stats['un_served_adz'] = $visitors_ad;
            update_post_meta($publisher_id,'visitor_adz_stats',$stats);

            if(count($unshiffted_visitors_ad) == 1){

                 $stats['served_adz'] = array();
                 $stats['un_served_adz'] = array();
                 update_post_meta($publisher_id,'visitor_adz_stats',$stats);
            }

            
        }else{

            $adz_need_to_served = array_shift($visitor_adz_stats['un_served_adz']);
            $visitor_ad_id = $adz_need_to_served;

            if(empty($network_adz_stats['un_served_adz'])){

                 $stats['served_adz'] = array();
                 $stats['un_served_adz'] = array();
            }else{
                $stats['served_adz'] = array_push($visitor_adz_stats['served_adz'],$adz_need_to_served);
                $stats['un_served_adz'] = $visitor_adz_stats['un_served_adz'];
            }
            update_post_meta($publisher_id,'visitor_adz_stats',$stats);
        }

        return $visitor_ad_id; 

    }

    public function fetch_adz_id($adz_array){
        $return = array();
        if(!empty($adz_array)){
            foreach ($adz_array as $key => $value) {
                $return[] = $value->ID; 
            }   
        }
        return $return;
    }
    /**
     * Grabs the five most recent posts and outputs them as a rest response.
     *
     * @param WP_REST_Request $request Current request.
     */

   
    public function get_visitor(){
        $visitor_ip = $_GET['visitor_ip'];
        $user_data = check_user_logged_in($visitor_ip);
        if ($user_data) {
            return rest_ensure_response( array('status' => 'logged_in','user_id' => $user_data) );
        }else{
            return rest_ensure_response( array('status' => 'not_logged'));
        }
    }

    public function check_manage_can_edit(){
        $manager_can_edit = get_option('manager_can_edit');
        if($manager_can_edit == 'Yes'){
            return rest_ensure_response( array('user_can_edit' => 'yes') );
        }elseif($manager_can_edit == 'No'){
            return rest_ensure_response( array('user_can_edit' => 'no') );
        }
    }

    public function check_for_premium_user( $request ){
        $user_id = $request['id'];

        if (WC_Subscriptions_Manager::user_has_subscription( $user_id, "2196", 'active') || wc_memberships_is_user_active_member( $user_id, 'premium-plugin-users' )) {
                return rest_ensure_response( array('premium_user' => 'yes') );
        }else{

             return rest_ensure_response( array('premium_user' => 'no') );
         }
    }


    public function get_items( $request ) {
        
        if(isset($_GET['id'])){
            $publisher_id = $_GET['id'];
            if($_GET['ad_from'] == 'publisher' && $_GET['publisher_ad'] != 'network'){
               $args = array(
                   'post_per_page' => 1,
                   'post_status' => 'publish',
                   'post_type' => 'adz_ad',
                   'post__in' => array($_GET['publisher_ad']),

               ); 
            }elseif($_GET['ad_from'] == 'network' || ($_GET['ad_from'] == 'publisher' && $_GET['publisher_ad'] == 'network')){
                $args = array(
                    'post_per_page' => 1,
                    'post_status' => 'publish',
                    'post_type' => 'adz_ad',
                    'meta_query' => array(
                        array( 
                            'key' => 'is_network_adz', 
                            'value' => 'yes', 
                            'compare' => '='
                        ),
                        array( 
                            'key' => 'network_adz_type', 
                            'value' => 'mimicked_ad', 
                            'compare' => '='
                        ),
                        array( 
                            'key' => 'network_adz_type_meta_value', 
                            'value' => $_GET['publisher_ad'], 
                            'compare' => '='
                        )
                    ),

                );
                $posts = get_posts( $args );
                
                if(empty($posts)){
                    $posts = $this->network_adz($publisher_id,$_GET['referral_code']);

                }

            }elseif($_GET['ad_from'] == 'visitor'){

                $visitor_id = $_GET['visitor_user_id'];
                $visitor_ad_id = $this->get_visitor_adz($publisher_id,$visitor_id);
                $publisher_user_id = $_GET['publisher_user_id'];
                $network_amazon_affiliate_id = "ufa4d4e06a-20";
                if(WC_Subscriptions_Manager::user_has_subscription( $publisher_user_id, "2196", 'active')) {

                    if($_GET['publisher_affiliate_id'] != ''){
                       
                       $affiliate_id = $_GET['publisher_affiliate_id'];
                    }else{
                       
                       $affiliate_id = $network_amazon_affiliate_id;
                    }

                }else{
                    if(get_option('visitor_adz_affiliate_id') == 'publisher'){
                        
                         if($_GET['publisher_affiliate_id'] != ''){
                            $affiliate_id = $_GET['publisher_affiliate_id'];
                         }else{
                            $affiliate_id = $network_amazon_affiliate_id;
                         }
                         
                         update_option('visitor_adz_affiliate_id','network');
                    }else{
                        
                         $affiliate_id =  $network_amazon_affiliate_id;
                         update_option('visitor_adz_affiliate_id','publisher');
                    }
                    
                }
               // $affiliate_id="14606-20";
               
                if($visitor_ad_id){

                    $visitor_ad_id;
                    $visitor_title = get_the_title( $visitor_ad_id );
                    $visitor_desc = get_post_meta($visitor_ad_id,"optional_more_detailed_description",true);
                    $visitor_amazon_urls = get_post_meta($visitor_ad_id,"url_of_product",true);
                    $adz_by_title = $this->get_result_amazon($visitor_title,$affiliate_id);
                    $adz_by_desc = $this->get_result_amazon($visitor_desc,$affiliate_id);
                    if($visitor_amazon_urls){       
                        $adz_by_url = $this->get_product_by_code($visitor_amazon_urls,$affiliate_id);
                    }else{
                        $adz_by_url = array();
                    }
                    if(empty($adz_by_desc)) $adz_by_desc = array();
                    $all_adz = array_merge($adz_by_title,$adz_by_desc,$adz_by_url);
                    $adz = $this->create_html_from_data($all_adz);    
                                 
                    $posts[0] = (object) array('ID' => 0, 'post_content' => $adz);
                    
                }

               
               
            }
            
            
            if(empty($posts)){

                $args = array(
                   'post_per_page' => 1,
                   'post_status' => 'publish',
                   'post_type' => 'adz_ad',
                   'post__in' => array($_GET['publisher_ad']),

                ); 
                $posts = get_posts( $args );               
               
            }
            $data = array();

            if ( empty( $posts ) ) {
                return rest_ensure_response( $data );
            }       
            $post = $posts[0];
            $response = $this->prepare_item_for_response( $post, $request );
            $data[] = $this->prepare_response_for_collection( $response );

            

            // Return all of our comment response data.
            return rest_ensure_response( $data );
        }
        
    }

    function get_result_amazon($search_keyword,$affiliate_id){
        $items_array = array();
        $xml = aws_signed_request('com', array(
          "Operation" => "ItemSearch",
          "SearchIndex" => "All",
          "Keywords" => $search_keyword,
          //"ItemId" => $asin,
          "IncludeReviewsSummary" => False,
          "ResponseGroup" => "AlternateVersions,Medium",
          
        ),$affiliate_id);

        $items = $xml->Items->Item;        

        foreach ($items as $item) {
           // echo $item->ASIN."<br>";
            $asin = htmlentities((string) $item->ASIN);
            $image_set_count = count($item->ImageSets->ImageSet);
            $items_array[$asin]['title'] =  htmlentities((string) $item->ItemAttributes->Title);   
            $items_array[$asin]['image'] =  htmlentities((string) $item->ImageSets->ImageSet[0]->MediumImage->URL); 

            if($image_set_count>0)
              $items_array[$asin]['image'] =  htmlentities((string) $item->ImageSets->ImageSet[$image_set_count-1]->MediumImage->URL); 

            $items_array[$asin]['url']   =  htmlentities((string) $item->DetailPageURL);
            $items_loop++;
        }
        //print_r($items_array);
        return $items_array;
    }

    public function get_product_by_code($amazon_search_title,$affiliate_id){

        $amazon_url_rtrim = rtrim($amazon_search_title,'|');
        $amazon_urls = explode('|', $amazon_url_rtrim);
        $items_ids = '';

        foreach($amazon_urls as $amazon_url)
        {
          $amazon_url = str_replace('&tag', '?tag', $amazon_url);       
          $amazon_url = str_replace('ref=', '?ref=', $amazon_url);              
          $parsed_url = parse_url($amazon_url, PHP_URL_PATH);       
          $item_id_array = explode('/', trim($parsed_url, '/'));        
          $items_ids .= end($item_id_array).',';  
        }

        $xml = aws_signed_request('com', 
                  array(    
                    "Operation" => "ItemLookup",
                    "ItemId" => rtrim($items_ids,','),    
                    "IncludeReviewsSummary" => False,   
                    "ResponseGroup" => "Medium,Offers,RelatedItems" ),$affiliate_id);

          
        $items = $xml->Items->Item;
        foreach ($items as $item) {
         // echo $item->ASIN."<br>";
          $asin = htmlentities((string) $item->ASIN);
          $image_set_count = count($item->ImageSets->ImageSet);
          $items_array[$asin]['title'] =  htmlentities((string) $item->ItemAttributes->Title);   
          $items_array[$asin]['image'] =  htmlentities((string) $item->ImageSets->ImageSet[0]->MediumImage->URL); 

          if($image_set_count>0)
            $items_array[$asin]['image'] =  htmlentities((string) $item->ImageSets->ImageSet[$image_set_count-1]->MediumImage->URL); 

          $items_array[$asin]['url']   =  htmlentities((string) $item->DetailPageURL);
          $items_loop++;
        }

        return $items_array;

    }
    
    public function create_html_from_data($data){
        $html = "<style>
        .section {
            clear: both;
            padding: 0px;
            margin: 0px;
        }
        .group:before,
        .group:after {
            content:'';
            display:table;
        }
        .group:after {
            clear:both;
        }
        .group {
            zoom:1; 
        }


        .col {
            display: block;
            float:left;
            margin: 1% 0 1% 1.6%;
        }

        .col:first-child { margin-left: 0; }

        @media only screen and (max-width: 480px) {.col {  margin: 1% 0 1% 0%;  } }
        .span_4_of_4 {
            width: 100%; 
        }

        .span_3_of_4 {
            width: 74.6%; 
        }

        .span_2_of_4 {
            width: 49.2%; 
        }

        .span_1_of_4 {
            width: 23.5%;
            text-align: center;
            border: 1px solid #ccc;
            min-height: 323px;
        }


        /*  GO FULL WIDTH AT LESS THAN 480 PIXELS */

        @media only screen and (max-width: 480px) {
            .span_4_of_4 {
                width: 100%; 
            }
            .span_3_of_4 {
                width: 100%; 
            }
            .span_2_of_4 {
                width: 100%; 
            }
            .span_1_of_4 {
                width: 100%; 
            }
        }
    </style>";
        $i=0;
        //print_r($data);

        foreach ($data as $amazon_ad) {
                if ($i == 0 || $i%4==0){
                    $html .= "<div class='section group'>";
                }
                $html .= '<div class="col span_1_of_4">
                    <a href="'.$amazon_ad['url'].' target="_blank"><img src="'.$amazon_ad['image'].'"><p>'.$amazon_ad['title'].'</p></a>
                </div>';

              

               $i++;
               if ($i % 4 == 0){
                   $html .= "</div>"; // <div class="row"> closes here on every fourth entry
               }
            
        }
       
        return $html;

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
       
        $id = (int) $request['id'];
        $post = get_post( $id );

        if ( empty( $post ) ) {
            return rest_ensure_response( array() );
        }

        $response = $this->prepare_item_for_response( $post, $request );

        // Return all of our post response data.
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
            $post_data['id'] = (int) $post->ID;
        }

        if ( isset( $schema['properties']['content'] ) ) {
            $post_data['content'] = $this->ad_network_filter($post->post_content);
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
            'title'                => 'post',
            'type'                 => 'object',
            // In JSON Schema you can specify object properties in the properties attribute.
            'properties'           => array(
                'id' => array(
                    'description'  => esc_html__( 'Unique identifier for the object.', 'my-textdomain' ),
                    'type'         => 'integer',
                    'context'      => array( 'view', 'edit', 'embed' ),
                    'readonly'     => true,
                ),
                'content' => array(
                    'description'  => esc_html__( 'The content for the ad.', 'my-textdomain' ),
                    'type'         => 'string',
                ),
            ),
        );

        return $schema;
    }

    // Sets up the proper HTTP status code for authorization.
    public function authorization_status_code() {

        $status = 401;

        if ( is_user_logged_in() ) {
            $status = 403;
        }

        return $status;
    }

        public function post_item( $request ) {
            if (! isset($request['id'])) {
                 new WP_Error( 'rest_bad_request', __( "Publisher id Not Specified" ) );
            }

          $data = $this->extract_post_data( $request );
          
          // Put the ad data in to the rotation
            if ( is_wp_error( $data ) ) {
                return $data;
            }
            $return = array();
            if ( isset( $data['ID'] ) ) {
                error_log("Data ID:{$data['ID']}");
                $publisher_id = (int)$request['id'];
                error_log("Publisher_ID = $publisher_id");
                $publisher = get_post($publisher_id);
                $ad_title = $publisher->post_title;
                $ad_title .= ": ".$data['post_title'];
                $ad_content = base64_decode($data['content']);
                if(isset($data['ad_type_slugs'])){
                    $ad_type_slugs = $data['ad_type_slugs'];
                }else{
                    $ad_type_slugs = '';
                }               
                $custom_field = $data['custom_field'];
                if (update_publisher_ad($data['ID'],$ad_title,$ad_content,$publisher_id,$ad_type_slugs,$custom_field)) {
                    $return['ID'] = $data['ID'];
                    $response = rest_ensure_response( $return );
                    $response->set_status( 200 );
                    $response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace , $this->resource_name , $return['ID'] ) ) );
                } else {
                    $response = rest_ensure_response( $return );
                    $response->set_status( 400 );
                }
            } else {
                $publisher_id = (int)$request['id'];
                $publisher = get_post($publisher_id);
                $ad_title = $publisher->post_title;
                $ad_title .= ": ".$data['post_title'];
                $ad_content = base64_decode($data['content']);
                if(isset($data['ad_type_slugs'])){
                    $ad_type_slugs = $data['ad_type_slugs'];
                }else{
                    $ad_type_slugs = '';
                }
                $custom_field = $data['custom_field'];
                $return['ID'] = create_publisher_ad($ad_title,$ad_content,$publisher_id,$ad_type_slugs,$custom_field);
                if ($return['ID']) {
                    $response = rest_ensure_response( $return );
                    $response->set_status( 201 );
                    $response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace , $this->resource_name , $return['ID'] ) ) );
                }  else {
                    $response = rest_ensure_response( $return );
                    $response->set_status( 400 );
                }
            }

            return $response;

        }

        public function delete_item( $request ) {
            if (! isset($request['id'])) {
                 new WP_Error( 'rest_bad_request', __( "Publisher id Not Specified" ) );
            }
            $data = $this->extract_post_data( $request );
            $response = rest_ensure_response( array() );
            if ( isset( $data['ID'] ) ) {
                $ok = delete_publisher_ad($data['ID'], $request['id']);
            } else if (isset($_GET['ad_id'])) {
                $ad_id = (int) $_GET['ad_id'];
                $ok = delete_publisher_ad($ad_id, $request['id']);
            }
            if ($ok) {
                $response->set_status( 204 );
            } else {
                $response->set_status( 400 );
            }

            return $response;
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
}

// Function to register our new routes from the controller.
function adz_cpt_ads_register_my_rest_routes() {
    $controller = new WP_REST_Ads_Controller();
    $controller->register_routes();
}

add_action( 'rest_api_init', 'adz_cpt_ads_register_my_rest_routes' );
