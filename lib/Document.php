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
foreach ($this->sections as $section) ksort($section->funcs);
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

public function render_md($output_dir)
{
$flinks=array();
$mfp=fopen("$output_dir/Home.md",'w');
if (!is_null($GLOBALS['main_prefix']))
	fwrite($mfp,file_get_contents($GLOBALS['main_prefix']));

foreach ($this->sections as $section)
	{
	if ($section->name=='-')
		{
		fwrite($mfp,"\n\n-----------\n\n");
		continue;
		}
	$url=$section->fname();
	fwrite($mfp,"###- [".$section->name."]($url)\n");
	$fpath="$output_dir/$url.md";
	$fp=fopen($fpath,'w');
	fwrite($fp,'#'.mstring($section->name)."\n\n");
	fwrite($fp,mstring($section->text)."\n");
	if (count($section->funcs))
		{
		fwrite($fp,"\n----------\n>###Functions\n");
		foreach($section->funcs as $fname => $f)
			{
			$flinks[$fname]=$url.'#'.$fname;
			fwrite($fp,"\n----------\n<a name=\"$fname\"></a>\n"
				.'##'.mstring($fname)."\n"
				.'**'.mstring($f->summary)."**\n"
				."\n".mstring($f->text)."\n"
				."####Arguments\n");
			if (count($f->args))
				{
				foreach($f->args as $arg)
					fwrite($fp,"- ".mstring($arg->name).": ".mstring($arg->text)."\n");
				}
			else fwrite($fp,"None\n");
			fwrite ($fp,"####Returns\n".mstring($f->returns)."\n"
				."####Displays\n".mstring($f->displays)."\n");
			}
		}
	fclose($fp);
	}

//-- Finish main page

fwrite($mfp,"\n\n-----------\n\n##[Function index](_index)\n\n");
if (!is_null($GLOBALS['main_suffix']))
	fwrite($mfp,file_get_contents($GLOBALS['main_suffix']));
fclose($mfp);

//-- Generate index

ksort($flinks);
$ifp=fopen("$output_dir/_index.md",'w');
fwrite($ifp,"#Function index\n\n---------\n\n");
foreach($flinks as $fname => $url) fwrite($ifp,mstring("- [$fname]($url)\n"));
fclose($ifp);
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