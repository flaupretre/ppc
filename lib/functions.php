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
// Code blocks are ouput as-is.
// On other text, '_', '*', and '<' chars are translated. To display italic
// or bold text, original text must be surrounded with double modifiers ('__'
// or '**'.

function mstring($str)
{
if ((!starts_with($str,"\t")) && (!starts_with($str,'    ')))
	{ // Not a code block
	$str=str_replace('_','\_',$str);
	$str=str_replace('\_\_','_',$str);
	$str=str_replace('*','\*',$str);
	$str=str_replace('\*\*','**',$str);
	$str=str_replace('<','&lt;',$str);
	}

return $str;
}
