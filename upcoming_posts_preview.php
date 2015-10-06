<?php
/*
Plugin Name: Upcoming Posts Previews
Plugin URI:  http://www.simoncodrington.com.au/plugins/upcoming_post_preview
Description: Gives you the ability to make your upcoming posts partially visibile. Determine on a post by post basis if your article will show some of its  content as a preview of whats to come. Each preview also displays its associated user profile. The shortcode [upcoming_post_preview] is used to display this on your page or you could echo out the shortcode in your themes blog landing page
Version:     1.0.0
Author:      Simon Codrington
Author URI:  http://simoncodrington.com.au
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
*/




class sc_upcoming_preview_posts{
	
	//call on initialisation 
	public function __construct(){
	
		add_action('post_submitbox_misc_actions', array($this,'add_post_preview_meta_options'), 10, 1);
		add_action('admin_enqueue_scripts', array($this,'enqueue_admin_scripts_and_styles'), 10, 1);
		add_action('wp_enqueue_scripts', array($this,'enqueue_public_scripts_and_styles'), 10, 1);
		add_action('save_post', array($this, 'save_post'), 10, 2);
		add_action('init', array($this,'register_shortcodes'), 10, 1);
	}
	
	//enqueue admin scripts and styles 
	public function enqueue_admin_scripts_and_styles(){
		wp_enqueue_style('upcoming-preview-admin-styles', plugin_dir_url(__FILE__ ) . '/css/upcoming_posts_preview_admin_styles.css');
		wp_enqueue_script('upcoming-preview-admin-scripts', plugin_dir_url(__FILE__ ) . '/js/upcoming_posts_preview_admin_scripts.js', array('jquery'));
	}

	public function enqueue_public_scripts_and_styles(){
		wp_enqueue_style('upcoming-preview-public-styles', plugin_dir_url(__FILE__ ) . '/css/upcoming_posts_preview_public_styles.css');
	}
	
	//Registers shortcodes for use (to display our upcoming preview)
	public function register_shortcodes(){
		add_shortcode('upcoming_post_preview', array($this,'shortcode_display'));
	}
	
	//handles the processing / display for our shortcodes
	public function shortcode_display($atts, $contet = '', $tag){
		
		$html = '';
		//our main upcoming preview shortcode
		if($tag == 'upcoming_post_preview'){
				
			//define default arguments
			$arguments = shortcode_atts(array(
				'number_of_previews'	=> 1,
				'post_id'				=> false
				), $atts, $tag);
				
			//build output
			$html .= $this->get_upcoming_post_preview($arguments);
		}
		
		return $html;
	}
	
	//output function for the preview
	public function get_upcoming_post_preview($optional_arguments = array()){
		
		$html = '';
		
		//collect and merge additional arguments
		$arguments = array(
			'number_of_previews'	=> 1,
			'post_id'				=> false
		);
		
		//merge optional passed in arguments
		if($optional_arguments){
			foreach($optional_arguments as $key => $value){
				if(array_key_exists($key, $arguments)){
					$arguments[$key] = $value;
				}
			}
		}
		
		//main query to find posts
		$post_args = array(
			'post_type'			=> 'post',
			'post_status'		=> 'future',
			'orderby'			=> 'post_date',
			'order'				=> 'DESC',
			'posts_per_page'	=> $arguments['number_of_previews'],
			'include'			=> $arguments['post_id'],
			'meta_key'			=> 'upcoming_preview_status',
			'meta_value'		=> 'true'
		);

		$posts = get_posts($post_args);
		if($posts){
			
			global $post;
			foreach($posts as $post){
				
				setup_postdata($post);
				$post_id = get_the_ID();

				//get post info
				$post_title = get_the_title();
				$post_permalink = get_permalink();
				
				//get author info
				$post_author = get_the_author();
				$post_author_id = get_the_author_meta('ID');
				$post_author_name = get_the_author_meta('display_name');
				$post_author_description = get_the_author_meta('description');
				$post_author_post_count = count_user_posts($post_author_id);
				$post_author_avatar = get_avatar($post_author_id, 48, '', $post_author, array('class' => 'alignleft'));
				$post_author_url = get_author_posts_url($post_author_id);
				$post_author_url_text = apply_filters('sc_upcoming_post_author_readmore_text', 'View ' . $post_author_name . '\'s other articles', $post);
				
				
				//get the date elements (post date, publish date, date differences)
				$post_publish_date = new DateTime(get_the_date('l, F j, Y g:i'));
				$post_todays_date = new DateTime(date('l, F j, Y g:i'));
				$post_difference_date = $post_publish_date->diff($post_todays_date);
					
				//collect settings for the upcoming preview
				$upcoming_preview_type = get_post_meta($post_id,'upcoming_preview_type', true);

				//show X number of words as set by the user
				if($upcoming_preview_type == 'upcoming_preview_x_words'){

					//build user engagement
					$readmore = $this->get_readmore_element();
					
					//get number of words we want to show
					$upcoming_preview_x_words_count = get_post_meta($post_id, 'upcoming_preview_x_words_number', true);
					$post_content = wp_trim_words(get_the_content(), $upcoming_preview_x_words_count , $readmore);
					
				}else if($upcoming_preview_type == 'upcoming_preview_more_tag'){
					
					//customise the text displayed on the standard 'more' link for the front end
					add_filter('the_content_more_link', array($this, 'customise_readmore_action'));
					$post_content = get_the_content();
					
				}
				
				//get all of our applicable post classes (to help for styling etc)
				$postClass = ((get_post_class()) ? implode(' ', get_post_class()) : ''); 
				 
				//built output
				$html .= '<article class="upcoming-post-preview ' . $postClass . '">';
				$html	.= '<header class="entry-header">';
				$html 		.= '<h1 class="post-title"><small>Upcoming Article</small> - ' . $post_title . '</h1>';
				$html 		.= '<h3 class="post-live-date">';
				$html			.=  $post_publish_date->format('l, F j, Y');
				$html		.= '</h3>';
				$html		.= '<h4 class="post-time-left">(' . $post_difference_date->d . ' days : ' . $post_difference_date->h . ' hours away)</h4>';
				$html	.= '</header>';
				$html	.= '<section class="entry-content">';
				$html 		.= '<div class="post-content">' . $post_content . '</div>';
				$html   	.= '<hr/>';
				$html 		.= '<aside class="author-content">';
				$html			.= '<h4><small>Author:</small> ' . $post_author_name . '</h4>'; 
						
				//if the author has an avatar (gravatar) display it			
				if($post_author_avatar){
				$html .= $post_author_avatar; 	
				}
				
				$html			.= '<p>' . $post_author_description . '</p>';
				//if the author has more than one other article
				if($post_author_post_count > 1){
				$html			.= '<a href="' . $post_author_url . '" title="' . $post_author_url_text .'">' . $post_author_url_text .'</a>';
				}
				$html 		.= '</aside>';
				$html	.= '</section>';
				$html .= '</article>';
					
				

				wp_reset_postdata();
			}
		}
		
		
		return $html;
	}

	//wrapper to display the upcoming post previews
	public function display_upcoming_post_previews($optional_arguments = array()){
		$html = $this->get_upcoming_post_preview($optional_arguments);
		echo $html;
	}


	//customises the 'more' link displayed when we choose to display our previews 
    public function customise_readmore_action($more_link){
    		
    	global $post;
		
		//determine if we want a post preview
		$upcoming_preview_status = get_post_meta($post->ID,'upcoming_preview_status', true);
		if($upcoming_preview_status == 'true'){
			//determine if we have set the option to display via 'more' tag
			$upcoming_preview_type = get_post_meta($post->ID,'upcoming_preview_type', true);
			
			//build a pretty readmore link for user engagement
			if($upcoming_preview_type == 'upcoming_preview_more_tag'){
				$more_link = $this->get_readmore_element();
			}
		}
		
		return $more_link;
    }
	
	//creates the 'read more' element displayed after the 'more' tag or after X number of words are displayed on a post preview
	public function get_readmore_element(){

		global $post; 

		$html = '';
		
		//get default action and text to display (and allow filters)
		$upcoming_post_link_action = apply_filters('sc_upcoming_post_link_action', 'mailto:contact@mywebsite.com.au', $post);
		$upcoming_post_link_text = apply_filters('sc_upcoming_post_link_text', 'Get in contact and we will update you when this post is published!', $post);

		$html .= '<div class="readmore-link">';
		$html .= '<a href="' . $upcoming_post_link_action .'" title="' . $upcoming_post_link_text . '">' . $upcoming_post_link_text . '</a>';
		$html .= '</div>';
		
		return $html; 
	}
	
	//defines the options for our post preview (displayed inside the publish post meta box)
	public function add_post_preview_meta_options($post){
			
		global $post, $post_type;
		
		// Add a nonce field (for security)
		wp_nonce_field( 'sc_upcoming_nonce', 'sc_upcoming_nonce_field' );
		
		//determine if we are on a post 
		if('post' == $post_type){
			
			//collect settings
			$upcoming_preview_status = (get_post_meta($post->ID,'upcoming_preview_status',true) ? get_post_meta($post->ID,'upcoming_preview_status',true) : 'false');
			$upcoming_preview_type = (get_post_meta($post->ID,'upcoming_preview_type', true) ? get_post_meta($post->ID,'upcoming_preview_type', true) : 'false');
			$upcoming_preview_type_x_words = (get_post_meta($post->ID,'upcoming_preview_x_words_number', true) ? get_post_meta($post->ID,'upcoming_preview_x_words_number', true) : '');
		
			?>
			<div class="misc-pub-section">
				<div class="set-upcoming-preview">
					<span class="title">Upcoming Preview:</span>
					
					<!--Upcoming post preview on / off option-->
					<div class="content-options ">
						<input class="value" type="radio" name="upcoming_preview_status" id="coming_preview_disabled" value="false" <?php if($upcoming_preview_status == 'false'){ echo 'checked';}?>/>
						<label for="coming_preview_disabled"> Disable upcoming preview</label><br/>
						<input class="value" type="radio" name="upcoming_preview_status" id="coming_preview_enabled" value="true" <?php if($upcoming_preview_status == 'true'){ echo 'checked';}?>/>
						<label for="coming_preview_enabled"> Enable upcoming preview</label>
						
						<!--Upcoming post preview display settings-->
						<div class="content-settings <?php echo ($upcoming_preview_status == 'true' ? 'active' : ''); ?>">
							
							<?php
							//action, execute code before we display main options
							do_action('sc_upcoming_post_admin_form_start', $post);
							?>
							<input class="value" type="radio" name="upcoming_preview_type" id="upcoming_preview_more_tag" value="upcoming_preview_more_tag" <?php if($upcoming_preview_type == 'upcoming_preview_more_tag'){ echo 'checked';}?>/>
							<label for="upcoming_preview_more_tag">Show post preview up to the 'more' tag </label>
							<br/>
							
							<input class="value" type="radio" name="upcoming_preview_type" id="upcoming_preview_x_words" value="upcoming_preview_x_words" <?php if($upcoming_preview_type == 'upcoming_preview_x_words'){ echo 'checked';}?>/>
							<label for="upcoming_preview_x_words">Show X number of Words</label>
							<!--Upcoming post preview, number of words to display-->
							<div class="content-sub-settings <?php echo ($upcoming_preview_type == 'upcoming_preview_x_words' ? 'active' : ''); ?>">
								<label for="upcoming_preview_x_words_number">Number of words</label>
								<input type="number" class="widefat" name="upcoming_preview_x_words_number" id="upcoming_preview_x_words_number" value="<?php echo $upcoming_preview_type_x_words; ?>"/>
							</div>
							
							<?php
							//action, execute code after we display main options
							do_action('sc_upcoming_post_admin_form_end', $post);
							?>
						</div>
						
					</div>
				</div>
			</div>
		<?php
		}
	}
	
	//call when we save our posts (so we can save our post preview status / options)
	public function save_post($post_id, $post){
		
		global $post_type;
		
		//only save for 'posts'
		if($post_type == 'post'){
			
			
			
			//check if we have nonce set
			if(!isset($_POST['sc_upcoming_nonce_field'])){
				return;
			}
			
			//verify nonce
			if(!wp_verify_nonce($_POST['sc_upcoming_nonce_field'], 'sc_upcoming_nonce')){
				return;
			}
			//check autosave
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}
			
			//action, called as we are saving, used to save any custom fields added to the admin
			do_action('sc_upcoming_post_admin_save', $post_id);
			
			//good to save
			//collect settings
			$upcoming_preview = isset($_POST['upcoming_preview_status']) ? $_POST['upcoming_preview_status'] : '';
			$upcoming_preview_type = isset($_POST['upcoming_preview_type']) ? $_POST['upcoming_preview_type'] : '';
			$upcoming_preview_type_x_words = isset($_POST['upcoming_preview_x_words_number']) ? $_POST['upcoming_preview_x_words_number'] : '';
			
			//update meta
			update_post_meta($post_id,'upcoming_preview_status',$upcoming_preview);
			update_post_meta($post_id,'upcoming_preview_type',$upcoming_preview_type);
			update_post_meta($post_id,'upcoming_preview_x_words_number', $upcoming_preview_type_x_words);
		}
	
	}
	
}
$sc_upcoming_preview_posts = new sc_upcoming_preview_posts();
?>