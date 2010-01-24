<?php

define('BASE_URL','http://www.tekwire.net/joomla/');

//-----
// Read up to 8192 bytes

function read_chunk()
{
$buf=fread(STDIN,8192);
if ($buf===false) throw new Exception('Cannot read');
return $buf;
}

//-----
// Get data from STDIN

$data='';

while (($chunk=read_chunk())!=='')	$data .= $chunk;

echo botMosSmilies($data);

//-----
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
