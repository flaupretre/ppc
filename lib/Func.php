<?php

//============================================================================
// A shell function
//============================================================================

class Func
{
public $name;		// string
public $summary;	// string
public $text;		// string
public $args;		// array
public $returns;	// string
public $displays;	// string

//------

public function __construct()
{
$this->summary=$this->text=$this->returns=$this->displays='';
$this->args=array();
}

//------

public function new_arg($name,$text)
{
$this->args[]=new Argument($name,$text);
}

//------

public function append_arg_text($text)
{
$this->args[count($this->args)-1]->append($text);
}

} // End of class Func

//============================================================================
?>