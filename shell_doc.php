<?php
/*============================================================================
*
* Syntax: php <script> [options] <sources>...
*
* -e <string>: exclude functions starting with <string>
* -c <string>: Clear <string> prefix in function names
* -S: Sort functions by name
* -o <dir>: Write output files in <dir> (default=dirname(source)/out)
* -f <format>: Output format (Default=md)
* -p <file>: Put file at the beginning of main page
* -e <file>: Put file at the end of main page
* -H <file>: Global header
* -F <file>: Global footer
*
*===========================================================================*/

include(dirname(__FILE__).'/external/phool.phk');

include(dirname(__FILE__).'/lib/functions.php');
include(dirname(__FILE__).'/lib/Document.php');
include(dirname(__FILE__).'/lib/Argument.php');
include(dirname(__FILE__).'/lib/Func.php');
include(dirname(__FILE__).'/lib/Section.php');
include(dirname(__FILE__).'/lib/SH_Document.php');

//======================================== MAIN =================

//--- Get options

$sort_flag=false;
$format='gfm';
$exclude_prefixes=array();
$clear_prefixes=array();
$main_prefix=$main_suffix=$global_footer=$global_header=null;
$output_dir=null;

$args=PHO_Getopt::readPHPArgv();
array_shift($args);
list($options,$args2)=PHO_Getopt::getopt2($args,'Se:c:f:o:p:s:F:H:');
foreach($options as $option)
	{
	list($opt,$val)=$option;
	switch($opt)
		{
		case 'S':
			$sort_flag=true;
			break;
		case 'e':
			if (is_array($val)) $exclude_prefixes=$val;
			else $exclude_prefixes[]=$val;
			break;
		case 'c':
			if (is_array($val)) $clear_prefixes=$val;
			else $clear_prefixes[]=$val;
			break;
		case 'f':
			$format=$val;
			break;
		case 'o':
			$output_dir=$val;
			break;
		case 'p':
			$main_prefix=$val;
			break;
		case 's':
			$main_suffix=$val;
			break;
		case 'F':
			$global_footer=$val;
			break;
		case 'H':
			$global_header=$val;
			break;
		}
	}

$source=$argv[$argc-1];

//---- Extract doc from file

$doc=new SH_Document();
foreach($args2 as $source)
	{
	PHO_Display::info("Reading file $source");
	$doc->read($source);
	}
if ($sort_flag) $doc->sort_functions();

//---- Format output

if (is_null($output_dir)) $output_dir=dirname($source).'/out';

$doc->render($format,$output_dir);

//============================================================================
?>
