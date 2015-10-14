<?php
/**
 * Facebook login & register add-on
 *
 * @package SocialStrap add-on
 * @author Milos Stojanovic
 * @copyright 2014 interactive32.com
 */


$this->addScriptPath(realpath(dirname(__FILE__)));

require_once 'functions.php';

if (isset($_GET['fb-login'])){
	loginWithFacebook();
}

if (isset($_GET['fb-register'])){
	registerWithFacebook();
}
