<?php
class TWRecentPostWidget extends WP_Widget {
    function TWRecentPostWidget() {
        $widget_ops = array('classname' => 'TWRecentPostWidget', 'description' => 'joinwebs recent posts.');
        parent::WP_Widget(false, 'joinwebs recent posts', $widget_ops);
    }
    function widget($args, $instance) {
        global $post;
        extract(array(
            'title' => '',
            'number_posts' => 5,
            'theme' => 'post_nothumbnailed',
            'post_order' => 'latest',
            'post_type' => 'post'
        ));
        extract($args);
        $title = apply_filters('widget_title', $instance['title']);
        $post_count = 5;
        if (isset($instance['number_posts']))
            $post_count = $instance['number_posts'];
        $q['posts_per_page'] = $post_count;
        $cats = (array) $instance['post_category'];
        $q['paged'] = 1;
        $q['post_type'] = $instance['post_type'];
        if (count($cats) > 0) {
            $typ = 'category';
	    if ($instance['post_type'] != 'post')
		$typ = 'catalog';
            $catq = '';
            $sp = '';
            foreach ($cats as $mycat) {
                $catq = $catq . $sp . $mycat;
                $sp = ',';
            }
            $catq = explode(',', $catq);
            $q['tax_query'] = Array(Array(
                    'taxonomy' => $typ,
                    'terms' => $catq,
                    'field' => 'id'
                )
            );
        }
        if ($instance['post_order'] == 'commented')
		
			
		$current = -1;
		$featuredCurrent = 0;
						
        query_posts($q);
        if (isset($before_widget))
            echo $before_widget;
        if ($title != '')
            echo $args['before_title'] . $title . $args['after_title'];
        echo '<div class="jw-recent-posts-widget">';
        echo '<ul>';
        while ( have_posts() ) : the_post();
		if ($instance['post_order'] == 'commented'){
           $featured_post = get_post_meta($post->ID, 'featured_post', true);
		   if($featured_post == "1") {  
		   ?>
				<li class="widget-ad-list ad-box featurads-widget">

											
					<?php require_once(TEMPLATEPATH . '/inc/BFI_Thumb.php'); ?>

						<?php 


                            if(get_post_meta($post->ID, 'quicket_thumbnail_url', true) ){
                                $thumb_url = get_post_meta($post->ID, 'quicket_thumbnail_url', true);
                                echo "<img alt='image' class='widget-ad-image' src='" . $thumb_url . "'/>";
                            }
                            else{
                                $thumb_id = get_post_thumbnail_id();
                                $thumb_url = wp_get_attachment_image_src($thumb_id,'thumbnail-size', true);

                                $params = array( 'width' => 300, 'height' => 250, 'crop' => true );
                                echo "<img alt='image' class='widget-ad-image' src='" . bfi_thumb( "$thumb_url[0]", $params ) . "'/>";
                            }

													
						?>
						
						<div class="ad-hover-content">
						<a href="<?php the_permalink(); ?>" style="padding:125px 18px">						
						<?php $post_price = get_post_meta($post->ID, 'post_price', true); ?>
						<span class="add-price"><?php echo $post_price; ?></span>
							</a>
						</div>
						

				</li>
			<?php
		   }
		}else{
            ?>
			<li class="widget-ad-list latestads-widget">

									
				<?php require_once(TEMPLATEPATH . '/inc/BFI_Thumb.php'); ?>

				<?php 

                            if(get_post_meta($post->ID, 'quicket_thumbnail_url', true) ){
                                $thumb_url = get_post_meta($post->ID, 'quicket_thumbnail_url', true);
                                echo "<img alt='image' class='widget-ad-image' src='" . $thumb_url . "'/>";
                            }
                            else{
                                $thumb_id = get_post_thumbnail_id();
                                $thumb_url = wp_get_attachment_image_src($thumb_id,'thumbnail-size', true);

                                $params = array( 'width' => 300, 'height' => 250, 'crop' => true );
                                echo "<img alt='image' class='widget-ad-image' src='" . bfi_thumb( "$thumb_url[0]", $params ) . "'/>";
                            }
											
				?>

					<div class="widget-ad-list-content">

					<span class="widget-ad-list-content-title"><a href="<?php the_permalink(); ?>"><?php the_titlesmall('', '...', true, '20') ?></a></span>

					<?php $post_price = get_post_meta($post->ID, 'post_price', true); ?>
					<p class="add-price"><?php echo $post_price; ?></p>
					<?php  the_excerpt(custom_excerpt_length('10')); ?>

					</div>

			</li>
			<?php
			}
        endwhile;
        echo '</ul>';
        echo '</div>';
        if (isset($after_widget))
            echo $after_widget;
        wp_reset_query();
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        /* Strip tags (if needed) and update the widget settings. */
        $instance['title'] = strip_tags($new_instance['title']);
        if ($new_instance['post_type'] == 'post') {
	    $instance['post_category'] = $_REQUEST['post_category'];
	} else {
	    $tax = get_object_taxonomies($new_instance['post_type']);
	    $instance['post_category'] = $_REQUEST['tax_input'][$tax[0]];
	}
        $instance['number_posts'] = strip_tags($new_instance['number_posts']);
        $instance['post_type'] = strip_tags($new_instance['post_type']);
        $instance['post_order'] = strip_tags($new_instance['post_order']);
        $instance['theme'] = strip_tags($new_instance['theme']);
        return $instance;
    }

    function form($instance) {
        //Output admin widget options form
        extract(shortcode_atts(array(
                    'title' => '',
                    'theme' => 'post_nothumbnailed',
                    'number_posts' => 5,
                    'post_order' => 'latest',
                    'post_type' => 'post'
                        ), $instance));
        $defaultThemes = Array(
            Array("name" => 'Thumbnailed posts', 'user_func' => 'post_thumbnailed'),
            Array("name" => 'Default posts', 'user_func' => 'post_nonthumbnailed')
        );
        $themes = apply_filters('jw_recent_posts_widget_theme_list', $defaultThemes);
        $defaultPostTypes = Array(Array("name" => 'Post', 'post_type' => 'post')); ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e("Title:", "joinwebs");?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $title; ?>"  />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('post_order'); ?>">Post order:</label>
            <select class="widefat" id="<?php echo $this->get_field_id('post_order'); ?>" name="<?php echo $this->get_field_name('post_order'); ?>">
                <option value="latest" <?php if ($post_order == 'latest') print 'selected="selected"'; ?>>Latest Ads</option>
                <option value="commented" <?php if ($post_order == 'commented') print 'selected="selected"'; ?>>Featured Ads</option>
            </select>
        </p>
       <?php 
        $customTypes = apply_filters('jw_recent_posts_widget_type_list', $defaultPostTypes);
        if (count($customTypes) > 0) { ?>
            <p style="display: none;">
                <label for="<?php echo $this->get_field_id('post_type'); ?>">Post from:</label>
                <select rel="<?php echo $this->get_field_id('post_cats'); ?>" onChange="jw_get_post_terms(this);" class="widefat" id="<?php echo $this->get_field_id('post_type'); ?>" name="<?php echo $this->get_field_name('post_type'); ?>"><?php
                    foreach ($customTypes as $postType) { ?>
                        <option value="<?php print $postType['post_type'] ?>" <?php echo selected($post_type, $postType['post_type']); ?>><?php print $postType['name'] ?></option><?php
                    } ?>
                </select>
            </p><?php
        } ?>
        <p>If you were not selected for cats, it will show all categories.</p>
        <div id="<?php echo $this->get_field_id('post_cats'); ?>" style="height:150px; overflow:auto; border:1px solid #dfdfdf;"><?php
            $post_type='post';
            $tax = get_object_taxonomies($post_type);

            $selctedcat = false;
            if (isset($instance['post_category']) && $instance['post_category'] != ''){
                $selctedcat = $instance['post_category'];
            }
            wp_terms_checklist(0, array('taxonomy' => $tax[0], 'checked_ontop' => false, 'selected_cats' => $selctedcat)); ?>
        </div>
        <p>
            <label for="<?php echo $this->get_field_id('number_posts'); ?>">Number of posts to show:</label>
            <input  id="<?php echo $this->get_field_id('number_posts'); ?>" name="<?php echo $this->get_field_name('number_posts'); ?>" value="<?php echo $number_posts; ?>" size="3"  />
        </p><?php
    }
}
add_action('widgets_init', create_function('', 'return register_widget("TWRecentPostWidget");'));
add_action('wp_ajax_themewave_recent_post_terms', 'get_post_type_terms');
function get_post_type_terms() {
    $cat = 'post';
    if (isset($_REQUEST['post_format']) && $_REQUEST['post_format'] != '')
        $cat = $_REQUEST['post_format'];
    $tax = get_object_taxonomies($cat);
    wp_terms_checklist(0, array('taxonomy' => $tax[0], 'checked_ontop' => false, 'selected_cats' => false));
    die;
} ?>