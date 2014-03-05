<?php

//============================================================================
// A shell function's argument
//============================================================================

class Argument
{
public $name;
public $text;

//---------

public function __construct($name,$text)
{
$this->name=$name;
$this->text=trim($text);
}

//---------

public function append($text)
{
$this->text.="\n".trim($text);
}

} // End of class Argument

//============================================================================
?>