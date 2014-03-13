<?php

//============================================================================
// Utility functions
//============================================================================

function starts_with($str1,$str2)
{
return ((strlen($str1) >= strlen($str2))&&(substr($str1,0,strlen($str2))===$str2));
}

//-------------------

function trim_comments($str)
{
return substr($str,2);
}

//-------------------

function append_string(&$str1,$str2)
{
if ($str1=='') $str1=$str2;
else $str1.=("\n".$str2);
}

//------ HTML ----------

function hstring($str)
{
return str_replace("\n",'</p><p>',htmlspecialchars($str));
}

//------- MARKDOWN ---------
// Lines containing code are ouput as-is.
// On other text, '_', '*', and '<' chars are translated. To display italic
// or bold text, original text must be surrounded with double modifiers ('__'
// or '**'.

function mstring($str)
{
if ($str==='') return '';

// A '-' may be preceeded by tabs for better readability in the source filecode.
// It shouldn't be considered as a code line.

$str=preg_replace('/^\t+-/m','-',$str);

$res=array();
foreach(explode("\n",$str) as $line)
	{
	if ((!starts_with($line,"\t")) && (!starts_with($line,'    ')))
		{ // Not a code line
		$line=str_replace('_','\_',$line);
		$line=str_replace('\_\_','_',$line);
		$line=str_replace('*','\*',$line);
		$line=str_replace('\*\*','**',$line);
		$line=str_replace('<','&lt;',$line);
		}
	$res[]=$line;
	}
return implode("\n",$res);
}
