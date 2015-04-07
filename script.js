<style type="text/css">
.form-group.registerbtn,
.form-group.loginbtn {
	margin-bottom: 0;
}
</style>

<script type="text/javascript">
/**
 * facebook init 2/2
 * 
 * v2.0 API https://developers.facebook.com/docs/apps/upgrading#upgrading_web_apps
 */
window.fbAsyncInit = function() {
	FB.init({
		appId   : php_facebookAppId,
		version : 'v2.0',
		status  : true, // check login status
		cookie  : true, // enable cookies to allow the server to access the session
		xfbml   : true // parse XFBML
	});

};

/**
 * facebook login
 */
function fb_login(){

	FB.login(function(response) {

		if (response.authResponse) {
			
			var reload_url = php_baseURL + '/addons/' + php_addonName + '/?fb-login';
				window.location.replace(reload_url);
				return true;

			} else {
	
				// cancel button pressed
				return true;
			}

		}, {
			scope: 'email'
		});
}


$(document).ready(function(){


	// add facebook buttons
	<?php if (Zend_Registry::get('config')->get('facebook_appid') && Zend_Registry::get('config')->get('facebook_secret')):?>
	$("#registerbtn, #loginbtn").closest('span').before('<button name="facebooklogin" id="facebooklogin" type="button" class="facebook-login-btn btn btn-info" onclick="fb_login();"><?php echo Zend_Registry::get('Zend_Translate')->translate('Facebook')?></button>');
	<?php endif;?>

	/**
	 * facebook init 1/2 (script load & attach to fb-root at footer) 
	 */
	(function() {
		   var e = document.createElement('script');
		   e.src = document.location.protocol + '//connect.facebook.net/en_US/sdk.js';
		   e.async = true;
		   document.getElementById('fb-root').appendChild(e);
		}());
		
	
});

</script>