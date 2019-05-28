jQuery(document).ready(function() {
	jQuery(document).on( 'click', '#shift8-cdn-register', function(e) {
		e.preventDefault();
		var button = jQuery(this);
    	var url = button.attr('href');
    	jQuery.ajax({
        	url: url,
        	dataType: 'json',
        	data: {
            	'action': 'shift8_cdn_push',
                'type': 'register',
        	},
        	success:function(data) {
            	// This outputs the result of the ajax request
            	jQuery('#shift8_cdn_api_field').val(data.apikey);
            	jQuery('#shift8_cdn_prefix_field').val(data.cdnprefix);
                jQuery('.shift8-cdn-response').html('Please allow for 5 minutes for CDN to be deployed across the network. Check the test URL and dont forget to enable!').fadeIn();
                setTimeout(function(){ jQuery('.shift8-cdn-response').fadeOut() }, 25000);                
            },
        	error: function(errorThrown){
            	console.log('Error : ' + JSON.stringify(errorThrown));
				jQuery('.shift8-cdn-response').html(errorThrown.responseText).fadeIn();
				setTimeout(function(){ jQuery('.shift8-cdn-response').fadeOut() }, 5000);
        	}
		}); 
	});
});
