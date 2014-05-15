<?php

//============================================================================
// A document
//============================================================================

class Document
{
//-----------

public $sections;

//-----------

public function __construct()
{
$this->sections=array();
}

//----------------

public function sort_functions()
{
echo "Sorting functions\n";
foreach ($this->sections as $section) ksort($section->funcs);
}

//-----------

public function open_page($path)
{
$fp=fopen($path,'w');
if ($fp===false) throw new Exception("Cannot open $path for writing");
if (!is_null($GLOBALS['global_header']))
	fwrite($fp,file_get_contents($GLOBALS['global_header']));
return $fp;
}

//-----------

public function close_page($fp)
{
if (!is_null($GLOBALS['global_footer']))
	fwrite($fp,file_get_contents($GLOBALS['global_footer']));
fclose($fp);
}

//-----------

public function render($format,$output_dir)
{
$method='render_'.$format;
if (!method_exists($this,$method))
	throw new Exception("Unsupported rendering format: $format");
$this->$method($output_dir);
}

//-----------
// Github wiki renderer.
// Syntax: Github-flavored Markdown

public function render_gfm($output_dir)
{
$flinks=array();
$docs=array('Home' => '');
if (!is_null($GLOBALS['main_prefix']))
	$docs['Home']=file_get_contents($GLOBALS['main_prefix']);

foreach ($this->sections as $section)
	{
	if ($section->name=='-')
		{
		$docs['Home'].="\n\n-----------\n\n";
		continue;
		}
	$url=$section->fname();
	$docs['Home'].="###- [".$section->name."]($url)\n";
	$doc=mstring($section->text)."\n";
	if (count($section->funcs))
		{
		$doc.="\n----------\n>###Functions\n";
		foreach($section->funcs as $fname => $f)
			{
			$flinks[$fname]=$url.'#'.$fname;
			$doc.="\n----------\n<a name=\"$fname\"></a>\n"
				.'##'.mstring($fname)."\n"
				.'**'.mstring($f->summary)."**\n"
				."\n".mstring($f->text)."\n"
				."\n####Arguments\n";
			if (count($f->args))
				{
				foreach($f->args as $arg)
					$doc.="- ".mstring($arg->name).": ".mstring($arg->text)."\n";
				}
			else $doc.="None\n";
			$doc.="\n####Returns\n".mstring($f->returns)."\n"
				."\n####Displays\n".mstring($f->displays)."\n";
			}
		}
	$docs[$url]=$doc;
	}

//-- Finish main page

$docs['Home'].="\n\n-----------\n\n##[Function index](Index)\n\n";
if (!is_null($GLOBALS['main_suffix']))
	$docs['Home'].=file_get_contents($GLOBALS['main_suffix']);

//-- Resolve function links

echo("Resolving function links\n");

foreach($docs as $name => $doc)
	{
	while(($pos=strpos($doc,'[function:'))!==false)
		{
		$pos2=strpos($doc,']',$pos+10);
		if ($pos2===false) die("Cannot find end of function link (section=$name, offset=$pos)");
		$func=str_replace('\_','_',substr($doc,$pos+10,$pos2-$pos-10));
		if (!array_key_exists($func,$flinks))
			die("Link to unknown function: $func (section=$name, offset=$pos)");
		$doc=substr_replace($doc,'['.str_replace('_','\\\\_',$func).']('.$flinks[$func].')',$pos,$pos2-$pos+1);
		}
	$docs[$name]=$doc;
	}

//-- Generate index

ksort($flinks);
$doc="#Function index\n\n---------\n\n";
foreach($flinks as $fname => $url) $doc.=mstring("- [$fname]($url)\n");
$docs['Index']=$doc;

//-- Generate files

foreach($docs as $name => $doc)
	{
	$fp=$this->open_page("$output_dir/$name.md");
	fwrite($fp,$doc);
	$this->close_page($fp);
	}
}

//----------------
#TODO: Modify this code, which was taken from the old shell doc generation
#	   software. This function must offer the same features as render_md().

function render_html($output_dir)
{
foreach ($this->sections as $section)
	{
	if (count($section->funcs)==0) continue;
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
	echo "\n";
	}
echo "\n";
}

//-----------
} // End of class Document
//============================================================================
?>