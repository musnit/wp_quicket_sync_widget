<?php
/*
Plugin Name: Quicket Sync
*/
//register_setting( 'quicket_widget-group', 'quicket_api_key' );

function add_quicket_settings() {
	// Add the section to reading settings so we can add our
	// fields to it
	add_settings_section(
		'quicket_setting_section',
		'Quicket Sync Settings',
		'quicket_setting_section_callback_function',
		'general'
	);
	
	// Add the field with the names and function to use for our new
	// settings, put it in our new section
	add_settings_field(
		'quicket_api_key',
		'Quicket Api Key',
		'quicket_setting_callback_function',
		'general',
		'quicket_setting_section'
	);
	
	// Register our setting so that $_POST handling is done for us and
	// our callback function just has to echo the <input>
	register_setting( 'general', 'quicket_api_key' );
}
function quicket_setting_section_callback_function() {
}
function quicket_setting_callback_function() {
	echo '<input name="quicket_api_key" id="quicket_api_key" type="text" value="'. get_option('quicket_api_key') . '"/>';
}

add_action( 'admin_init', 'add_quicket_settings' );


add_action( 'wp_dashboard_setup', 'add_quicket_widget' );
function add_quicket_widget() {
	wp_add_dashboard_widget(
                 'quicket_widget',         // Widget slug.
                 'Quicket Sync Widget',         // Title.
                 'quicket_widget_function' // Display function.
        );	
}
if ( is_admin() ) {
	add_action( 'wp_ajax_sync_from_quicket', 'sync_from_quicket_callback' );
}
function sync_from_quicket_callback() {
	//global $wpdb; // this is how you get access to the database
	$url = 'http://api.quicket.co.za/api/Events?pageSize=5000&api_key=' . get_option('quicket_api_key');
	$json = file_get_contents($url);
	$parsed = json_decode($json, true);
	$events = $parsed["results"];
	$posts = array_map("convertToPost", $events);
	$all_inserts = array();
	deleteAllQuicketPosts();
	$num_imported = 0;
	foreach ($posts as &$post) {
	    if (strtotime($post["end_date"]) >= time()) {
		    $id = wp_insert_post( $post );
		    update_post_meta( $id, 'post_price', $post["post_price"] );
		    update_post_meta( $id, 'post_location', $post["post_location"] );
		    update_post_meta( $id, 'post_address', $post["post_address"] );
		    update_post_meta( $id, 'post_latitude', $post["post_latitude"] );
		    update_post_meta( $id, 'post_longitude', $post["post_longitude"] );
		    update_post_meta( $id, 'quicket_thumbnail_url', $post["thumbnail_url"] );
		    update_post_meta( $id, 'quicket_id', $post["quicket_id"] );

	       	update_post_meta($id, '_expiration-date', $post["post_expiration_date"]);
	       	$expiration_opts = array();
			$expiration_opts['expireType'] = 'delete';
			$expiration_opts['id'] = $id;
	        update_post_meta($id, '_expiration-date-options', $expiration_opts);
			update_post_meta($id, '_expiration-date-status','saved');

			wp_publish_post($id);

		    $num_imported = $num_imported + 1;
		}
	}

	echo $num_imported;
	echo " events successfully imported!";

	wp_die(); // this is required to terminate immediately and return a proper response
}

function deleteAllQuicketPosts()
{
	 $args = array(
	    'post_type' => 'post',
	    'posts_per_page' => -1,
	    'meta_query' => array(
	    	array(
		        'key' => 'quicket_id',
		        'value'   => array(''),
		        'compare' => 'NOT IN'
	    	)
    	)
	 );

    $the_query = new WP_Query( $args );
	while ( $the_query->have_posts() ) {
		$the_query->the_post();
		wp_delete_post( $the_query->post->ID, true );
	}
}
function convertToPost($event)
{
    $combined_prices = array_reduce($event["tickets"], function($accum, $ticket){
        return($accum . $ticket["name"] . " R" . $ticket["price"] . ", ");
    });
    $combined_prices = rtrim($combined_prices, ', ');
    $combined_address = "";
    if ($event["venue"]["addressLine1"]) {
      $combined_address = $combined_address . $event["venue"]["addressLine1"];
    }
    if ($event["venue"]["addressLine2"]) {
      $combined_address = $combined_address . ", " . $event["venue"]["addressLine2"];
    }

    $date_tag = date('d-m-Y', strtotime($event["startDate"]));
    $end_date = date('d-m-Y', strtotime($event["endDate"]));
    $expiry = strtotime("+7 day", strtotime($event["endDate"]));

    $transformed_categories = array_unique(flattenArray(array_map('convertCategory', $event["categories"])));

    $new_post = array(
        'post_title'        =>      $event["name"],
    	'post_content'      =>      $event["description"],
		'post_category' => 		$transformed_categories,
        'post_price'        =>     $combined_prices,
        'post_location'     =>     $event["locality"]["levelThree"],
        'post_latitude'     =>     $event["venue"]["latitude"],
        'post_longitude'    =>     $event["venue"]["longitude"],
        'post_address'      =>     $combined_address,
        'post_expiration_date'      =>     $expiry,
        'tags_input'        =>     $date_tag,
        'thumbnail_url'        =>     $event["imageUrl"],
        'quicket_id'        =>     $event["id"],
        'end_date'        =>     $end_date
    );
    
    return($new_post);
}

function flattenArray(array $array) {
    $return = array();
    array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
    return $return;
}

function convertCategory($category)
{
    //Concerts-Theatre 154
	//Festivals 134
	//Lifestyle 132
	//Nightlife 123
	//Other 1

	$quicketCategoryIDToHappening = array(
		45 => array(134),
		28 => array(132),
		9 => array(1),
		13 => array(1),
		4 => array(1),
		50 => array(1),
		30 => array(1),
		15 => array(154),
		11 => array(154),
		8 => array(154),
		49 => array(1),
		14 => array(132),
		46 => array(132),
		24 => array(134),
		3 => array(154),
		12 => array(132),
		35 => array(1),
		43 => array(134),
		38 => array(132),
		2 => array(1),
		27 => array(154),
		1 => array(134, 123),
		39 => array(123),
		51 => array(132),
		6 => array(134),
		18 => array(123),
		31 => array(1),
		36 => array(134, 123),
		52 => array(1),
		48 => array(154),
		25 => array(1)
	);
    $categoryID = $category["id"];
	if (array_key_exists($categoryID,$quicketCategoryIDToHappening)){
	    return($quicketCategoryIDToHappening[$categoryID]);
	}
	else{
	    return(1);
	}
    
}

function quicket_widget_function() {
	echo "<div>Clicking the button will delete all events that have been imported from Quicket, and then pull in the latest batch of events from Quicket</div>";
	echo "<button id='quicket-sync'>Click to sync!</button>";
	echo "<script type='text/javascript' >
		jQuery(document).ready(function($) {

			var data = {
				'action': 'sync_from_quicket'
			};

			jQuery('#quicket-sync').on('click', function(){
				alert('Import starting, THIS COULD TAKE UP TO 5 minutes. DON\'T CLOSE THE WINDOW! Click Ok to begin.');
				jQuery.post(ajaxurl, data, function(response) {
					alert('Import completed - ' + response);
				});
			});
		});
	</script>";

}
