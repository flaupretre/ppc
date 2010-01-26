<?php
//
// Preprocesseur pour doc html.
//
// Fabrique 1 doc HTML
// et un nombre variable de scripts destinations. Les fichiers generes
// vont dans un sous-repertoire 'ppc' (override avec env var PPC_DIR).
//
// Syntaxe dans le texte :
// {ppc:<plugin> [out[=<n>]][,hidden]}
// Plugins actuels :
// code : Formatte un bloc de code dans la doc, envoie le code ASCII vers out
// note : Formatte une note
// asis : Sort le code HTML tel quel, envoie l'ASCII vers output
// comment : Sort le code HTML tel quel, envoie l'ASCII precede de '#--- '
//
// F. Laupretre
//
//============================================================================

error_reporting(E_ALL);
ini_set('display_errors',true);
set_include_path('.:'.get_include_path());

include dirname(__FILE__).'/config.php';

define('NL',"\n");

//----------------------------

$glob_toc=null;
$glob_toc_string='';
$tag=array(0,0,0,0,0,0,0,0,0);
$GLOBALS['outputs']=$GLOBALS['out_array']=array();
$GLOBALS['source_info']=array('source' => '', 'valid_on' => array());
$GLOBALS['text_stack']=array();

add_output('mk','mk.sh.%');
add_output('deps','deps.res.%');
add_output('param','param.%');
add_output('os_vars','os_vars.%');

$GLOBALS['features']=array();

array_shift($argv);

foreach($argv as $source)
	{
	unset($GLOBALS['notoc']);
	$ppc_dir=((getenv('PPC_DIR')!==false)
		? getenv('PPC_DIR') : dirname($source).'/ppc');
	if (!is_dir($ppc_dir)) error("Le repertoire $ppc_dir n'existe pas");

	echo "INFO: Processing $source\n";
	ob_start();
	$GLOBALS['source_stack']=array();
	include_source($source); // interprete les codes PHP du document
	$buf=ob_get_clean();
	$buf=ppc($source,$buf);
	if (!isset($GLOBALS['notoc']))
		update_toc($source,$buf,$tag,$glob_toc_string);
	$buf=botMosSmilies(correct_html($buf));
	file_put_contents($ppc_dir.'/_'.basename($source),$buf);
	}

foreach($GLOBALS['outputs'] as $index => $file)
	{
	if ($GLOBALS['out_array'][$index]!=='') // Non vide
		{
		if (strpos($file,'%')!==false)
			{
			if (isset($GLOBALS['OS_ID']))
				$f=str_replace('%',$GLOBALS['OS_ID'],$file);
			else continue;
			}
		else $f=$file;
		if (DEBUG) echo "Writing $f\n";
		file_put_contents($ppc_dir.'/'.$f,$GLOBALS['out_array'][$index]);
		}
	}

// Build global TOC file

if (!is_null($glob_toc))
   {
   $glob_toc=dirname($ppc_file).'/'.$glob_toc;
   $buf=file_get_contents($glob_toc);
   toc_replace($buf,$glob_toc_string);
   file_put_contents($ppc_dir.'/_'.basename($glob_toc),$buf);
   }

exit(0);

//=============================================================================

function warning($msg)
{
fwrite(STDERR,"* WARNING : $msg\n");
}

//----------------------------

function error($msg)
{
fwrite(STDERR,"* ERROR : $msg\n");
exit(1);
}

//----------------------------

function ppc($source,$buf)
{
$def_options=array('source' => $source);

while (true)
	{
	if (($start=strpos($buf,'{ppc:'))===false) break;

	if (($end=strpos($buf,'{/}'))===false)
		{  echo substr($buf,$start,200)."\n"; error('Cannot find ppc end $buf'); }
	if ($end<$start) error('end before start : '.substr($buf,$end,200)."\n");

	if (($start2=strrpos(substr($buf,$start,$end-$start),'{ppc:'))!=0)
		$start += $start2; // Correction si imbrication

	if (($start_end=strpos($buf,'}',$start+5))===false)
		error('Cannot find ppc start end');
	$start_end++;
	$string=trim(substr($buf,$start+5,$start_end-$start-6));
	$plugin=strtok($string," \t");
	$optstr=strtok(' ');
	$options=$def_options;
	$options['*']=$optstr;
	if ($optstr!==false)
		{
		foreach(explode(',',$optstr) as $optunit)
			{
			$a=explode('=',trim($optunit));
			if (count($a)==0) continue;
			if (count($a)==1) $a[]=true;
			$options[$a[0]]=$a[1];
			}
		}

	$buf_content=ppc($source,substr($buf,$start_end,$end-$start_end));
	if (DEBUG) echo "Calling ppc:$plugin - content=<$buf_content>\n";
	ob_start();
	$func="ppc_$plugin";
	$output='';
	$func($buf_content,$options,$output);
	$repl_buf=(isset($options['hidden'])) ? '' :ob_get_contents();
	ob_end_clean();
	$buf=substr_replace($buf,$repl_buf,$start,$end-$start+3);
	if (isset($options['out']))
		{
		if ($options['out']===true) $options['out']='mk'; // Default script
		if (DEBUG) echo "Writing to channel(s) : ".$options['out']."\n";
		foreach(explode(',',$options['out']) as $key)
			$GLOBALS['out_array'][$key] .= $output;
		}
	}

return $buf;
}

//-----------------------

function replace_all($search,$replace,$subject)
{
$obuf='';
while ($obuf!=$subject)
	{
	$obuf=$subject;
	$subject=str_replace($search,$replace,$subject);
	}
return $subject;
}

//-----------------------
//-- Supprime les paragraphes vides

function correct_html($buf)
{
$buf=str_replace('</p>','',$buf);
$buf=str_replace('<p/>','<p>',$buf);
$buf=str_replace("\r",'',$buf);

$buf=replace_all(" \n","\n",$buf);
$buf=replace_all("\n\n","\n",$buf);
$buf=replace_all("<p><p>","<p>",$buf);
$buf=replace_all("<p>\n<p>","<p>",$buf);

$buf=str_replace('<p><table ','<table ',$buf);
$buf=str_replace("<p>\n<table ","\n<table ",$buf);
$buf=str_replace("<p><h","<h",$buf);
$buf=str_replace("<p>\n<h","\n<h",$buf);

return $buf;
}

//-----------------------

function raw2html($buf)
{
$buf=htmlspecialchars($buf);
$buf=str_replace("\t",'&nbsp;&nbsp;&nbsp; ',$buf);
$buf=str_replace('  ','&nbsp; ',$buf);
$buf=str_replace("\n","<br />\n",$buf);
return $buf;
}

//-----------------------

function rawify1($buf)
{
$buf=str_replace('</p>','',$buf);
$buf=eregi_replace('<p[^>]*>','<br/>',$buf);
$buf=str_replace("\t",'&nbsp;&nbsp;&nbsp; ',$buf);
return str_replace('  ','&nbsp; ',$buf);
}

//-----------------------

function rawify($buf)
{
$output=str_replace("\r",'',rawify1($buf));
$output=eregi_replace("<br[^>]*> *\n","<new line with break>",$output);
$output=str_replace("\n",' ',$output);
$output=str_replace("<new line with break>","\n",$output);
$output=str_replace('&nbsp;',' ',$output);
return html_entity_decode(strip_tags($output));
}

//-----------------------

function codify($buf)
{
return CODE_START.rawify1($buf).CODE_END;
}

//-----------------------

function add_output($key,$file)
{
if (DEBUG) echo "add_output: key=$key, file=$file\n";
$GLOBALS['outputs'][$key]=$file;
$GLOBALS['out_array'][$key]='';
}

//-----------------------
// {ppc:output key=<val>}<filename>{/}

function ppc_output($buf,&$options,&$output)
{
add_output($options['key'],trim(rawify($buf)));
}

//-----------------------

function ppc_var($buf,&$options,&$output)
{
echo $GLOBALS[trim(rawify($buf))];
}

//-----------------------

function ppc_url($buf,&$options,&$output)
{
$GLOBALS['url']=trim(rawify($buf));
}

//-----------------------

function ppc_glob_toc($buf,&$options,&$output)
{
$GLOBALS['glob_toc']=trim(rawify($buf));
}

//-----------------------

function include_source($path)
{
echo "{ppc:source_start}$path{/}\n";

require($path);

echo "{ppc:source_end}{/}\n";
}

//-----------------------
// Change xterm title

function refresh_window_title(&$output)
{
$string=basename($GLOBALS['source_info']['source'],'.htm');

$output .= "echo \""
	.chr(27)
	."]0;`hostname`"
	.(($string!=='') ? (' ('.$string.')') : '')
	.chr(7)
	."\"\n";
}

//-----------------------

function ppc_source_start($buf,&$options,&$output)
{
if (!isset($options['out'])) $options['out']=OUT_DEFAULT;	// Force output

$file=basename($buf,'.htm');
$output="\necho \">>>>> Entering $file (`date`)\"\n";

$GLOBALS['source_stack'][]=$GLOBALS['source_info'];

$GLOBALS['source_info']=array('source' => $buf, 'valid_on' => array());
refresh_window_title($output);
}

//-----------------------

function ppc_source_end($buf,&$options,&$output)
{
if (!isset($options['out'])) $options['out']=OUT_DEFAULT;	// Force output

$file=basename($GLOBALS['source_info']['source'],'.htm');
$output="\necho \"<<<<< Exiting ".$GLOBALS['source_info']['source']." (`date`)\"\n";

$a=$GLOBALS['source_info']['valid_on'];
if ((array_search('all',$a)===false)
	&& isset($GLOBALS['OS_ID'])
	&& (array_search($GLOBALS['OS_ID'],$a)===false))
	{
	fprintf(STDERR,"** Warning: ".$GLOBALS['source_info']['source']
		." has not been validated on ".$GLOBALS['OS_ID']."\n");
	}

$GLOBALS['source_info']=array_pop($GLOBALS['source_stack']);
refresh_window_title($output);
}

//-----------------------

function ppc_push_text($buf,&$options,&$output)
{
$GLOBALS['text_stack'][]=$buf;
}

//-----------------------

function ppc_pop_text($buf,&$options,&$output)
{
if (count($GLOBALS['text_stack'])) echo array_pop($GLOBALS['text_stack']);
}

//-----------------------

function ppc_valid_on($buf,&$options,&$output)
{
$a=explode(',',rawify($buf));
foreach($a as $val)
	{
	if (($val=trim($val))==='') continue;
	$GLOBALS['source_info']['valid_on'][]=$val;
	}

array_unique($GLOBALS['source_info']['valid_on']);
}

//-----------------------

function ppc_common($buf,&$options,&$output)
{
include_source(dirname($options['source']).'/common/'.$buf.'.htm');
}

//-----------------------

function ppc_global($buf,&$options,&$output)
{
include_source(getenv('ACP').'/'.$buf.'.htm');
}

//-----------------------

function ppc_module($buf,&$options,&$output)
{
unset($GLOBALS['module_info']);
ppc_global('modules/'.$buf,$options,$output);
}

//-----------------------
// Ne peut pas être en ppc car les variables doivent etre initialisees avant
// l'include du fichier main.

function set_os_id($os_id)
{
$GLOBALS['OS_ID']=$os_id;
$dummy=null;
echo "{ppc:valid_on}$os_id{/}";

ppc_global('os/'.$GLOBALS['OS_ID'].'/cfg',$dummy,$dummy);

#-- Export variables to shell

$GLOBALS['out_array']['os_vars']=
	"OS=\"".$GLOBALS['OS']."\"\n".
	"OS_VERSION=\"".$GLOBALS['OS_VERSION']."\"\n".
	"RPM_OS=\"".$GLOBALS['RPM_OS']."\"\n".
	"SHELL=\"".$GLOBALS['SHELL']."\"\n".
	"SHLIB_EXT=\"".$GLOBALS['SHLIB_EXT']."\"\n".
	"RPATH_OPT=\"".$GLOBALS['RPATH_OPT']."\"\n".
	"ARCH=\"".$GLOBALS['ARCH']."\"\n".
	"export OS OS_VERSION RPM_OS SHELL SHLIB_EXT RPATH_OPT ARCH\n";
}

//-----------------------

function ppc_os($buf,&$options,&$output)
{
$dummy=null;
ppc_global('os/'.$GLOBALS['OS_ID'].'/'.trim($buf),$dummy,$dummy);
}

//-----------------------

function ppc_os_script($buf,&$options,&$output)
{
echo codify(str_replace('__BASE__','$BASE',raw2html(file_get_contents(
	getenv('ACP').'/os/'.$GLOBALS['OS_ID'].'/'.trim($buf)))));
}

//-----------------------

function ppc_code($buf,&$options,&$output)
{
// Format HTML code

echo codify($buf);

// Compute text version
// "<br>\n" -> "\n" but "\n" without <br> -> space

$output=rawify($buf)."\n";
}

//-----------------------

function ppc_note($buf,&$options,&$output)
{
echo '[:note] '.$buf;
}

//-----------------------
// Ces fonctions ne sortent rien nulle part

function ppc_hide($buf,&$options,&$output) {}
function ppc_valid($buf,&$options,&$output) {}

//-----------------------

function ppc_asis($buf,&$options,&$output)
{
echo $buf;

$output=str_replace("\r",'',$buf);
$output=html_entity_decode(strip_tags($output))."\n";
}

//-----------------------

function ppc_comment($buf,&$options,&$output)
{
echo $buf;

if (!isset($options['out'])) $options['out']=OUT_DEFAULT;	// Force output

$output="\n";
foreach(explode("\n",rawify($buf)) as $line) $output .="#-------- $line\n";
$output.="\n";
}

//-----------------------

function ppc_provides($buf,&$options,&$output)
{
foreach(explode(",",rawify($buf)) as $feature)
	{
	$feature=trim($feature);
	if (isset($GLOBALS['features'][$feature]))
		error("Feature <$feature> is already provided");
	$GLOBALS['features'][$feature]=true;
	}
}

//-----------------------

function is_provided($feature)
{
return isset($GLOBALS['features'][trim($feature)]);
}

//-----------------------

function ppc_requires($buf,&$options,&$output)
{
if (($buf=rawify($buf))=='') return;

foreach(explode(",",$buf) as $feature)
	{
	if (!is_provided($feature))
		{
		echo '* Provided features : '
			.implode(',',array_keys($GLOBALS['features']))."\n";
		error("Feature <$feature> is required by "
			.$GLOBALS['source_info']['source']." but not present");
		}
	}
}

//-----------------------

function ppc_source($buf,&$options,&$output)
{
if (!isset($options['out'])) $options['out']=OUT_DEFAULT; // Force output

$output=NL;
foreach(explode(",",rawify($buf)) as $f)
	{
	$output.=NL
		.'_old_dir=`pwd`'.NL
		.'cd $SRC_DIR/build'.NL
		.'echo "* Extracting '.$f.'..."'.NL
		.'gunzip <$ACP_DEPOT/src/'.$f.'.tar.gz | $TAR xf -'.NL
		.'cd $_old_dir'.NL;
	}
}

//-----------------------
// Permet de mettre les directives 'source' et 'dir' dans n'importe quel ordre

function ppc_dir($buf,&$options,&$output)
{
if (!isset($options['out'])) $options['out']=OUT_DEFAULT; // Force output

$output=NL
	.'_d=$SRC_DIR/build/'.rawify($buf).NL
	.'[ -d $_d ] || mkdir -p $_d'.NL
	.'cd $_d'.NL
	.'date'.NL;
}

//-----------------------

function ppc_notoc()
{
$GLOBALS['notoc']=true;
}

//-----------------------

function ppc_getenv($buf,&$options,&$output)
{
echo getenv(trim($buf));
}

//-------------------------------------------------------------------

function find_in_buf($buf,$start,$pattern,&$pos,&$result)
{
if ($start >= strlen($buf)) return false;

$buf2=substr($buf,$start);
if (! eregi($pattern,$buf2,$regs)) return false;
$result=$regs[0];
$pos=$start + strpos($buf2,$result);
return true;
}

//-------------------------------------------------------------------

function finalize_static(&$static_buf)
{
$static_buf=str_replace('[:note]','<u>Note:</u> ',$static_buf);
}

//-------------------------------------------------------------------

function string_to_tocstring($string)
{
return ereg_replace("\r",'',ereg_replace("\n",' ',(quote_car(quote_car(strip_tags($string),"\""),"\$"))));
}
//-------------------------------------------------------------------

function quote_car($string,$car)
{
$p=explode($car,$string);
return implode("\\" . $car,$p);
}

//-------------------------------------------------------------------

function update_toc($source,&$buf,&$tag,&$global_toc)
{
$start_tag="";

// Corps du texte

if (find_in_buf($buf,0,"<body>",$cur_pos,$res))
	{
	if (DEBUG) {echo "<p>Found <body> at position : $cur_pos\n"; flush();}
	$cur_pos += 6;
	}
else
	{
	if (find_in_buf($buf,0,"</head>",$cur_pos,$res))
		{
		if (DEBUG) {echo "<p>Found </head> at position : $cur_pos\n"; flush();}
		$cur_pos += 7;
		}
	else $cur_pos=0;
	}

$local_toc='';

// Scan chapters, insert numbers, and compute TOC

while (true)
	{
	if (DEBUG) {echo "<p>cur_pos=$cur_pos\n"; flush();}
	$start_pos=0;
	if (! find_in_buf($buf,$cur_pos,'<h[0-9]',$start_pos,$res)) break;
	if (DEBUG) {echo "<p>Found tag " . substr($res,1) . " - pos=$start_pos\n"; flush();}
	$level=$res{2};
	if (! find_in_buf($buf,$start_pos,'</h' . $level . '>',$end_pos,$res))
		{
		echo "ERROR : Impossible de trouver la fin du chapitre (niveau $level)\n";
		echo "Fichier source : $source\n";
		echo "Ligne : " . count(split(substr($buf,0,$start_pos),'\n'))+1 . "\n\n";
		$update_error_flag=true;
		return;
		}
	$string=substr($buf,$start_pos,$end_pos - $start_pos + 5);
	if (DEBUG) {echo "<p>string = " . strip_tags($string); flush();}
	$tag[$level - 1]++;
	if ($level < 9) for ($i=$level;$i<9;$i++) $tag[$i]=0;
	$tag_string="";
	for ($i=0;$i<$level;$i++) $tag_string .= "." . $tag[$i];
	$tag_string=substr($tag_string,1);
	if ($start_tag == "") $start_tag=$tag_string;
	find_in_buf($buf,$start_pos,'>',$end_start_pos,$res);
	if (DEBUG) {echo "<p>end_start_pos=$end_start_pos\n"; flush();}
	$rstring="$tag_string<a name=\"p$tag_string\">&nbsp;</a>- ";
	$buf=substr_replace($buf,$rstring,$end_start_pos+1,0);
	$toc1 = "<tr><td width=100% align=left>";
	if (($level-1) != 0) for ($i=0;$i<$level-1;$i++) $toc1 .='&nbsp;&nbsp;&nbsp;&nbsp; ';
	$toc1 .="$tag_string. <a href=\"";
	$toc2="#p$tag_string\">";	// Tag must start with a letter (ISO compliance)
	if ($level==1) $toc2 .= '<b>';
	$toc2 .=string_to_tocstring($string);
	if ($level==1) $toc2 .= '</b>';
	$toc2 .="</a></td></tr>\n";
	$local_toc .= $toc1.$toc2;
	if (isset($GLOBALS['url'])) $global_toc .= $toc1.$GLOBALS['url'].$toc2;
	else $global_toc .= "<b>$tag_string - NO URL !! </b>$toc2";
	if (DEBUG) {echo "<p>end_pos=$end_pos\n"; flush();}

	$cur_pos=$end_pos+5+strlen($rstring);
	}

toc_replace($buf,$local_toc);	// Insertion TOC
}

//-------------------------------------------------------------------

function toc_replace(&$buf,$toc_string)
{
$buf=str_replace('{toc}',"<table cellpadding=2 cellspacing=0 border=0>"
	.$toc_string."</table>\n",$buf);
}

//-------------------------------------------------------------------
// Adapted from mossmilies - FLP - FEB 2007

function ConvertSmiley ($seppre, $idx, $seppost ,$botsm, $txt, $RemSep=false)
{
$repl='<img src="'.BASE_URL.'mambots/content/mossmilies/'.$botsm
	.'" border=0 title="'.$idx.'" alt="'.$idx.'" align=absmiddle>';

if ($RemSep)
	{    // Also remove the separators...
	$txt = str_replace ($seppre.$idx.$seppost,$repl, $txt);
	}
else
	{          // Keep separators...
	$txt = str_replace ($seppre.$idx.$seppost, $seppre.$repl.$seppost, $txt);
	}

return $txt;
}

//-----

function botMosSmilies($buf)
{

$botsmiley=array();

  // define some variables for the Bot
  $botsmiley['#-)']     = "sm_crazy.gif";
  $botsmiley['%-(']     = "sm_sick.gif";
  $botsmiley[':-#']     = "sm_confused.gif";
  $botsmiley[':-$']     = "sm_sigh.gif";
  $botsmiley[':-(']     = "sm_mad.gif";
  $botsmiley[':-)']     = "sm_smile.gif";
  $botsmiley[':-?']     = "sm_confused.gif";
  $botsmiley[':-[']     = "sm_angry.gif";
  $botsmiley[':-]']     = "sm_biggrin.gif";
  $botsmiley[':-}']     = "sm_biggrin.gif";
  $botsmiley[':-{']     = "sm_upset.gif";
  $botsmiley[':-K']     = "sm_capo.gif";
  $botsmiley[':-o']     = "sm_bigeek.gif";
  $botsmiley[':-O']     = "sm_shocked.gif";
  $botsmiley[':-p']     = "sm_razz.gif";
  $botsmiley[':-P']     = "sm_tongue.gif";
  $botsmiley[':-x']     = "sm_dead.gif";
  $botsmiley[':(']      = "sm_mad.gif";
  $botsmiley[':)']      = "sm_smile.gif";
  $botsmiley[':?']      = "sm_confused.gif";
  $botsmiley[':alien1'] = "sm_alien.gif";
  $botsmiley[':alien2'] = "sm_alien2.gif";
  $botsmiley[':ask']    = "sm_question.gif";
  $botsmiley[':bozo']   = "sm_clown.gif";
  $botsmiley[':cake']   = "sm_cake.gif";
  $botsmiley[':call']   = "sm_capo.gif";
  $botsmiley[':crazy']  = "sm_crazy.gif";
  $botsmiley[':cry']    = "sm_cry.gif";
  $botsmiley[':eek']    = "sm_bigeek.gif";
  $botsmiley[':girl']   = "sm_girl.gif";
  $botsmiley[':grin']   = "sm_biggrin.gif";
  $botsmiley[':grr']    = "sm_boid.gif";
  $botsmiley[':heart']  = "sm_heart.gif";
  $botsmiley[':#heart'] = "sm_heartbroken.gif";
  $botsmiley[':idea']   = "sm_idea.gif";
  $botsmiley[':love']   = "sm_love.gif";
  $botsmiley[':nuts']   = "sm_nuts.gif";
  $botsmiley[':o)']     = "sm_clown.gif";
  $botsmiley[':p']      = "sm_razz.gif";
  $botsmiley[':P']      = "sm_tongue.gif";
  $botsmiley[':roll']   = "sm_rolleyes.gif";
  $botsmiley[':sigh']   = "sm_sigh.gif";
  $botsmiley[':sleep']  = "sm_sleep.gif";
  $botsmiley[':thumbdown'] = "sm_thumbdown.gif";
  $botsmiley[':thumbup']   = "sm_thumbup.gif";
  $botsmiley[':upset']  = "sm_upset.gif";
  $botsmiley[':x']      = "sm_dead.gif";
  $botsmiley[':yield']  = "sm_capo.gif";
  $botsmiley[':zzz']    = "sm_sleep.gif";
  $botsmiley[';-)']     = "sm_wink.gif";
  $botsmiley[';)']      = "sm_wink.gif";
  $botsmiley['38-o']    = "sm_afraid.gif";
  $botsmiley['38-O']    = "sm_afraid.gif";
  $botsmiley['8-)']     = "sm_cool.gif";
  $botsmiley['8-}']     = "sm_shades_smile.gif";
  $botsmiley['8)']      = "sm_cool.gif";
//  $botsmiley[':note']   = "sm_note.gif";   // FLP - 26 JUN 2006
  $botsmiley[':note']   = "tooltip.png";   // FLP - 12 FEB 2007
  $botsmiley[':warn']   = "caution.png";   // FLP - SEP 2007
  $botsmiley[':stop']   = "warning2.png";   // FLP - SEP 2007
  $botsmiley[':death']   = "skull1.gif";   // FLP - SEP 2007
  $botsmiley[':new']   = "new.gif";   // FLP - SEP 2007
  $botsmiley[':work']   = "3263.gif";   // FLP - SEP 2007
  $botsmiley[':check']   = "check.png";   // FLP - SEP 2007


  // Replace the text smilies by associated pictures (from above variables...)
  foreach ($botsmiley as $idx=>$botsm) {

    $buf = ConvertSmiley (" ", $idx, " ",  $botsm, $buf);
    $buf = ConvertSmiley (" ", $idx, "\r", $botsm, $buf);
    $buf = ConvertSmiley (" ", $idx, "\n", $botsm, $buf);
    $buf = ConvertSmiley (" ", $idx, ".",  $botsm, $buf);
    $buf = ConvertSmiley (" ", $idx, ",",  $botsm, $buf);
    $buf = ConvertSmiley (" ", $idx, "<",  $botsm, $buf);

    $buf = ConvertSmiley (">", $idx, " ",  $botsm, $buf);
    $buf = ConvertSmiley (">", $idx, "\t", $botsm, $buf);
    $buf = ConvertSmiley (">", $idx, "\r", $botsm, $buf);
    $buf = ConvertSmiley (">", $idx, "\n", $botsm, $buf);
    $buf = ConvertSmiley (">", $idx, ".",  $botsm, $buf);
    $buf = ConvertSmiley (">", $idx, ",",  $botsm, $buf);
    $buf = ConvertSmiley (">", $idx, "<",  $botsm, $buf);

    $buf = ConvertSmiley ("[", $idx, "]",  $botsm, $buf,true);

  }

return $buf;
}

//-------------------------------------------------------------------
?>
