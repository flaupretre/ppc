<?php

//============================================================================
// A section
//============================================================================

class Section
{
public $name;	// String
public $text;
public $funcs;	// Array

//----

public function __construct($name)
{
$this->name=trim($name);
$this->text='';
$this->funcs=array();
}

#-----

public function add_func($f)
{
foreach($GLOBALS['exclude_prefixes'] as $prefix)
	{
	if (starts_with($f->name,$prefix)) return;
	}

foreach($GLOBALS['clear_prefixes'] as $prefix)
	{
	if (starts_with($f->name,$prefix))
		{
		$f->name=substr($f->name,strlen($prefix));
		break;
		}
	}

$this->funcs[$f->name]=$f;
}

#-----
# Derive a name usable in URLs or filenames

public function fname()
{
return str_replace(' ','-',$this->name);
}

} // End of class Section
//============================================================================
?>