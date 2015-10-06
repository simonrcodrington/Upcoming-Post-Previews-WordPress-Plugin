/*
 * Upcoming Posts Preview Admin Scripts
 * Handles the interactivity with the admin components of the post
 */
jQuery(document).ready(function($){
	
	
	//containers
	var preview_container = $('.set-upcoming-preview');
	var preview_content_settings = preview_container.find('.content-settings');
	var preview_content_sub_settings = preview_container.find('.content-sub-settings');
	
	//elements
	var preview_status = preview_container.find('[name="upcoming_preview_status"]');
	var preview_types = preview_container.find('[name="upcoming_preview_type"]');
	var preview_x_number_of_items = preview_container.find('[name="upcoming_preview_x_words_number"]')

	
	//toggles the main enabled / disabled
	function upcoming_post_preview_toggle_display(){
		
		//get current state, if we have enabled it, slide down else slide up
		chosen_preview_status = preview_status.filter(':checked').val();
		if(chosen_preview_status == 'true'){
			preview_content_settings.slideDown('fast');
		}else if(chosen_preview_status == 'false'){
			preview_content_settings.slideUp('fast');
			//reset the X number of characters to display 
			preview_container.find('')
		}
		
	}
	upcoming_post_preview_toggle_display();
	
	//Toggling the main settings (display up to more tag or X number of words)
	function upcoming_post_preview_setting_display(){
		
		//get the currently chosen option setting
		if(preview_types.length != 0){
			var chosen_preview_type = preview_types.filter(':checked').val();
			if(chosen_preview_type == 'upcoming_preview_more_tag'){
				preview_content_sub_settings.slideUp('fast');
				preview_x_number_of_items.removeAttr('required');
			}else if(chosen_preview_type == 'upcoming_preview_x_words'){
				preview_content_sub_settings.slideDown('fast');
				//set the number of words field to have the 'required' attribute
				preview_x_number_of_items.attr('required','required');
			}
		}	
	}
	upcoming_post_preview_setting_display();
	
	
	//when we change status, toggle container accordingly
	preview_status.on('click', upcoming_post_preview_toggle_display);
	
	//when we choose our upcoming preview settings (after we select enabled)
	preview_types.on('click', upcoming_post_preview_setting_display);
	
});
