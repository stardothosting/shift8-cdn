jQuery(document).ready(function() {

    // Set placeholder of textare for reject / ignore files
    
    var textArea = document.getElementById('shift8-cdn-reject-files');
    if (textArea) {
        textArea.placeholder = textArea.placeholder.replace(/\\n/g, '\n');
    }

    // Handle enable/disable of minify checkboxes based on parent CDN checkboxes
    jQuery('#shift8_cdn_css').on('change', function() {
        if (jQuery(this).is(':checked')) {
            jQuery('#shift8_cdn_minify_css').prop('disabled', false);
        } else {
            jQuery('#shift8_cdn_minify_css').prop('disabled', true).prop('checked', false);
        }
    });

    jQuery('#shift8_cdn_js').on('change', function() {
        if (jQuery(this).is(':checked')) {
            jQuery('#shift8_cdn_minify_js').prop('disabled', false);
        } else {
            jQuery('#shift8_cdn_minify_js').prop('disabled', true).prop('checked', false);
        }
    });

    // Handle clear cache button
    jQuery(document).on('click', '#shift8-cdn-clear-cache', function(e) {
        e.preventDefault();
        var button = jQuery(this);
        var url = button.attr('href');
        
        button.prop('disabled', true).text('Clearing...');
        
        jQuery.ajax({
            url: url,
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    jQuery('.shift8-cdn-cache-response').html('✓ ' + data.data).css('color', 'green').fadeIn();
                    button.prop('disabled', false).text('Clear Minified Cache');
                    // Reload page to update cache stats
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    jQuery('.shift8-cdn-cache-response').html('✗ ' + data.data).css('color', 'red').fadeIn();
                    button.prop('disabled', false).text('Clear Minified Cache');
                }
                setTimeout(function() {
                    jQuery('.shift8-cdn-cache-response').fadeOut();
                }, 5000);
            },
            error: function(errorThrown) {
                console.log('Error : ' + JSON.stringify(errorThrown));
                jQuery('.shift8-cdn-cache-response').html('✗ Error clearing cache').css('color', 'red').fadeIn();
                button.prop('disabled', false).text('Clear Minified Cache');
                setTimeout(function() {
                    jQuery('.shift8-cdn-cache-response').fadeOut();
                }, 5000);
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

    // Check & synchronize config of CDN account
    jQuery(document).on( 'click', '#shift8-cdn-purge', function(e) {
        jQuery(".shift8-cdn-purge-spinner").show();
        e.preventDefault();
        var button = jQuery(this);
        var url = button.attr('href');
        jQuery.ajax({
            url: url,
            dataType: 'json',
            data: {
                'action': 'shift8_cdn_push',
                'type': 'purge'
            },
            success:function(data) {
                console.log(JSON.stringify(data));
                jQuery('.shift8-cdn-purge-response').html(data.response).fadeIn();
                setTimeout(function(){ jQuery('.shift8-cdn-purge-response').fadeOut() }, 25000);
                jQuery(".shift8-cdn-purge-spinner").hide();               
            },
            error: function(errorThrown){
                console.log('Error : ' + JSON.stringify(errorThrown));
                jQuery('.shift8-cdn-purge-response').html(errorThrown.responseText).fadeIn();
                setTimeout(function(){ jQuery('.shift8-cdn-purge-response').fadeOut() }, 5000);
                jQuery(".shift8-cdn-purge-spinner").hide();
            }
        }); 
    });
});


function Shift8CDNCopyToClipboard(containerid) {
    if (document.selection) { 
        var range = document.body.createTextRange();
        range.moveToElementText(document.getElementById(containerid));
        range.select().createTextRange();
        document.execCommand("copy"); 

    } else if (window.getSelection) {
        var range = document.createRange();
         range.selectNode(document.getElementById(containerid));
         window.getSelection().addRange(range);
         document.execCommand("copy");
         alert("text copied") 
    }
}
