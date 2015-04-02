<?php namespace Collective\Html;

use DateTime;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Session\Store as Session;
use Illuminate\Support\Traits\Macroable;

class BootstrapBuilder extends FormBuilder {

	/**
	 * 	Bootstrap form type.
	 *  
	 * @var string
	 */
	protected $formType;

	/**
	 * Open up a new HTML form.
	 *
	 * @param  array   $options
	 * @return string
	 */
	public function open(array $options = array())
	{
		$form_type = array_get($options, 'formType', NULL);

		unset($options['formType']);

		if (isset($options['model'])) {
			$this->model = $options['model'];
			unset($options['model']);
		}

		if (!is_null($this->model) && $this->model->exists) {
			if (isset($options['update'])) {
				$options['route'] = [$options['update'], $this->model->getKey()];
				$options['method'] = 'PUT';
			}
		} else if(isset($options['store'])){
			$options['route'] = $options['store'];
			$options['method'] = 'POST';
		}
		unset($options['store']);
		unset($options['update']);

		$method = array_get($options, 'method', 'post');

		// We need to extract the proper method from the attributes. If the method is
		// something other than GET or POST we'll use POST since we will spoof the
		// actual method since forms don't support the reserved methods in HTML.
		$attributes['method'] = $this->getMethod($method);

		$attributes['action'] = $this->getAction($options);

		$attributes['accept-charset'] = 'UTF-8';
		
		$attributes['class'] = $this->formType = $this->getFormType($form_type);

		// If the method is PUT, PATCH or DELETE we will need to add a spoofer hidden
		// field that will instruct the Symfony request to pretend the method is a
		// different method than it actually is, for convenience from the forms.
		$append = $this->getAppendage($method);

		if (isset($options['files']) && $options['files'])
		{
			$options['enctype'] = 'multipart/form-data';
		}

		// Finally we're ready to create the final form HTML field. We will attribute
		// format the array of attributes. We will also add on the appendage which
		// is used to spoof requests for this PUT, PATCH, etc. methods on forms.
		$attributes = array_merge(

			$attributes, array_except($options, $this->reserved)

		);

		// Finally, we will concatenate all of the attributes into a single string so
		// we can build out the final form open statement. We'll also append on an
		// extra value for the hidden _method field if it's needed for the form.
		$attributes = $this->html->attributes($attributes);

		return '<form'.$attributes.'>'.$append;
	}

	public function openHorizontal(array $options = array())
	{
		$options['formType'] = 'horizontal';
		return $this->open($options);
	}


	public function openInline(array $options = array())
	{
		$options['formType'] = 'inline';
		return $this->open($options);
	}

	public function modelHorizontal($model, array $options = array())
	{
		$this->model = $model;

		return $this->openHorizontal($options);
	}


	public function modelInline($model, array $options = array())
	{
		$this->model = $model;

		return $this->openInline($options);
	}

	/**
	 *	Get bootstrap form type.
	 * 
	 * @param  string
	 * @return string
	 */
	protected function getFormType($type = NULL)
	{
		switch ($type) {
			case 'horizontal':
				return "form-horizontal";
				
			case 'inline':
				return "form-inline";
			
			default:
				return NULL;
		}
	}

	/**
	 * Close the current form.
	 *
	 * @return string
	 */
	public function close()
	{
		$this->labels = array();

		$this->model = null;

		return '</form>';
	}

	/**
	 * Generate a hidden field with the current CSRF token.
	 *
	 * @return string
	 */
	public function token()
	{
		$token = ! empty($this->csrfToken) ? $this->csrfToken : $this->session->getToken();

		return $this->hidden('_token', $token);
	}

	/**
	 * Create a form label element.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	public function label($name, $value = null, $options = array())
	{
		$this->labels[] = $name;

		switch ($this->formType) {
			case 'form-horizontal':
				$options = $this->addClass($options, 'col-sm-2', true);
		}

		$options = $this->addClass($options, 'control-label');

		$options = $this->html->attributes($options);

		$value = e($this->formatLabel($name, $value));

		return '<label for="'.$name.'"'.$options.'>'.$value.'</label>';
	}

	/**
	 * Create a form label for checkable element.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	public function labelCheckable($name, $value = null, $options = array())
	{
		$this->labels[] = $name;

		$options = $this->html->attributes($options);

		$value = e($this->formatLabel($name, $value));

		return $value;
	}

	/**
	 * Format the label value.
	 *
	 * @param  string  $name
	 * @param  string|null  $value
	 * @return string
	 */
	protected function formatLabel($name, $value)
	{
		return $value ?: ucwords(str_replace('_', ' ', $name));
	}

	/**
	 * Create a form input field.
	 *
	 * @param  string  $type
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	public function input($type, $name, $value = null, $options = array())
	{
		if ( ! isset($options['name'])) $options['name'] = $name;

		// We will get the appropriate value for the given field. We will look for the
		// value in the session for the value in the old input data then we'll look
		// in the model instance if one is set. Otherwise we will just use empty.
		$id = $this->getIdAttribute($name, $options);

		if ( ! in_array($type, $this->skipValueTypes))
		{
			$value = $this->getValueAttribute($name, $value);
		}

		// Once we have the type, value, and ID we can merge them into the rest of the
		// attributes array so we can convert them into their HTML attribute format
		// when creating the HTML element. Then, we will return the entire input.
		$merge = compact('type', 'value', 'id');

		$options = array_merge($options, $merge);

		return '<input'.$this->html->attributes($options).'>';
	}

	protected function formControl($options = array())
	{
		return $this->addClass($options, 'form-control', true);
	}

	protected function addClass($options, $class, $before = false)
	{
		if (!is_null($options) && array_key_exists('class', $options)) {
			if ($before) {
				$options['class'] = $class .' '. $options['class'];
			}else{
				$options['class'] = $options['class'] .' '. $class ;
			}
		}else{
			$options['class'] = $class;
		}
		return $options;
	}

	protected function formGroup($options = array())
	{
		return $this->addClass($options, 'form-group', true);
	}

	protected function beginFormGroup($errorClass = null, $options = array())
	{
		$options = $this->addClass($options, $errorClass);
		$options = $this->formGroup($options);
		$options = $this->html->attributes($options);
		return '<div '.$options.'>';
	}

	protected function endFormGroup()
	{
		return '</div>';
	}

	protected function beginHorizontalGroup($margin = false)
	{
		if ($margin) {
			return '<div class="col-sm-offset-2 col-sm-10">';
		}
		return '<div class="col-sm-10">';
	}

	protected function endHorizontalGroup()
	{
		return '</div>';
	}

	protected function formBox($type, $name, $value = null, $options = array())
	{
		$label = $this->label($name, $this->labelGen($name, $options), $options);

		switch ($type) {
			case 'select':			
			case 'checkbox':			
			case 'radio':			
			case 'textarea':			
				$input = $value;
				break;
			default:
				$inputOptions = $this->formControl($options);
				$input =  $this->input($type , $name, $value, $inputOptions);
				break;
		}
		
		$errorClass = $this->getFieldErrorClass($name);

		switch ($this->formType) {
			case 'form-horizontal':
				return 	$this->beginFormGroup($errorClass).
							 	$label.
				 				$this->beginHorizontalGroup().
								$input.
								$this->getFieldError($name).
								$this->endHorizontalGroup().
								$this->endFormGroup();
			
			default:
				return 	$this->beginFormGroup($errorClass).
							 	$label.
								$input.
								$this->getFieldError($name).
								$this->endFormGroup();
		}
	}

	protected function labelGen($name, $options)
	{
		if (isset($options['label'])) {
			return $options['label'];
		}
		
		if (!is_null($this->model)) {
			$classNameWithNamespace = get_class($this->model);
		    $className =  snake_case( str_replace("\\", '', $classNameWithNamespace) );
		    
		    //example to translate from model Html/Bootstrap: models.html_bootstrap.text
		    $trans = trans('models.' . $className . '.' . $name);

			if ($trans != 'models.' . $className . '.' . $name) {
				return $trans;
			}
		}
		return null;
	}

	/**
	 * Create a text input field.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	public function text($name, $value = null, $options = array())
	{
		return $this->formBox('text' ,$name, $value, $options);
	}

	/**
	 * Create a password input field.
	 *
	 * @param  string  $name
	 * @param  array   $options
	 * @return string
	 */
	public function password($name, $options = array())
	{
		return $this->formBox('password' ,$name, '', $options);
	}

	/**
	 * Create a hidden input field.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	public function hidden($name, $value = null, $options = array())
	{
		return $this->input('hidden', $name, $value, $options);
	}

	/**
	 * Create an e-mail input field.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	public function email($name, $value = null, $options = array())
	{
		return $this->formBox('email' ,$name, $value, $options);
	}

	/**
	 * Create a number input field.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	public function number($name, $value = null, $options = array())
	{
		return $this->formBox('number' ,$name, $value, $options);
	}

	/**
	 * Create a date input field.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	public function date($name, $value = null, $options = array())
	{
		
		if ($value instanceof DateTime)
		{
			$value = $value->format('Y-m-d');
		}

		return $this->formBox('date' ,$name, $value, $options);
	}

	/**
	 * Create a url input field.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	public function url($name, $value = null, $options = array())
	{
		return $this->formBox('url' ,$name, $value, $options);
	}

	/**
	 * Create a file input field.
	 *
	 * @param  string  $name
	 * @param  array   $options
	 * @return string
	 */
	public function file($name, $options = array())
	{
		return $this->formBox('file' ,$name, $value, $options);
	}

	/**
	 * Create a textarea input field.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	public function textarea($name, $value = null, $options = array())
	{
		$formBoxOptions = $options;
		if ( ! isset($options['name'])) $options['name'] = $name;

		// Next we will look for the rows and cols attributes, as each of these are put
		// on the textarea element definition. If they are not present, we will just
		// assume some sane default values for these attributes for the developer.
		$options = $this->setTextAreaSize($options);

		$options = $this->formControl($options);

		$options['id'] = $this->getIdAttribute($name, $options);

		$value = (string) $this->getValueAttribute($name, $value);

		unset($options['size']);

		// Next we will convert the attributes into a string form. Also we have removed
		// the size attribute, as it was merely a short-cut for the rows and cols on
		// the element. Then we'll create the final textarea elements HTML for us.
		$options = $this->html->attributes($options);

		$textarea = '<textarea'.$options.'>'.e($value).'</textarea>';
		return $this->formBox('textarea' ,$name, $textarea, $formBoxOptions);
	}

	/**
	 * Set the text area size on the attributes.
	 *
	 * @param  array  $options
	 * @return array
	 */
	protected function setTextAreaSize($options)
	{
		if (isset($options['size']))
		{
			return $this->setQuickTextAreaSize($options);
		}

		// If the "size" attribute was not specified, we will just look for the regular
		// columns and rows attributes, using sane defaults if these do not exist on
		// the attributes array. We'll then return this entire options array back.
		$cols = array_get($options, 'cols', 50);

		$rows = array_get($options, 'rows', 10);

		return array_merge($options, compact('cols', 'rows'));
	}

	/**
	 * Set the text area size using the quick "size" attribute.
	 *
	 * @param  array  $options
	 * @return array
	 */
	protected function setQuickTextAreaSize($options)
	{
		$segments = explode('x', $options['size']);

		return array_merge($options, array('cols' => $segments[0], 'rows' => $segments[1]));
	}

	/**
	 * Create a select box field.
	 *
	 * @param  string  $name
	 * @param  array   $list
	 * @param  string  $selected
	 * @param  array   $options
	 * @return string
	 */
	public function select($name, $list = array(), $selected = null, $options = array())
	{
		$optionsSelect = $this->formControl($options);

		// When building a select box the "value" attribute is really the selected one
		// so we will use that when checking the model or session for a value which
		// should provide a convenient method of re-populating the forms on post.
		$selected = $this->getValueAttribute($name, $selected);

		$optionsSelect['id'] = $this->getIdAttribute($name, $optionsSelect);

		if ( ! isset($optionsSelect['name'])) $optionsSelect['name'] = $name;

		// We will simply loop through the optionsSelect and build an HTML value for each of
		// them until we have an array of HTML declarations. Then we will join them
		// all together into one single HTML element that can be put on the form.
		$html = array();

		foreach ($list as $value => $display)
		{
			$html[] = $this->getSelectOption($display, $value, $selected);
		}

		// Once we have all of this HTML, we can join this into a single element after
		// formatting the attributes into an HTML "attributes" string, then we will
		// build out a final select statement, which will contain all the values.
		$optionsSelect = $this->html->attributes($optionsSelect);

		$list = implode('', $html);

		$select = "<select{$optionsSelect}>{$list}</select>";
		return $this->formBox('select' ,$name, $select, $options);
	}

	/**
	 * Create an option group form element.
	 *
	 * @param  array   $list
	 * @param  string  $label
	 * @param  string  $selected
	 * @return string
	 */
	protected function optionGroup($list, $label, $selected)
	{
		$html = array();

		foreach ($list as $value => $display)
		{
			$html[] = $this->option($display, $value, $selected);
		}

		return '<optgroup label="'.e($label).'">'.implode('', $html).'</optgroup>';
	}

	/**
	 * Create a select element option.
	 *
	 * @param  string  $display
	 * @param  string  $value
	 * @param  string  $selected
	 * @return string
	 */
	protected function option($display, $value, $selected)
	{
		$selected = $this->getSelectedValue($value, $selected);

		$options = array('value' => e($value), 'selected' => $selected);

		return '<option'.$this->html->attributes($options).'>'.e($display).'</option>';
	}

	/**
	 * Create a checkbox input field.
	 *
	 * @param  string  $name
	 * @param  mixed   $value
	 * @param  bool    $checked
	 * @param  array   $options
	 * @return string
	 */
	public function checkbox($name, $value = 1, $checked = null, $options = array())
	{
		if (is_null($value)) $value = 1;

		return $this->checkable('checkbox', $name, $value, $checked, $options);
	}

	public function checkboxes($name, $choices = array(), $checkedValues = array(), $options = array())
	{
		$elements = '';
		foreach ($choices as $value => $choiceLabel) {
			$checked = in_array($value, (array) $checkedValues);

			$optionsElement = array_merge($options, array('label' => $choiceLabel,'display' => 'inline'));
			$elements .= $this->checkable('checkbox', $name, $value, $checked, $optionsElement);
		}

		$elements = '<div>' .$elements . '</div>';

		return $this->formBox('checkbox' ,$name, $elements, $options);
	}

	/**
	 * Create a radio button input field.
	 *
	 * @param  string  $name
	 * @param  mixed   $value
	 * @param  bool    $checked
	 * @param  array   $options
	 * @return string
	 */
	public function radio($name, $value = null, $checked = null, $options = array())
	{
		if (is_null($value)) $value = $name;

		return $this->checkable('radio', $name, $value, $checked, $options);
	}	

	public function radios($name, $choices = array(), $checkedValue = null, $options = array())
	{
		$elements = '';
		foreach ($choices as $value => $choiceLabel) {
			$checked = $value === $checkedValue;

			$optionsElement = array_merge($options, array('label' => $choiceLabel,'display' => 'inline'));
			$elements .= $this->checkable('radio', $name, $value, $checked, $optionsElement);
		}

		$elements = '<div>' .$elements . '</div>';

		return $this->formBox('radio' ,$name, $elements, $options);
	}

	/**
	 * Create a checkable input field.
	 *
	 * @param  string  $type
	 * @param  string  $name
	 * @param  mixed   $value
	 * @param  bool    $checked
	 * @param  array   $options
	 * @return string
	 */
	protected function checkable($type, $name, $value, $checked, $options)
	{
		$disabled = '';
		if (array_key_exists('disabled',$options) && $options['disabled'] == 'disabled') {
			$disabled = ' disabled';
		}

		$label = $this->labelCheckable($name, $this->labelGen($name, $options), $options);

		$checked = $this->getCheckedState($type, $name, $value, $checked);

		if ($checked) $options['checked'] = 'checked';

		if (array_key_exists('display',$options) && $options['display'] == 'inline') {
			$inline = ' class="' .$type. '-inline"';
			unset($options['display']);
		}else{
			$inline = '';
		}

		$error = $this->getFieldError($name);
		
		if ($inline) {
			$html = '';
		}else{
			$html = '<div class="' .$type.$disabled. '">';
		}

		$html .= '<label' .$inline. '>';
		if($type=="checkbox") $html .= $this->hidden($name, 0);
		$html .= $this->input($type, $name, $value, $options);
		$html .= $label;
		$html .= '</label>';
		
		if (!$inline) {
			$html .= '</div>';
		}

		if ($inline) 
			return $html;

		$errorClass = $this->getFieldErrorClass($name);

		switch ($this->formType) {
			case 'form-horizontal':
				return 	$this->beginFormGroup($errorClass).
						$this->beginHorizontalGroup(true).
						$html. 
						$this->endHorizontalGroup().
						$error.
						$this->endFormGroup();
				break;
			default:
				if(!is_null($errorClass)){
					return '<div class="' .$errorClass . '">'.$html.$error.'</div>';
				}
				return $html.$error;
		}
	}

	/**
	 * Create a HTML reset input element.
	 *
	 * @param  string  $value
	 * @param  array   $attributes
	 * @return string
	 */
	public function reset($value, $attributes = array())
	{
		return $this->input('reset', null, $value, $attributes);
	}

	/**
	 * Create a HTML image input element.
	 *
	 * @param  string  $url
	 * @param  string  $name
	 * @param  array   $attributes
	 * @return string
	 */
	public function image($url, $name = null, $attributes = array())
	{
		$attributes['src'] = $this->url->asset($url);

		return $this->input('image', $name, null, $attributes);
	}

	/**
	 * Create a submit button element.
	 *
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	public function submit($value = null, $options = array())
	{
		$options['type'] = 'submit';
		return $this->button($value, $options);
	}

	/**
	 * Create a button element.
	 *
	 * @param  string  $value
	 * @param  array   $options
	 * @return string
	 */
	public function button($value = null, $options = array())
	{
		if ( ! array_key_exists('type', $options))
		{
			$options['type'] = 'button';
		}

		$pattern = (isset($options['pattern'])) ? $options['pattern'] : null ;

		$btn = 'btn';
		switch ($pattern) {
			case 'primary':
				$btn .= ' btn-primary';
				break;
			case 'success':
				$btn .= ' btn-success';
				break;
			case 'info':
				$btn .= ' btn-info';
				break;
			case 'warning':
				$btn .= ' btn-warning';
				break;
			case 'danger':
				$btn .= ' btn-danger';
				break;
			case 'link':
				$btn .= ' btn-link';
				break;
			
			default:
				$btn .= ' btn-default';
				break;
		}

		if (!is_null($pattern)) {
			unset($options['pattern']);
		}

		$options = $this->addClass($options, $btn);

		$html = '<button'.$this->html->attributes($options).'>'.$value.'</button>';

		switch ($this->formType) {
			case 'form-horizontal':
				return 	$this->beginFormGroup().
						$this->beginHorizontalGroup(true).
						$html.
						$this->endHorizontalGroup().
						$this->endFormGroup();
				break;
			default:
				return $html;
		}
		return $html;
	}

	/**
	 * Get the action for a "url" option.
	 *
	 * @param  array|string  $options
	 * @return string
	 */
	protected function getUrlAction($options)
	{
		if (is_array($options))
		{
			return $this->url->to($options[0], array_slice($options, 1));
		}

		return $this->url->to($options);
	}

	/**
	 * Get the action for a "route" option.
	 *
	 * @param  array|string  $options
	 * @return string
	 */
	protected function getRouteAction($options)
	{
		if (is_array($options))
		{
			return $this->url->route($options[0], array_slice($options, 1));
		}

		return $this->url->route($options);
	}

	/**
	 * Get the action for an "action" option.
	 *
	 * @param  array|string  $options
	 * @return string
	 */
	protected function getControllerAction($options)
	{
		if (is_array($options))
		{
			return $this->url->action($options[0], array_slice($options, 1));
		}

		return $this->url->action($options);
	}

	/**
	* Get the MessageBag of errors that is populated by the
	* validator.
	*
	* @return \Illuminate\Support\MessageBag
	*/
	protected function getErrors()
	{
		return $this->session->get('errors');
	}

	/**
	* Get the first error for a given field, using the provided
	* format, defaulting to the normal Bootstrap 3 format.
	*
	* @param string $field
	* @param string $format
	* @return mixed
	*/
	protected function getFieldError($field, $format = '<span class="help-block">:message</span>')
	{
		if ($this->getErrors()) {
			$errors =$this->getErrors()->get($field, $format);
			if (is_array($errors)) {
				return implode("", $errors);
			}else if(is_string($errors)){
				return $errors;
			}
		}
	}

	/**
	* Return the error class if the given field has associated
	* errors, defaulting to the normal Bootstrap 3 error class.
	*
	* @param string $field
	* @param string $class
	* @return string
	*/
	protected function getFieldErrorClass($field, $class = 'has-error')
	{
		return $this->getFieldError($field) ? $class : null;
	}

}