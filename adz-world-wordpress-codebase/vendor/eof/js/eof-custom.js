jQuery(document).ready(function ($) {

	$("select[name*='rotation_pages']").chosen({
		placeholder_text_multiple : 'Select Pages',
		hide_results_on_select : false,
		
	});

	$("select[name*='rotation_categories']").chosen({
		placeholder_text_multiple : 'Select Categories',
		hide_results_on_select : false,
		
	});

	$("select[name*='rotation_tags']").chosen({
		placeholder_text_multiple : 'Select Tags',
		hide_results_on_select : false,
	
	});
	$("#adz_ad_options_exclude_pages_from_rotation").chosen({
		placeholder_text_multiple : 'Select Tags',
		hide_results_on_select : false,
	
	});

	$("input[name*='adz_rotation']").each(function(){
		
		if( $(this).is(':checked') == true && $(this).val() == 'random'){
			$(this).closest('td').next('td').find('select').prop("disabled",true);
			$(this).closest('td').next('td').find('select').val("");
		}else{
			$(this).closest('td').next('td').find('select').prop("disabled",false);
		}
	});

    $(document).on('change',"input[name*='adz_rotation']",function(){ 

		if($(this).val() == 'random'){
			$(this).closest('td').next('td').find('select').prop("disabled",true);
			$(this).closest('td').next('td').find('select').val("");
		}else{
			$(this).closest('td').next('td').find('select').prop("disabled",false);
		}
	});



	$("input[name*='loop']").each(function(){
		if($(this).is(':checked') == true){
			$(this).closest('td').next('td').find('input').prop("disabled",true);
		}else{
			$(this).closest('td').next('td').find('input').prop("disabled",false);
		}
	});
	$(document).on('change',"input[name*='loop']",function(){
		if($(this).is(':checked') == true){
			$(this).closest('td').next('td').find('input').prop("disabled",true);
			$(this).closest('td').next('td').find('input').val("");
		}else{
			$(this).closest('td').next('td').find('input').prop("disabled",false);
		}
	});

	

	
	var EOF_settings = {
		init: function() {
			this.color();
			this.media();
			this.image_select();
			this.repeat();
			this.date();
			this.eof_port();
		},
		/*=Color Picker Field */
		color: function() {
			// Add Color Picker to all inputs that have 'color-field' class
			// $( '.eof-color-picker' ).wpColorPicker();
		},
		/*=Date Picker Field */
		date: function() {
			//$('.eof-datepicker').datepicker();	
		},
		/*=Media Uploader Field  */
		media: function() {

			var custom_uploader;
			var $url_input = '';

			$('body').on('click', '.eof-media-delete-button', function(e) {
				e.preventDefault();

				$url_input = $('#' + $(this).data('input-id'));
				$url_input.val('');
				$(this).closest('td').find('.image-preview').remove();
			});

			$('body').on('click', '.eof-media-button', function(e) {
				e.preventDefault();
				
				$url_input = $('#' + $(this).data('input-id'));

				if( undefined !== custom_uploader ) {
					custom_uploader.open();
					return;
				}

				// Create the media frame.
				custom_uploader = wp.media.frames.file_frame = wp.media({
					frame: 'post',
					state: 'insert',
					multiple: false
				});
				//When an image is selected, run a callback.
				custom_uploader.on('insert', function() {
					var selection = custom_uploader.state().get('selection');
					selection.each( function( attachment, index ) {
						attachment = attachment.toJSON();
						if( 0 === index ) {
							//Place first attachment in field
							$url_input.val( attachment.url );
						} else {
							//Create a new row for all additional attachments
							// @todo
						}
					});
					
				});

				custom_uploader.open();

			});

			//var custom_uploader, $url_input = '';
		},
		image_select: function() {

			var syncClassChecked = function( img ) {
				var radioName = img.prev('input[type="radio"]').attr('name');

				$('input[name="' + radioName + '"]').each(function() {
					// Define img by radio name.
					var myImg = $(this).next('img');

					// Add / Remove Checked class.
					if ( $(this).prop('checked') ) {
						myImg.addClass('item-selected wp-ui-highlight');
					} else {
						myImg.removeClass('item-selected wp-ui-highlight');
					}
				});
			}

			$('input.radioImageSelect').each(function(e) {
				$(this)
					// First all we are need to hide the radio input.
					.hide()
					// And add new img element by data-image source.
					.after('<img src="' + $(this).data('image') + '" alt="radio image" />');

				// Define the new img element.
				var img = $(this).next('img');
				// Add item class.
				img.addClass('radio-img-item');

				// When we are created the img and radio get checked, we need add checked class.
				if ( $(this).prop('checked') ) {
					img.addClass('item-selected wp-ui-highlight');
				}

				// Create click event on img element.
				img.on('click', function(e) {
					$(this)
						// Prev to current radio input.
						.prev('input[type="radio"]')
						// Set checked attr.
						.prop('checked', true)
						// Run change event for radio element.
						.trigger('change');

					// Firing the sync classes.
					syncClassChecked($(this));
				} );
			});
		},
		repeat: function() {
			var $parent = $('.eof-repeat-table-wrap');
			$parent.on('click', '.add-row', function(event) {
				$("select[name*='rotation_pages']").chosen('destroy');
				$("select[name*='rotation_categories']").chosen('destroy');
				$("select[name*='rotation_tags']").chosen('destroy');
				event.preventDefault();
				var count = $(this).data('count');
				var $new_item = $parent.find('tr.clone').clone().removeAttr('class').removeAttr('style');
				var $item = $new_item.wrap('<div/>').parent().html().replace(/field_count/g, count);
				$parent.find('tbody').append($item);
				count += 1;
				$(this).data('count', count);

				

				$("select[name*='rotation_pages']").chosen({
					placeholder_text_multiple : 'Select Pages',
					hide_results_on_select : false,
				});

				$("select[name*='rotation_categories']").chosen({
					placeholder_text_multiple : 'Select Categories',
					hide_results_on_select : false,
				});

				$("select[name*='rotation_tags']").chosen({
					placeholder_text_multiple : 'Select Tags',
					hide_results_on_select : false,
				});

			});

			$parent.on('click', 'tbody tr .item-action .remove_row', function(event) {
				event.preventDefault();
				$(this).closest('tr').remove();
			});

			$parent.on('click', 'tbody tr .item-action .duplicate_row', function(event) {
				event.preventDefault();
				var $tr = $(this).closest('tr');

				var count = $('.add-row').data('count');
				var clone_count = $(this).data('number');

				var $new_item = $tr.clone().removeAttr('class').removeAttr('style');
				var re = new RegExp(clone_count,"g");
				var $item = $new_item.wrap('<div/>').parent().html().replace(re, count);
				$parent.find('tbody').append($item);
				count += 1;
				$('.add-row').attr('data-count', count);


				//var $clone = $tr.clone();
				  // $clone.find(':text').val('');
				//$tr.after($clone);
				//$(this).closest('tr').clone();
			});
		},
		/* import and export option settings */
		eof_port: function() {

		}
	};
	EOF_settings.init();
});

function reset_registration(){
	var conf = confirm('Are you sure ?');
	var adz_nonce_value = jQuery('#adz_nonce').val();
	if(conf === true){
		var current_url = window.location.href;
		jQuery.ajax({
	        url: "/wp-admin/admin-ajax.php",
	        type : 'post',
	        data: {
	            action :'adz_reset_registration',
	            adz_nonce : adz_nonce_value,
	        },
	        success:function(data) {
	        	if(jQuery.trim(data) == 'continue'){
	        		window.location = current_url;
	        	}
							
	        },
	        error: function(errorThrown){
	            console.log(errorThrown);
	        }
	    });
	}
}