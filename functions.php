<?php

/**
 * Facebook login & register add-on
 *
 * @package SocialStrap add-on
 * @author Milos Stojanovic
 * @copyright 2014 interactive32.com
 */
function loginWithFacebook()
{
	$fb_helper = new FacebookHelper();
	$facebook = $fb_helper->facebookObj();
	
	if ($facebook->getProfile() != 0) {
		$fb_user = $facebook->api('/v2.0/me');
		$fb_user_email = $fb_user['email'];
		$fb_user_display_name = mb_strtolower(preg_replace("/[^A-Za-z0-9]/", '', $fb_user['name']), 'UTF-8');
		
		$defaultres = 64;
		$bigres = Zend_Registry::get('config')->get('avatar_size') ? Zend_Registry::get('config')->get('avatar_size') : $defaultres;
		
		$fb_avatar = 'https://graph.facebook.com/v2.0/' . $fb_user['id'] . '/picture?width='.$bigres.'&height='.$bigres;
		
		if (! $fb_user_email) {
			Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector')->gotoUrl('/');
			return;
		}
		
		$emailAuthAdapter = Application_Plugin_Common::getEmailAuthAdapter($fb_user_email);
		
		$auth = Zend_Auth::getInstance();
		$authStorage = $auth->getStorage();
		$result = $auth->authenticate($emailAuthAdapter);
		
		if ($result->isValid()) {
			
			$Profiles = new Application_Model_Profiles();
			$user_db_data = $Profiles->getProfileByField('email', $fb_user_email);
			
			// clear identity - force logout
			Zend_Auth::getInstance()->clearIdentity();
			
			// check if account is activated
			if (! $Profiles->isActivated($user_db_data->name)) {
				Application_Plugin_Alerts::error(Zend_Registry::get('Zend_Translate')->translate('Please activate your account first'), 'on');
				
				// clear identity - force logout
				Zend_Auth::getInstance()->clearIdentity();
			} elseif ($user_db_data->is_hidden) {
				
				Application_Plugin_Alerts::error(Zend_Registry::get('Zend_Translate')->translate('This account has been deleted or suspended'), 'off');
				
				// clear identity - force logout
				Zend_Auth::getInstance()->clearIdentity();
			} else {
				// everything ok, login user
				$user_data = $emailAuthAdapter->getResultRowObject();
				
				Application_Plugin_Common::loginUser($user_data, $emailAuthAdapter, $authStorage);
				
				// trigger hooks
				$profile_id = $user_data->id;
				Zend_Registry::get('hooks')->trigger('hook_login', $profile_id);
				
				// flush url
				Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector')->gotoUrl('');
			}
		} else {
			// User must create account first...
			
			// save tmp facebook data to session
			$session = new Zend_Session_Namespace('Default');
			$session->fb_user_email = $fb_user_email;
			$session->fb_user_display_name = $fb_user_display_name;
			$session->fb_avatar = $fb_avatar;
			
			// go to register with facebook
			Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector')->gotoUrl('addons/' . basename(__DIR__) . '/?fb-register');
		}
	} else {
		echo 'Error in Facebook App Config - Could not retrieve user info!';
	}
}

/**
 * Register with facebook
 */
function registerWithFacebook()
{
	// flush if already logged in
	Zend_Auth::getInstance()->clearIdentity();
	
	$session = new Zend_Session_Namespace('Default');
	$email = $session->fb_user_email;
	$avatar = $session->fb_avatar;
	
	// do not allow direct access - without fb_user_email inside session
	if (! $session->fb_user_email) {
		Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector')->gotoUrl('');
	}
	
	require_once 'Form.php';
	$registerwithfacebook_form = new Addon_FacebookRegisterForm();
	
	$Profiles = new Application_Model_Profiles();
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		
		if ($registerwithfacebook_form->isValid($_POST)) {
			$name = $registerwithfacebook_form->getValue('name');
			
			$user = $Profiles->createRow();
			$user->name = $name;
			$user->email = $email;
			$user->password = '';
			$user->activationkey = 'activated';
			$user->language = Zend_Registry::get('config')->get('default_language');
			
			$user = $Profiles->createNewUser($user, 'facebook');
			
			// update last login date
			$ProfilesMeta = new Application_Model_ProfilesMeta();
			$ProfilesMeta->metaUpdate('last_login', Application_Plugin_Common::now(), $user->id);
			
			$Storage = new Application_Model_Storage();
			$StorageAdapter = $Storage->getAdapter();
			
			$defaultres = 64;
			$bigres = Zend_Registry::get('config')->get('avatar_size') ? Zend_Registry::get('config')->get('avatar_size') : $defaultres;
			
			// get the image
			$c = new Zend_Http_Client();
			$c->setUri($avatar);
			$result = $c->request('GET');
			$img = imagecreatefromstring($result->getBody());
			
			// create regular avatar image, resample and store
			$imgname = 'profileimage_' . $name . '.jpg';
			imagejpeg($img, TMP_PATH . '/' . $imgname);
			Application_Plugin_ImageLib::resample(TMP_PATH . '/' . $imgname, TMP_PATH . '/' . $imgname, $defaultres, $defaultres, false);
			$new_filename = $StorageAdapter->moveFileToStorage($imgname, 'avatar');
			$Profiles->updateField($name, 'avatar', $new_filename);
			
			// create big avatar image, resample and store
			$imgname = 'bigprofileimage_' . $name . '.jpg';
			imagejpeg($img, TMP_PATH . '/' . $imgname);
			Application_Plugin_ImageLib::resample(TMP_PATH . '/' . $imgname, TMP_PATH . '/' . $imgname, $bigres, $bigres, false);
			$big_avatar = $StorageAdapter->moveFileToStorage($imgname, 'avatar');
			$ProfilesMeta->metaUpdate('big_avatar', $big_avatar, $user->id);

			// free img resource
			imagedestroy($img);
			
			// login user
			$emailAuthAdapter = Application_Plugin_Common::getEmailAuthAdapter($email);
			$auth = Zend_Auth::getInstance();
			$auth->authenticate($emailAuthAdapter);
			$identity = $emailAuthAdapter->getResultRowObject();
			$authStorage = $auth->getStorage();
			$authStorage->write($identity);
			
			// clear session data
			$session->fb_user_email = '';
			$session->fb_user_display_name = '';
			$session->fb_avatar = '';

			// trigger hooks
			Zend_Registry::get('hooks')->trigger('hook_firsttimelogin', $user->id);
			
			// show welcome message
			Application_Plugin_Alerts::success(Zend_Registry::get('Zend_Translate')->translate('Welcome to the network.'), 'on');
			
			Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector')->gotoUrl('');
		}
	}
	
	echo $registerwithfacebook_form;
}
