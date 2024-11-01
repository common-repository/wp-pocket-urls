"use strict";
jQuery(document).ready(function($) {
    $('input#publish').on('click', function(e){
		var url = $('input[name="wp_pocketurl_link"]').val();
		if(! isURL( validateURL(url) ) ){
			$('div.wp_pocketurl_error').show();
			e.preventDefault();	
		}
	});
	if($('#enable-custom-link-options').is(':checked')){
		$('fieldset#custom-options').removeAttr('disabled');
	}
	$('#enable-custom-link-options').on('click', function(){
		if($(this).is(':checked')){
			$('fieldset#custom-options').removeAttr('disabled');
		}else{
			$('fieldset#custom-options').attr('disabled','disabled');
		}
	})
});

function validateURL(url){
	if( (url.indexOf('http://') == 0) || (url.indexOf('https://') == 0) ){
		return url;
	}else{
		return 'http://' + url;	
	}
}
function isURL(url) {
  var regex =/(https?|ftp):\/\/(-\.)?([^\s/?\.#-]+\.?)+(\/[^\s]*)?/i;
  return regex.test(url);
}