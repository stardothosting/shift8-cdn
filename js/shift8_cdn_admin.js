jQuery(document).ready(function() {

    // Register for new CDN account
	jQuery(document).on( 'click', '#shift8-cdn-register', function(e) {
        jQuery(".shift8-cdn-spinner").show();
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
                console.log(JSON.stringify(data));
                if (data != null) {
                	jQuery('#shift8_cdn_api_field').val(data.apikey);
                	jQuery('#shift8_cdn_prefix_field').val(data.cdnprefix);
                }
                jQuery('.shift8-cdn-response').html('Please allow for 5 minutes for CDN to be deployed across the network. Check the test URL and dont forget to enable!').fadeIn();
                setTimeout(function(){ jQuery('.shift8-cdn-response').fadeOut() }, 25000);
                jQuery(".shift8-cdn-spinner").hide();         
            },
        	error: function(errorThrown){
            	console.log('Error : ' + JSON.stringify(errorThrown));
				jQuery('.shift8-cdn-response').html(errorThrown.responseText).fadeIn();
				setTimeout(function(){ jQuery('.shift8-cdn-response').fadeOut() }, 5000);
                jQuery(".shift8-cdn-spinner").hide();
        	}
		}); 
	});

    // Check & synchronize config of CDN account
    jQuery(document).on( 'click', '#shift8-cdn-check', function(e) {
        jQuery(".shift8-cdn-spinner").show();
        e.preventDefault();
        var button = jQuery(this);
        var url = button.attr('href');
        jQuery.ajax({
            url: url,
            dataType: 'json',
            data: {
                'action': 'shift8_cdn_push',
                'type': 'check'
            },
            success:function(data) {
                // This outputs the result of the ajax request
                if (data != null) {
                    jQuery('#shift8_cdn_api_field').val(data.apikey);
                    jQuery('#shift8_cdn_prefix_field').val(data.cdnprefix);
                }
                jQuery('.shift8-cdn-response').html('Values have been checked & re-populated from CDN settings.').fadeIn();
                setTimeout(function(){ jQuery('.shift8-cdn-response').fadeOut() }, 25000);
                jQuery(".shift8-cdn-spinner").hide();               
            },
            error: function(errorThrown){
                console.log('Error : ' + JSON.stringify(errorThrown));
                jQuery('.shift8-cdn-response').html(errorThrown.responseText).fadeIn();
                setTimeout(function(){ jQuery('.shift8-cdn-response').fadeOut() }, 5000);
                jQuery(".shift8-cdn-spinner").hide();
            }
        }); 
    });

    // Unregister with CDN network and delete account
    jQuery(document).on( 'click', '#shift8-cdn-unregister', function(e) {
        jQuery(".shift8-cdn-spinner").show();
        e.preventDefault();
        var button = jQuery(this);
        var url = button.attr('href');
        jQuery.ajax({
            url: url,
            dataType: 'json',
            data: {
                'action': 'shift8_cdn_push',
                'type': 'delete',
            },
            success:function(data) {
                // This outputs the result of the ajax request
                if (data != null) {
                    jQuery('#shift8_cdn_api_field').val(data.apikey);
                    jQuery('#shift8_cdn_prefix_field').val(data.cdnprefix);
                }
                jQuery('.shift8-cdn-response').html('Please allow for 5 minutes for your site to be removed from the CDN system. You will have to re-register if you want to use the system again.').fadeIn();
                setTimeout(function(){ jQuery('.shift8-cdn-response').fadeOut() }, 50000);
                jQuery(".shift8-cdn-spinner").hide();               
            },
            error: function(errorThrown){
                console.log('Error : ' + JSON.stringify(errorThrown));
                jQuery('.shift8-cdn-response').html(errorThrown.responseText).fadeIn();
                setTimeout(function(){ jQuery('.shift8-cdn-response').fadeOut() }, 5000);
                jQuery(".shift8-cdn-spinner").hide();
            }
        }); 
    });
});
