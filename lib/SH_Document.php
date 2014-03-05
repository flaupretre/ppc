<?php

//============================================================================
// A shell document
//============================================================================

class SH_Document extends Document
{

const OUT=0;		// Out of function
const SDOC=1;		// In section doc
const FSTART=2;		// Comment block detected
const TEXT=3;		// In text
const ARGS=4;		// In args
const RETURNS=5;	// In return
const DISPLAYS=6;	// In display
const NAME=7;		// Looking for func name


//-----------

public function __construct()
{
parent::__construct();
}

//-----------

function read($path)
{
$csection=null;	// Current section
$cfunc=null; // Current func object

$state=self::OUT;
$lnum=0;
$ignored_lines=0;
foreach(file($path) as $line)
	{
	$lnum++;
	if ($ignored_lines)
		{
		$ignored_lines--;
		echo "Ignoring($ignored_lines): $line\n";
		continue;
		}
	$line=trim($line,"\n");
	switch ($state)
		{
		case self::OUT:
			if (starts_with($line,'# Section: '))
				{
				$csection=$this->sections[]=new Section(substr($line,11));
				$state=self::SDOC;
				}
			elseif (starts_with($line,'##'))
				{
				$cfunc=new Func();
				$state=self::FSTART;
				}
			break;

		case self::SDOC:
			$line=trim_comments($line);
			if (starts_with($line,'-----------------')) break;
			if (starts_with($line,'===================')) $state=self::OUT;
			else append_string($csection->text,$line);
			break;

		case self::FSTART:
			$cfunc->summary=trim(trim_comments($line));
			$state=self::TEXT;
			break;

		case self::TEXT:
			$line=trim_comments($line);
			if (starts_with(trim($line),'Args:')) $state=self::ARGS;
			else append_string($cfunc->text,$line);
			break;

		case self::ARGS:
			$line=ltrim(trim_comments($line));
			if (starts_with($line,'Returns:'))
				{
				$state=self::RETURNS;
				append_string($cfunc->returns,substr($line,8));
				}
			else
				{
				if ((strlen($line)>0) && ($line{0}=='$')) // New arg;
					{
					$a=explode(':',$line,2);
					if (count($a)==2) $cfunc->new_arg($a[0],$a[1]);
					}
				else $cfunc->append_arg_text($line);
				}
			break;

		case self::RETURNS:
			$line=ltrim(trim_comments($line));
			if (starts_with($line,'Displays:'))
				{
				$state=self::DISPLAYS;
				append_string($cfunc->displays,ltrim(substr($line,9)));
				}
			else append_string($cfunc->returns,$line);
			break;

		case self::DISPLAYS:
			$line=ltrim(trim_comments($line));
			if (starts_with($line,'---')) $state=self::NAME;
			else append_string($cfunc->displays,$line);
			break;

		case self::NAME:
			// 2 possible syntaxes : 'func()' or 'function func'. 2nd is better
			$funcname=null;
			if (strpos($line,'()')!==false) $funcname=trim($line,"() \t");
			if (strpos($line,'function ')===0) $funcname=trim(substr($line,9),"() \t");
			if (!is_null($funcname))
				{
				$cfunc->name=$funcname;
				$csection->add_func($cfunc);
				$state=self::OUT;
				}
			break;
		}
	}
}

//-----------
} // End of class SH_Document
//============================================================================
?>