<?php
/**
 * Facebook login & register add-on
 *
 * @package SocialStrap add-on
 * @author Milos Stojanovic
 * @copyright 2014 interactive32.com
 */

$this->attach('view_body', 10, function($view) {
	
	echo '<div id="fb-root"></div>';
	echo '<script type="text/javascript">var php_addonName = "'.basename(__DIR__).'"</script>';
	
	require_once 'script.js';
	
});
