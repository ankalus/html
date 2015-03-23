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
		$method = array_get($options, 'method', 'post');

		$form_type = array_get($options, 'formType', NULL);

		unset($options['formType']);

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

	public function openHorizontal(array $options = array()){
		$this->open($options);
	}


	public function openInline(array $options = array()){
		$this->open($options);
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
				if (array_key_exists('class', $options)) {
					$options['class'] = "col-sm-2 control-label " . $options['class'];
				}else{
					$options['class'] = "col-sm-2 control-label";
				}
		}

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
		if (array_key_exists('class', $options)) {
			$options['class'] = "form-control " . $options['class'];
		}else{
			$options['class'] = "form-control";
		}
		return $options;
	}

	protected function formGroup($options = array())
	{
		if (array_key_exists('class', $options)) {
			$options['class'] = "form-group " . $options['class'];
		}else{
			$options['class'] = "form-group";
		}
		return $options;
	}

	public function beginFormGroup($options = array())
	{
		$options = $this->formGroup($options);
		$options = $this->html->attributes($options);
		return '<div '.$options.'>';
	}

	public function endFormGroup()
	{
		return '</div>';
	}

	public function beginHorizontalGroup($margin = false)
	{
		if ($margin) {
			return '<div class="col-sm-offset-2 col-sm-10">';
		}
		return '<div class="col-sm-10">';
	}

	public function endHorizontalGroup()
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

		switch ($this->formType) {
			case 'form-horizontal':
				$html  = $this->beginFormGroup();
				$html .= $label;
				$html .= $this->beginHorizontalGroup();
				$html .= $input;
				$html .= $this->endFormGroup(). $this->endHorizontalGroup();
				return $html;
			
			default:
				$html  = $this->beginFormGroup();
				$html .= $label;
				$html .= $input;
				$html .= $this->endFormGroup();
				return $html;
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
		return $this->checkable('checkbox', $name, $value, $checked, $options);
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
		
		if ($inline) {
			$html = '';
		}else{
			$html = '<div class="' .$type.$disabled. '">';
		}

		$html .= '<label' .$inline. '>';
		$html .= $this->input($type, $name, $value, $options);
		$html .= $label;
		$html .= '</label>';
		
		if (!$inline) {
			$html .= '</div>';
		}

		if ($inline) 
			return $html;

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
	public function button($value = null, $type = "default", $options = array())
	{
		if ( ! array_key_exists('type', $options))
		{
			$options['type'] = 'button';
		}

		$btn = 'btn';
		switch ($type) {
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

		if ( array_key_exists('class', $options)) {
			$options['class'] = $btn. ' ' .$options['class'];
		}else{
			$options['class'] = $btn;
		}

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

}