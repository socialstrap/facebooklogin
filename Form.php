<?php
/**
 * Facebook login & register add-on
 *
 * @package SocialStrap add-on
 * @author Milos Stojanovic
 * @copyright 2013 interactive32.com
 */

class Addon_FacebookRegisterForm extends Zend_Form
{

	/**
	 *
	 * Login page form
	 *
	 */
	public function init()
	{
		$baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
		$translator = $this->getTranslator();

		// use template file
		$this->setDecorators( array(
				array('ViewScript', array('viewScript' => 'Form.phtml'))));

		$this->setName(get_class());
		$this->setMethod('post');
		$this->setAction('');

		$username_minchars = Zend_Registry::get('config')->get('username_minchars');
		$username_maxchars = Zend_Registry::get('config')->get('username_maxchars');

		$session = new Zend_Session_Namespace('Default');

		// fields

		// lowercase, alnum without whitespaces
		$name = new Zend_Form_Element_Text('name');
		$name
		->setDecorators(array('ViewHelper', 'Errors'))
		->setRequired(true)
		->addFilter('StringToLower')
		->addValidator('alnum', false, array('allowWhiteSpace' => false))
		->addValidator('stringLength', false, array($username_minchars, $username_maxchars))
		->setLabel($translator->translate('Username'))
		->setErrorMessages(array(sprintf($translator->translate('Please choose a valid username between %d and %d characters'), $username_minchars, $username_maxchars)))
		->setValue($session->fb_user_display_name)
		->setAttrib('class', 'form-control alnum-only');


		$confirm = new Zend_Form_Element_Checkbox('confirm');
		$confirm
		->setDecorators(array('ViewHelper', 'Errors'))
		->setLabel($translator->translate('Accept Terms & Conditions'))
		->addValidator('GreaterThan', false, array(0))
		->setErrorMessages(array($translator->translate('Please Read and Agree to our Terms & Conditions')));


		$register = new Zend_Form_Element_Submit('registerfbsubmit');
		$register
		->setDecorators(array('ViewHelper'))
		->setLabel($translator->translate('Create account'))
		->setAttrib('class', 'submit btn btn-default');


		$this->addElements(array($name, $confirm, $register));

	}


	/**
	 *
	 * unique name validator
	 */
	public function isValid($data)
	{
		// return on false to see previous errors
		if (parent::isValid($data) == false) return false;

		$translator = $this->getTranslator();

		$this->getElement('name')
		->addValidator(
				'Db_NoRecordExists',
				false,
				array(
						'table'     => 'profiles',
						'field'     => 'name',
				)
		)
		->setErrorMessages(array($translator->translate('This username is not available')));

		return parent::isValid($data);
	}
}

