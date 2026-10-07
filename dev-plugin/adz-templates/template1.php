<?php 
/*Dynamic template for displaying adz.*/
$template_name = basename(__FILE__);
$setting_data = get_option('template_'.$template_name);
$setting_array = unserialize($setting_data);
//print_r($setting_array);

$style = '<style>		
			#adz_header_container { background:#4A7354; border:1px solid #104A1F;  left:0; position:fixed; width:100%; top:0; }		
			#adz_header_content{  margin:0 auto; width:100%; text-align:center; }
			#adz_container { margin:0 auto; overflow:auto; padding:80px 0; text-align:center; }
			#adz_footer_container { background:#4A7354; border:1px solid #104A1F; bottom:0; height:60px; left:0; position:fixed; width:100%; }
			#adz_footer_content { line-height:60px; margin:0 auto; width:100%; text-align:center !important; }
			.adz_popup #adz_header_container,.adz-world-container #adz_header_container, .adz-world-category-wapper #adz_header_container{ position:inherit;  }
			.adz_popup #adz_footer_container,.adz-world-container #adz_footer_container, .adz-world-category-wapper #adz_footer_container { position:inherit;  }
			.adz_header_text, .adz_footer_text{color:#fff;}
			.adz_timer{font-size:22px;}
			.adz_popup #adz_container { max-height: 600px;overflow-y: scroll; } 
	    </style>';

if($display_type == 'thru_page'){
	
?>
	<head>
	<script type="text/javascript" src="<?php echo site_url();?>/wp-includes/js/jquery/jquery.js"></script>
	<script type="text/javascript">
		jQuery(document).ready(function(){

			var i = <?php echo $ad_visibility;?>;
			var adz_interval = setInterval(function () {
			    if (i >= 0) {
			    	jQuery('.adz_timer').text(" "+i+" ");
			        i--;
			    } else {
			    	jQuery('.continue_reading').text('Continue >>');
			    	jQuery('.continue_reading').addClass('close_adz');	
			    	jQuery('.continue_reading').css('cusrsor','pointer');						       
			    	clearInterval(adz_interval);
			    }
			}, 1000);

			jQuery(document).on('click','.close_adz',function(){
				jQuery.ajax({
			        url: "<?php echo site_url();?>/wp-admin/admin-ajax.php",
			        type : 'post',
			        data: {
			            action :'adz_get_advertise_content',
			            target_type : '<?php echo $target_type;?>',
			            target : '<?php echo $target; ?>',
			            ad_visibility_interval : '<?php echo $ad_visibility_interval; ?>',
			            repeat_times : '<?php echo $repeat_times;?>',
			            close_adz : 'true',

			        },
			        success:function(data) {
			        	if(data != 'continue'){
							location.reload();
						}					
			        },
			        error: function(errorThrown){
			            console.log(errorThrown);
			        }
			    });
			});
		});

	</script>
	<?php echo $style; ?>
	</head>
	<!-- BEGIN: Sticky Header -->
	<div id="adz_header_container">
	    <div id="adz_header_content" style="text-align: <?php echo $setting_array['header_style']; ?>" >
	        <a href="javascript:void()" class="adz_header_text"><div class="continue_reading"><?php echo $setting_array['header_text']; ?> <div> <span class="adz_timer" > </span> seconds</div></div></a>
	    </div>
	</div>
	<!-- END: Sticky Header -->

	<!-- BEGIN: Page Content -->
	<div id="adz_container">
	    <div id="content">
	        <?php

			if (strpos($ad_text, 'adzworld_iframe') !== false) {
				
				echo do_shortcode($ad_text); 
			}else{
				
				echo $ad_text; 
			}
			?>
	    </div>
	</div>
	<!-- END: Page Content -->

	<!-- BEGIN: Sticky Footer -->
	<div id="adz_footer_container">
	    <div id="adz_footer_content" style="text-align: <?php echo $setting_array['footer_style']; ?>">
	       <a href="javascript:void()" class="adz_footer_text"><p class="continue_reading"><?php echo $setting_array['footer_text']; ?> <span class="adz_timer"> </span> seconds.</p></a>
	    </div>
	</div>

<?php

}else{

	if (strpos($ad_text, 'adzworld_iframe') !== false) {

		echo base64_encode('<!-- BEGIN: Sticky Header -->
		<div id="adz_header_container">
		    <div id="adz_header_content" style="text-align: '.$setting_array['header_style'].'">
		        <a href="javascript:void()" class="adz_header_text"><div class="continue_reading">'.$setting_array['header_text'].' <div><span class="adz_timer" > </span> seconds</div></div></a>
		    </div>
		</div>
		<!-- END: Sticky Header -->

		<!-- BEGIN: Page Content -->
		<div id="adz_container">
		    <div id="content">
		        '.do_shortcode($ad_text).'
		    </div>
		</div>
		<!-- END: Page Content -->

		<!-- BEGIN: Sticky Footer -->
		<div id="adz_footer_container">
		    <div id="adz_footer_content" style="text-align: '.$setting_array['footer_style'].'">
		       <a href="javascript:void()" class="adz_footer_text"><p class="continue_reading">'.$setting_array['footer_text'].' <span class="adz_timer"> </span> seconds.</p></a>
		    </div>
		</div>
		'.$style);

	}else{

		echo base64_encode('<!-- BEGIN: Sticky Header -->
		<div id="adz_header_container">
		    <div id="adz_header_content" style="text-align: '.$setting_array['header_style'].'">
		        <a href="javascript:void()" class="adz_header_text"><div class="continue_reading">'.$setting_array['header_text'].' <div> <span class="adz_timer" > </span> seconds</div></div></a>
		    </div>
		</div>
		<!-- END: Sticky Header -->

		<!-- BEGIN: Page Content -->
		<div id="adz_container">
		    <div id="content">
		        '.$ad_text.'
		    </div>
		</div>
		<!-- END: Page Content -->

		<!-- BEGIN: Sticky Footer -->
		<div id="adz_footer_container">
		    <div id="adz_footer_content" style="text-align: '.$setting_array['footer_style'].'>
		        <a href="javascript:void()" class="adz_footer_text"><p class="continue_reading">'.$setting_array['footer_text'].'  <span class="adz_timer"> </span> seconds.</p></a>
		    </div>
		</div>
		'.$style);
	}
}
?>