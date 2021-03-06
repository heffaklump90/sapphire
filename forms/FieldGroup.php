<?php
/**
 * Lets you include a nested group of fields inside a template.
 * This control gives you more flexibility over form layout.
 * 
 * Note: the child fields within a field group aren't rendered using FieldHolder().  Instead,
 * SmallFieldHolder() is called, which just prefixes $Field with a <label> tag, if the Title is set.
 * 
 * <b>Usage</b>
 * 
 * <code>
 * new FieldGroup(
 * 	new FieldGroup(
 * 		new HeaderField('FieldGroup 1'),
 * 		new TextField('Firstname')
 * 	),
 * 	new FieldGroup(
 * 		new HeaderField('FieldGroup 2'),
 * 		new TextField('Surname')
 * 	)
 * )
 * </code>
 * 
 * <b>Adding to existing FieldGroup instances</b>
 * 
 * <code>
 * function getCMSFields() {
 * 	$fields = parent::getCMSFields();
 * 	
 * 	$fields->addFieldToTab(
 * 		'Root.Content', 
 * 		new FieldGroup(
 * 			new TimeField("StartTime","What's the start time?"),
 * 			new TimeField("EndTime","What's the end time?")
 * 		),
 * 		'Content'
 * 	);	
 * 	
 * 	return $fields;
 * 		
 * }
 * </code>
 * 
 * @package forms
 * @subpackage fields-structural
 */
class FieldGroup extends CompositeField {
	protected $zebra;
	public $subfieldParam = "SmallFieldHolder";
	
	function __construct($arg1 = null, $arg2 = null) {
		if(is_array($arg1) || is_a($arg1, 'FieldSet')) {
			$fields = $arg1;
		
		} else if(is_array($arg2) || is_a($arg2, 'FieldList')) {
			$this->title = $arg1;
			$fields = $arg2;
		
		} else {
			$fields = func_get_args();
			if(!is_object(reset($fields))) $this->title = array_shift($fields);
		}
			
		parent::__construct($fields);
	}
	
	/**
	 * Returns the name (ID) for the element.
	 * In some cases the FieldGroup doesn't have a title, but we still want 
	 * the ID / name to be set. This code, generates the ID from the nested children
	 */
	function Name(){
		if(!$this->title) {
			$fs = $this->FieldList();
			$compositeTitle = '';
			$count = 0;
			foreach($fs as $subfield){
				$compositeTitle .= $subfield->getName();
				if($subfield->getName()) $count++;
			}
			if($count == 1) $compositeTitle .= 'Group';
			return preg_replace("/[^a-zA-Z0-9]+/", "", $compositeTitle);
		}

		return preg_replace("/[^a-zA-Z0-9]+/", "", $this->title);
	}

	/**
	 * Returns a set of <span class="subfield"> tags, each containing a sub-field.
	 * You can also use <% control FieldSet %>, if you'd like more control over the generated HTML
	 * 
	 * @todo Shouldn't use SmallFieldHolder() (very difficult to style), 
	 * it is easier to overwrite the <div class="field"> behaviour in a more specific class
	 */
	function Field() {
		$fs = $this->FieldList();
    	$spaceZebra = isset($this->zebra) ? " fieldgroup-$this->zebra" : '';
    	$idAtt = isset($this->id) ? " id=\"{$this->id}\"" : '';
		$content = "<div class=\"fieldgroup$spaceZebra\"$idAtt>";

		$count = 1;
		foreach($fs as $subfield) {
			$childZebra = (!isset($childZebra) || $childZebra == "odd") ? "even" : "odd";
			if($subfield->hasMethod('setZebra'))  {
				$subfield->setZebra($childZebra);
			}

			//label the first and last fields of each surrounding div
			if ($count == 1) $firstLast = "first";
			elseif ($count == count($fs)) $firstLast = "last";
			else $firstLast = '';

			$content .= "<div class=\"fieldgroup-field $firstLast\">" . $subfield->{$this->subfieldParam}() . "</div>";
			$count++;
		}
		$content .= "</div>";
		
		return $content;
	}
	
	public function setID($id) {
		$this->id = Convert::raw2att($id);
	}
  
	/**
	 * Set an odd/even class
	 */
  	function setZebra($zebra) {
	    if($zebra == 'odd' || $zebra == 'even') $this->zebra = $zebra;
	    else user_error("setZebra passed '$zebra'.  It should be passed 'odd' or 'even'", E_USER_WARNING);
	    return $this;
 	}
  
	function FieldHolder() {
		$Title = $this->XML_val('Title');
		$Message = $this->XML_val('Message');
		$MessageType = $this->XML_val('MessageType');
		$RightTitle = $this->XML_val('RightTitle');
		$Type = $this->XML_val('Type');
		$extraClass = $this->XML_val('extraClass');
		$Name = $this->XML_val('Name');
		$Field = $this->XML_val('Field');
		
		$titleBlock = (!empty($Title)) ? "<label class=\"left\">$Title</label>" : "";
		$messageBlock = (!empty($Message)) ? "<span class=\"message $MessageType\">$Message</span>" : "";
		$rightTitleBlock = (!empty($RightTitle)) ? "<label class=\"right\">$RightTitle</label>" : "";
		$id = $Name ? ' id="$Name"' : '';

		return <<<HTML
<div$id class="field $Type $extraClass">$titleBlock<div class="middleColumn">$Field</div>$rightTitleBlock$messageBlock</div>
HTML;
	}
	
	function Message() {
		$fs = $this->FieldList();
		foreach($fs as $subfield) {
			if($m = $subfield->Message()) $message[] = $m;
		}
		if(isset($message)) return implode(",  ", $message) . ". ";
	}	
	
	function MessageType(){
		$fs = $this->FieldList();
		foreach($fs as $subfield) {
			if($m = $subfield->MessageType()) $MessageType[] = $m;
		}
		if(isset($MessageType)) {
			return implode(".  ", $MessageType);
		}
	}
	
	function php($data){
		return;
	}
	
}

