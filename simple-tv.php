<?php

$version = "1.5.0";
$save_path = "D:\\Temp";

error_reporting(0);
strncasecmp(php_uname('s'), "Win", 3) == 0 ? $windows = true : $windows = false;

// Paths for linux or windows
if ($windows) {
	if (file_exists("C:\\Program Files (x86)\\VideoLAN\\VLC\\vlc.exe")) {
        $vlc = "C:\\Program Files (x86)\\VideoLAN\\VLC\\vlc.exe";
	} else {
        $vlc = "C:\\Program Files\\VideoLAN\\VLC\\vlc.exe";
	}
	$livestreamer = "\"C:\\Program Files (x86)\\Livestreamer\\livestreamer.exe\" --player \"'".$vlc."' --file-caching=10000\"";
}
else
{
	$livestreamer  = "livestreamer --player \"vlc --file-caching=10000\"";
}

$canali = array();
$chn = array();
$xforwarded = array();
$plugin = array();

// Update tv-sources.xml from web site (URLs can change often...)
file_put_contents("tv-sources.xml", file_get_contents("http://nemolis.com/tv/tv-sources.xml"));
$xml = new DomDocument();
$xml->load('tv-sources.xml');
echo "Le fichier XML des sources TV a ete mis a jour.\r\n";

$sourcesList = $xml->getElementsByTagName('source');
$i=0;
foreach($sourcesList as $source)
{
    $chn[$i] = $source->getAttribute("label");
    $canali[$i] = $source->getElementsByTagName('url')->item(0)->firstChild->nodeValue;
    $xforwarded[$i] = $source->getElementsByTagName('xforwarded')->item(0)->firstChild->nodeValue;
    $plugin[$i]= $source->getElementsByTagName('plugin')->item(0)->firstChild->nodeValue;
    $i++;
}

$WshShell = new COM("WScript.Shell");

echo "Television\r\n";
while (true){
	echo "Liste des chaines:\r\n";
	for ($i=0; $i<count($chn); $i++){
		echo "$i) " . $chn [$i] . ( (( $i + 1 )% 4 == 0 ) ? "\r\n" : str_repeat ( ' ' , 17- strlen ($chn [$i]. $i )) ); // thanks to omepiet
	}
	echo "Choisir une chaine (x=quitter): ";
	$inp = trim(fgets(STDIN));
	if ($inp >= count($chn) || !preg_match("/[0-9xX]/", $inp)){
		echo "\r\nChoix invalide!\r\n";
		sleep(3);
		continue;
	} elseif ($inp == "x" || $inp == "X"){
		die("\r\nSalut !\r\n");
	}

	$url = $canali[$inp];
	$title = $chn[$inp];
	$ip = "85.".rand(0,7).".".rand(0,255).".".rand(0,255);

	echo "(L)ire or (E)nregistrer la video ?: ";
	$opt = trim(fgets(STDIN));
	if ($opt == "l" || $opt == "L") {
	   echo "Lecture en cours de ".$title."...\r\n";
		$cmd = $livestreamer;
		if ($xforwarded[$inp] == "true") {
			$cmd = $cmd.' --http-header "X-Forwarded-For='.$ip.'"';
		}
		$cmd = $cmd.' "'.$plugin[$inp].$url.'" best';
		$oExec = $WshShell->Run($cmd, 7, false);
	} 
	elseif ($opt == "e" || $opt == "E") {
		$cmd = $livestreamer;
		if ($xforwarded[$inp] == "true") {
			$cmd = $cmd.' --http-header "X-Forwarded-For='.$ip.'"';
		}
		$cmd = $cmd.' "'.$plugin[$inp].$url.'" best -o "'.$save_path.'/'.$title.'-'.date('m-d-Y_hisa').'.ts"';
		
		echo "Enregistrement (I)mmediat ou (D)iffere ?: ";
		$optEnr = trim(fgets(STDIN));
		if ($optEnr == "i" || $optEnr == "I") {
			echo $cmd;
			echo "\r\nEnregistrement en cours de ".$title."... (Appuyer sur ENTREE pour stopper)\r\n\r\n";
			if ($windows){
				pclose(popen('start /B "" '. $cmd, "w"));
				$stop = fgets(STDIN);
				$oExec = $WshShell->Run('Taskkill /IM livestreamer.exe /F', 7, false);
			} else {
				shell_exec($cmd." > /dev/null 2>/dev/null &");
				$stop = fgets(STDIN);
				shell_exec('pkill -9 -f livestreamer');
			}
		}
		elseif ($optEnr == "d" || $optEnr == "D") {
			echo "A quelle heure demarrer l'enregistrement (hh:mm) ?: ";
			$optTimeStringStart = trim(fgets(STDIN));
			echo "A quelle heure stopper l'enregistrement (hh:mm) ?: ";
			$optTimeStringEnd = trim(fgets(STDIN));
			
			$optTimeNow = explode(":", date(''));
			$optTimeStringStartTab = explode(":", $optTimeStringStart);
			$optTimeStringEndTab = explode(":", $optTimeStringEnd);
			
			$countMinutesStart = intval($optTimeStringStartTab[0])*60 + intval($optTimeStringStartTab[1]);
			$countMinutesEnd = intval($optTimeStringEndTab[0])*60 + intval($optTimeStringEndTab[1]);
			$delayRecording = $countMinutesEnd - $countMinutesStart;
			$delayBeforeStart = $countMinutesStart - ( intval(date("H"))*60 + intval(date("i")));
			
			echo "Enregistrement planifie; en attente de demarrage...";
			sleep(60 * $delayBeforeStart);
			
			echo $cmd;
			echo "\r\nEnregistrement en cours de ".$title."... (Appuyer sur ENTREE pour stopper)\r\n\r\n";
			if ($windows){
				pclose(popen('start /B "" '. $cmd, "w"));
				sleep(60 * $delayRecording);
				$oExec = $WshShell->Run('Taskkill /IM livestreamer.exe /F', 7, false);
			} else {
				shell_exec($cmd." > /dev/null 2>/dev/null &");
				sleep(60 * $delayRecording);
				shell_exec('pkill -9 -f livestreamer');
			}
		}
	} 
	else continue;
}
?>
