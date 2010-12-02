<?php

class Func
{
public $name;		// string
public $summary;	// string
public $text;		// string
public $args;		// array
public $returns;	// string
public $displays;	// string

private $last_arg;	// string

//------

public function __construct()
{
$this->summary=$this->text=$this->returns=$this->displays='';
$this->args=array();
}

//------

public function new_arg($name,$text)
{
$this->args[$this->last_arg=trim($name)]=trim($text);
}

//------

public function append_arg_text($text)
{
append_string($this->args[$this->last_arg],$text);
}

} // End of class Func

//-------------------

class Section
{
public $name;	// String
public $funcs;	// Array

//----

public function __construct($name)
{
$this->name=trim($name);
$this->funcs=array();
}

#-----

public function add_func($f)
{
$this->funcs[$f->name]=$f;
}

} // End of class Section

//-------------------

function starts_with($str1,$str2)
{
return ((strlen($str1) >= strlen($str2))&&(substr($str1,0,strlen($str2))===$str2));
}

//-------------------

function trim_comments($str)
{
return trim($str,"# \t");
}

//-------------------

function append_string(&$str1,$str2)
{
$str2=trim($str2);
if ($str2!='')
	{
	if ($str2{0}=='-')
		{
		$str2=trim(substr($str2,1));
		$pad_char="\n";
		}
	else $pad_char=' ';
	if ($str1 != '') $str1 .=$pad_char;
	$str1 .= $str2;
	}
}

//-------------------

define('OUT'	,0);	// Out of function
define('START'	,1);	// Comment block detected
define('TEXT'	,2);	// In text
define('ARGS'	,3);	// In args
define('RETURNS',4);	// In return
define('DISPLAYS',5);	// In display
define('NAME'	,6);	// Looking for func name

function extract_doc($path)
{
$sections=array(); // Section array
$csection=null;	// Current section
$cfunc=null; // Current func object

$state=OUT;

foreach(file($path) as $line)
	{
	$line=trim($line);
	switch ($state)
		{
		case OUT:
			if (starts_with($line,'# Section: '))
				{
				$csection=$sections[]=new Section(substr($line,11));
				}
			elseif (starts_with($line,'##'))
				{
				$cfunc=new Func();
				$state=START;
				}
			break;

		case START:
			$cfunc->summary=trim_comments($line);
			$state=TEXT;
			break;

		case TEXT:
			$line=trim_comments($line);
			if (starts_with($line,'Args:'))
				{
				$state=ARGS;
				break;
				}
			append_string($cfunc->text,$line);
			break;

		case ARGS:
			$line=trim_comments($line);
			if (starts_with($line,'Returns:'))
				{
				$state=RETURNS;
				append_string($cfunc->returns,trim(substr($line,8)));
				break;
				}
			if ($line=='') break;
			if ($line{0}=='$') // New arg;
				{
				$line=trim($line,'$');
				$a=explode(':',$line,2);
				if (count($a)==2) $cfunc->new_arg($a[0],$a[1]);
				}
			else $cfunc->append_arg_text($line);
			break;

		case RETURNS:
			$line=trim_comments($line);
			if (starts_with($line,'Displays:'))
				{
				$state=DISPLAYS;
				append_string($cfunc->displays,trim(substr($line,9)));
				break;
				}
			append_string($cfunc->returns,$line);
			break;

		case DISPLAYS:
			$line=trim_comments($line);
			if (starts_with($line,'---'))
				{
				$state=NAME;
				break;
				}
			append_string($cfunc->displays,$line);
			break;

		case NAME:
			if (strpos($line,'()')!==false)
				{
				
				$cfunc->name=trim($line,"() \t");
				if ($cfunc->name{0} != '_') $csection->add_func($cfunc);
				$state=OUT;
				$cfunc=null;
				}
			break;
		}
	}

foreach ($sections as $section) ksort($section->funcs);

return $sections;
}

//----------------

function hstring($str)
{
return str_replace("\n",'</p><p>',htmlspecialchars($str));
}

//----------------

function display_html($sections)
{
echo '<p>{toc}</p><p>&nbsp;</p><hr/>';

foreach ($sections as $section)
	{
	echo '<h1>'.hstring($section->name)."</h1>\n";
	foreach($section->funcs as $fname => $f)
		{
		echo '<h2>'.hstring($fname)."</h2>\n";
		echo '<p><b>'.hstring($f->summary)."</b></p>\n";
		echo '<p>'.hstring($f->text)."</p>\n";
		echo '<table width=100% border=0><tr><td width=50>&nbsp;</td><td>';
		echo '<table border=1 cellpadding=5 style="border-collapse: collapse;" width=100%>';
		echo '<tr><td align=center width=50><b><i>Args</i></b></td>';
		if (count($f->args))
			{
			echo '<td style="padding: 0;"><table border=1 cellpadding=5'
				.' style="border-collapse: collapse;" width=100%>';
			foreach($f->args as $aname => $adoc)
				{
				echo '<tr><td width=20 align=center>'.hstring($aname).'</td><td>'.hstring($adoc)
					."</td></tr>\n";
				}
			echo '</table>';
			}
		else echo "<td>None";
		echo "</td></tr>";

		echo '<tr><td align=center width=50><b><i>Returns</i></b></td><td>'.hstring($f->returns)
			."</td></tr>\n";
		
		echo '<tr><td align=center width=50><b><i>Displays</i></b></td><td>'.hstring($f->displays)
			."</td></tr>\n";
		
		echo "</table>\n";
		echo '</td><td width=50>&nbsp;</td></tr></table>';
		}
	}
}

//======================================== MAIN =================

$doc=extract_doc($argv[1]);

switch($argv[2])
	{
	case 'html':
		display_html($doc);
		break;

	default:
		echo $argv[2].": Unknown output format\n";
	}
