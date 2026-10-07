/*Javascript for admin*/
jQuery(document).ready(function(){
	jQuery('#adz_template_list').change(function(){
		var selected_template = jQuery(this).val();
		jQuery('.loading_data').show();
		jQuery.ajax({
	        url: "/wp-admin/admin-ajax.php",
	        type : 'post',
	        data: {
	            action :'get_template_settings',
	            selected_template : selected_template,
	        },
	        success:function(data){
	        	
	        	var obj = JSON.parse(data);

	        	jQuery('#header_text').val(obj.header_text);
	        	jQuery('#footer_text').val(obj.footer_text);
	        	if(obj.header_style == 'right'){
	        		jQuery('#header_right').prop( "checked", true );
	        	}
	        	if(obj.header_style == 'left'){
	        		jQuery('#header_left').prop( "checked", true );
	        	}
	        	if(obj.header_style == 'center'){
	        		jQuery('#header_center').prop( "checked", true );
	        	}
	        	if(obj.footer_style == 'right'){
	        		jQuery('#footer_right').prop( "checked", true );
	        	}
	        	if(obj.footer_style == 'left'){
	        		jQuery('#footer_left').prop( "checked", true );
	        	}
	        	if(obj.footer_style == 'center'){
	        		jQuery('#footer_center').prop( "checked", true );
	        	}
	        	jQuery('.loading_data').hide();
	        					
	        },
	        error: function(errorThrown){
	            console.log(errorThrown);
	        }
	    });
	});
});
