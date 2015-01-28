<?php

class EE_Email_Input extends EE_Form_Input_Base{

	function __construct($options = array()){
		$this->_set_display_strategy(new EE_Text_Input_Display_Strategy('email'));
		$this->_set_normalization_strategy(new EE_Text_Normalization());
		$this->_add_validation_strategy(new EE_Email_Validation_Strategy());
		parent::__construct($options);
		$this->set_html_class( $this->html_class() . ' email' );
	}
}