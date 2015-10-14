<?php
/**
 * Facebook login & register add-on
 *
 * @package SocialStrap add-on
 * @author Milos Stojanovic
 * @copyright 2014 interactive32.com
 */
 
require_once 'include/autoload.php';

$this->attach('view_body', 10, function($view) {
	
	$fb_appid = Zend_Registry::get('config')->get('facebook_appid');
	$fb_secret = Zend_Registry::get('config')->get('facebook_secret');
	
	$fb = new Facebook\Facebook([
	  'app_id' => $fb_appid,
	  'app_secret' => $fb_secret,
	  'default_graph_version' => 'v2.4',
	]);
	
	
	$helper = $fb->getRedirectLoginHelper();

	$permissions = ['email']; // Optional permissions
	
	$reload_url = Application_Plugin_Common::getFullBaseUrl().'/addons/' . basename(__DIR__) . '/?fb-login';

	$loginUrl = $helper->getLoginUrl($reload_url, $permissions);
	
	echo '<div id="fb-root"></div>';
	echo '<script type="text/javascript">var php_addonName = "'.basename(__DIR__).'"; var php_fbloginurl = "'.$loginUrl.'"</script>';
	
	require_once 'script.js';
	
});
