<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) { exit('No direct script access allowed'); }
/**
 * EE_Form_Input_With_Options_Base
 * For form inputs which are meant to only have a limit set of options that can be used
 * (like for checkboxes or select dropdowns, etc; as opposed to more open-ended text boxes etc)
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 */
class EE_Form_Input_With_Options_Base extends EE_Form_Input_Base{

	/**
	 * array of available options to choose as an answer
	 * @var array
	 */
	protected $_options = array();

	/**
	 * whether to display the html_label_text above the checkbox/radio button options
	 * @var boolean
	 */
	protected $_display_html_label_text = TRUE;

	/**
	 * whether to display an question option description as part of the input label
	 * @var boolean
	 */
	protected $_use_desc_in_label = TRUE;

	/**
	 * strlen() result for the longest input value (what gets displayed in the label)
	 * this is used to apply a css class to the input label
	 * @var int
	 */
	protected $_label_size = 0;

	/**
	 * whether to enforce the label size value passed in the constructor
	 * @var boolean
	 */
	protected $_enforce_label_size = FALSE;

	/**
	 * whether to allow multiple selections (ie, the value of this input should be an array)
	 * or not (ie, the value should be a simple int, string, etc)
	 * @var boolean
	 */
	protected $_multiple_selections = FALSE;



	/**
	 * @param array $answer_options
	 * @param array $input_settings
	 */
	public function __construct( $answer_options = array(), $input_settings = array() ) {
		if ( isset( $input_settings['label_size'] )) {
			$this->_set_label_size( $input_settings['label_size'] );
			if ( isset( $input_settings['enforce_label_size'] ) && $input_settings['enforce_label_size'] ) {
				$this->_enforce_label_size = TRUE;
			}
		}
		if ( isset( $input_settings['display_html_label_text'] )) {
			$this->set_display_html_label_text( $input_settings['display_html_label_text'] );
		}
		$this->set_select_options( $answer_options );
		$this->_add_validation_strategy(
			new EE_Many_Valued_Validation_Strategy(
				array(
					new EE_Enum_Validation_Strategy(
						isset( $input_settings[ 'validation_error_message' ] )
							? $input_settings[ 'validation_error_message' ]
							: null
					)
				)
			)
		);
		parent::__construct( $input_settings );
	}



	/**
	 * Sets the allowed options for this input. Also has the side-effect of
	 * updating the normalization strategy to match the keys provided in the array
	 * @param array $answer_options
	 * @return void  just has the side-effect of setting the options for this input
	 */
	public function set_select_options( $answer_options = array() ){
		$answer_options = is_array( $answer_options ) ? $answer_options : array( $answer_options );
		//get the first item in the select options and check it's type
		if ( reset( $answer_options ) instanceof EE_Question_Option ) {
			$this->_options = $this->_process_question_options( $answer_options );
		} else {
			$this->_options = $answer_options;
		}
		//d( $this->_options );
		$select_option_keys = array_keys( $this->_options );
		// attempt to determine data type for values in order to set normalization type
		if (
			count( $this->_options ) == 2
			&&
			(
				( in_array( TRUE, $select_option_keys, true ) && in_array( FALSE, $select_option_keys, true ))
				|| ( in_array( 1, $select_option_keys, true ) && in_array( 0, $select_option_keys, true ))
			)
		){
			// values appear to be boolean, like TRUE, FALSE, 1, 0
			$normalization = new EE_Boolean_Normalization();
		} else{
			//are ALL the options ints? If so use int validation
			$all_ints = true;
			foreach($select_option_keys as $value ){
				if( ! is_int( $value ) ){
					$all_ints = false;
					break;
				}
			}
			if( $all_ints ){
				$normalization = new EE_Int_Normalization();
			}else{
				$normalization = new EE_Text_Normalization();
			}
		}
		// does input type have multiple options ?
		if ( $this->_multiple_selections ) {
			$this->_set_normalization_strategy( new EE_Many_Valued_Normalization( $normalization ));
		} else {
			$this->_set_normalization_strategy( $normalization );
		}
	}



	/**
	 * @return array
	 */
	public function options(){
		return $this->_options;
	}



	/**
	 * Returns an array which is guaranteed to not be multidimensional
	 * @return array
	 */
	public function flat_options(){
		return $this->_flatten_select_options($this->options());
	}



	/**
	 * Makes sure $arr is a flat array, not a multidimensional one
	 * @param array $arr
	 * @return array
	 */
	protected function _flatten_select_options( $arr ){
		$flat_array = array();
		EE_Registry::instance()->load_helper('Array');
		if ( EEH_Array::is_multi_dimensional_array( $arr )) {
			foreach( $arr as $sub_array ){
				foreach( $sub_array as $key => $value ) {
					$flat_array[ $key ] = $value;
					$this->_set_label_size( $value );
				}
			}
		} else {
			foreach( $arr as $key => $value ) {
				$flat_array[ $key ] = $value;
				$this->_set_label_size( $value );
			}
		}
		return $flat_array;
	}



	/**
	 * @param EE_Question_Option[] $question_options_array
	 * @return array
	 */
	protected function _process_question_options( $question_options_array = array() ) {
		$flat_array = array();
		foreach( $question_options_array as $question_option ) {
			if ( $question_option instanceof EE_Question_Option ) {
				$desc = '';
				if ( $this->_use_desc_in_label ) {
					$desc = $question_option->desc();
					$desc = ! empty( $desc ) ? '<span class="ee-question-option-desc"> - ' . $desc . '</span>' : '';
				}
				$flat_array[ $question_option->value() ] = $question_option->value() . $desc;
			} elseif ( is_array( $question_option )) {
				$non_question_option = $this->_flatten_select_options( $question_option );
				$flat_array = $flat_array + $non_question_option;
			}
		}
		return $flat_array;
	}



	/**
	 *    set_label_sizes
	 * @return void
	 */
	public function set_label_sizes(){
		// did the input settings specifically say to NOT set the label size dynamically ?
		if ( ! $this->_enforce_label_size ) {
			foreach( $this->_options as $option ) {
				// calculate the strlen of the label
				$this->_set_label_size( $option );
			}
		}
	}



	/**
	 *    _set_label_size_class
	 * @param int|string $value
	 * @return void
	 */
	private function _set_label_size( $value = '' ){
		// determine length of option value
		$val_size = is_int( $value ) ? $value : strlen( $value );
		// use new value if bigger than existing
		$this->_label_size = $val_size > $this->_label_size ? $val_size : $this->_label_size;
	}



	/**
	 * 	get_label_size_class
	 * @return string
	 */
	function get_label_size_class(){
		// use maximum option value length to determine label size
		if( $this->_label_size < 3 ) {
			$size = ' nano-lbl';
		} else if ( $this->_label_size < 6 ) {
			$size =  ' micro-lbl';
		} else if ( $this->_label_size < 12 ) {
			$size =  ' tiny-lbl';
		} else if ( $this->_label_size < 25 ) {
			$size =  ' small-lbl';
		} else if ( $this->_label_size < 50 ) {
			$size =  ' medium-lbl';
		} else if ( $this->_label_size >= 100 ) {
			$size =  ' big-lbl';
		} else {
			$size =  ' medium-lbl';
		}
		return $size;
	}

	/**
	 * Returns the pretty value for the normalized value
	 * @return string
	 */
	function pretty_value(){
		$options = $this->flat_options();
		$unnormalized_value_choices = $this->get_normalization_strategy()->unnormalize( $this->_normalized_value );
		if( ! $this->_multiple_selections ){
			$unnormalized_value_choices = array( $unnormalized_value_choices );
		}
		$pretty_strings = array();
		foreach( $unnormalized_value_choices as $unnormalized_value_choice ){
			if( isset( $options[ $unnormalized_value_choice ] ) ){
				$pretty_strings[] =  $options[ $unnormalized_value_choice ];
			}else{
				$pretty_strings[] = $this->normalized_value();
			}
		}
		return implode(", ", $pretty_strings );
	}



	/**
	 * @return boolean
	 */
	public function display_html_label_text() {
		return $this->_display_html_label_text;
	}



	/**
	 * @param boolean $display_html_label_text
	 */
	public function set_display_html_label_text( $display_html_label_text ) {
		$this->_display_html_label_text = filter_var( $display_html_label_text, FILTER_VALIDATE_BOOLEAN );
	}



	/**
	 * list of possible validation strategies that *could* be applied to this input
	 *
	 * @return array EE_Enum_Validation_Strategy
	 */
	public static function optional_validation_strategies() {
		return array(
			//'credit_card' => 'EE_Credit_Card_Validation_Strategy',
			//'email'       => 'EE_Email_Validation_Strategy',
			//'enum'        => 'EE_Enum_Validation_Strategy',
			//'float'       => 'EE_Float_Validation_Strategy',
			//'int'        => 'EE_Int_Validation_Strategy',
			//'full_html'   => 'EE_Full_HTML_Validation_Strategy',
			//'many_valued' => 'EE_Many_Valued_Validation_Strategy',
			//'max_length' => 'EE_Max_Length_Validation_Strategy',
			//'min_length' => 'EE_Min_Length_Validation_Strategy',
			//'plaintext'   => 'EE_Plaintext_Validation_Strategy',
			'required'    => 'EE_Required_Validation_Strategy',
			//'simple_html' => 'EE_Simple_HTML_Validation_Strategy',
			//'text'        => 'EE_Text_Validation_Strategy',
			//'url'         => 'EE_URL_Validation_Strategy',
		);
	}


}
// End of file EE_Form_Input_With_Options_Base.input.php