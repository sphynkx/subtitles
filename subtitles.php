<?php
require 'vendor/autoload.php';
use Google\Cloud\Translate\V2\TranslateClient;

$url = "https://www.youtube.com/watch?v=";
$wikitext = '';

if (isset($_GET['form']) ) $translation='   Translate to Russian:  <input type="checkbox" id="" name="translation" /><br>';

echo <<<ENDE
<title>Генератор викитекста из субтитров</title><link rel="icon" href="/lahwiki_pyr_fav32_01.png"/>
<h2>Генератор викитекста из субтитров Вики<a href='./?form' style='text-decoration: none'>.</a></h2>
<a href=".">Данный сервис</a> позволяет извлечь речь из видеоматериалов на <a href='https://www.youtube.com/'>Youtube</a> и разместить 
ее в текстовом виде в данной <a href='/'>Вики</a>. Его задача объединить информацию из видео в единое информационное пространство 
с остальным содержимым <a href='/'>Вики</a>, сделать ее доступной для средств поиска, упорядочивания и пр. инструментов.<br><br>

1. Вставьте адрес Youtube-видеоролика (в любом виде: обычном - <b>https://www.youtube.com/watch?v=bb4rAz7fz3I</b>, кратком - <b>https://youtu.be/bb4rAz7fz3I</b>, или просто как ID видеоролика - <b>bb4rAz7fz3I</b>) и нажмите на кнопку <b>"Генерировать"</b>:<br>
<form method="post">
$translation
    <input type="text" name="yturl" size=50>
    <input type="submit" value="Генерировать" />
</form>
ENDE;

if ($_POST)
{
    $url = $_POST["yturl"];
    $id = parseurl($url);
    $wikitext_before .= "<table border=0><tr><td valign='top'>\n".embedcode($id)."</td><td valign='top'>__TOC__</td></tr></table>\n";
    $wikitext .= parsemeta( getmeta($id), $wikitext_before );
    $wikitext .= parsesubtitles( getsubtitles($id) ) . "\n<h3>Ссылки на эту страницу</h3>\n{{Special:WhatLinksHere/{{FULLPAGENAME}}}}\n";
    $wikilink  = $id ? '<a href="/index.php?title=Субтитры:' . $id . '&action=edit">': '<s><b>';
    $wikilink .= 'Ссылку на создание новой вики-страницы';
    $wikilink .= $id ? '</a>' : '</b></s>';
	if (preg_match("/transcripts disabled for that video/sim", $wikitext) > 0 )
	    {
	    echo "<h3>К сожалению, данное видео не содержит встроенных субтитров. Попробуйте другое видео.</h3><br>";
	    if (! isset($_GET["form"]) ) exit(1);//force show textarea contents without correct subtitles - add to URL ?form
	    }
    if($id !== '') 
	{
	echo "2. Нажмите на кнопку <b>\"Выделить и скопировать вики-текст\"</b><br><textarea  rows=20 cols=100>" . $wikitext . "</textarea><br>";
	}else{
	    echo "<h3>Сначала введите корректный адрес Youtube-видеоролика</h3>(<a href=\".?form\">force mode</a>)";
	    exit(1);
	    }
    echo "<button>Выделить и скопировать вики-текст</button><br>";
    echo '<br>3. Откройте желательно в новом окне <b>' . $wikilink . '</b>. Затем скопированный ранее текст вставьте там и нажмите кнопку <b>"Записать страницу"</b><br>';
    echo '<br>4. Созданную страницу далее можно при необходимости править, применять <a href="https://www.mediawiki.org/wiki/Help:Formatting/ru">вики-форматирование</a> итд.';
}


function parseurl($url)
    {
    if (preg_match("/.*?youtu\.be\/(.*)/", $url, $u) == 1) return preg_replace("/\?.*/", "", $u[1]);
	$url = preg_replace("/.*?v=/", "", $url);
	$url = preg_replace("/\&.*?/",  "", $url);
	return $url;

    }


function getmeta($id)
    {
    return file_get_contents("https://www.youtube.com/watch?v=" . $id);
    }


function format_date($date)
    {
    $months = array('NON', 'Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек');
    $res = date_parse($date);
    return "<b>" . $res['day'] . " " . $months[ $res['month'] ] . " " . $res['year'] . "</b>\n";
    }


function embedcode($id)
    {
    return "<html><iframe src=\"https://www.youtube.com/embed/$id\" width=\"300\" height=\"250\" frameborder=\"1\" allowfullscreen=\"true\" style=\"white-space: nowrap; display: -webkit-flex; -webkit-flex-direction: row; display: flex; flex-direction: row;\" align=\"baseline\" seamless></iframe></html>\n";
    }


function parsemeta($html, $wikitext_before)
    {
    global $url, $id;
    $out='[[File:Youtube.png|24px|left|link=]] ';
    $tpl_pict = 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg';

    preg_match("/<meta name\=\"title\" content\=\"(.*?)\"/", $html, $title);
    $title[1] = preg_replace('/\]/', "&amp;#93;", $title[1]);##fix if title has brackets and they render by engine
    $out .= "<h3>[" . $url . $id . ' ' . $title[1] . "]</h3>\n";
    $tpl_title = $title[1];

    preg_match("/<meta itemprop=\"datePublished\" content=\"(.*?)\">/", $html, $date);
    $out .= "\n" . format_date($date[1]) . "&nbsp;";
    $tpl_date = format_date($date[1]);
    $tpl_date = preg_replace('|<b>(.*?)</b>|', '$1', trim($tpl_date));

    preg_match("/Person\"><link itemprop=\"url\" href=\"(.*?)\"><link itemprop=\"name\" content=\"(.*?)\">/", $html, $channel);
    $channel[1] = preg_replace('|http:|', 'https:', $channel[1]); ## bug from Google =))
    $out .= "[" . $channel[1] . " " . $channel[2] . "]\n\n";
    $tpl_channel_url = $channel[1];
    $tpl_channel_name = $channel[2];

    preg_match("/\"lengthSeconds\"\:\"(\d*?)\"/", $html, $dur);
    $out .= "Длительность: " . sectostr($dur[1], true) ." (" . $dur[1] . " сек.)\n";
    $tpl_time = sectostr($dur[1], true);

    preg_match("/\"shortDescription\"\:\"(.*?)\"/", $html, $desc);
    $desc = preg_replace("/\\\\n/m", "<br>\n:", $desc[1]);
    $out .= "<div {{DivWrapOpts}}>[[Категория:WrapBlock]]\nОписание:\n<div class=\"mw-collapsible-content\">\n:".$desc."\n</div></div>\n<br>\n";

    $tpl = <<< EOL
{{empty
| pict = $tpl_pict
| title = $tpl_title
| channel_name = $tpl_channel_name
| channel_url = $tpl_channel_url
| date = $tpl_date
| time = $tpl_time
}}

EOL;

    return $tpl . $wikitext_before . $out;
    }


function sectostr($matches, $meta = false)
    {
    global $url, $id;
    $out = '';
    $meta ? $secs = $matches : $secs = $matches[1];
    $hours = floor($secs / 3600);
    $secs = $secs % 3600;
    if($hours >0) $out .= $hours . ':';
    $minutes = floor($secs / 60);
    $secs = $secs % 60;
    ($hours == 0) ? $out .= $minutes . ':' : $out .= sprintf("%02d",$minutes) . ':';
    $out .= sprintf("%02d",$secs);
    return $meta ? $out : "<tr>\n  <td>[" . $url . $id . '&t=' . $matches[1] . 's ' . $out . "]</td>\n  <td>";
    }


// Got broken due to changes subtitles system on the YouTube side..
function getsubtitles_OLD($id)
    {
    $translate = new TranslateClient(['projectId' => 'subtitle-414118']);

    $regex='/.*\{"captionTracks":\[\{"baseUrl":"(.*?)".*/';
    $data = file_get_contents('https://www.youtube.com/watch?v=' . $id);
    preg_match($regex, $data, $matches);
    $file = file_get_contents(preg_replace('/\\\\u0026/', '&', $matches[1]));

    if (isset($_POST["translation"]))
	{
	$translated = $translate->translate($file, ['target' => 'ru']);
	return explode("</text>", $translated['text']);
	}
    else
	{
	return explode("</text>", $file); 
	}
    }


// New version based on `youtube-dl` utility, fully imitate old version (refarmat/adapt  vtt to google's TTML)
function getsubtitles($id)
{
    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
    $lang = 'ru';
    $tmp = "/tmp/{$id}";
    $videoUrl = escapeshellarg("https://www.youtube.com/watch?v={$id}");

    $cmd = "./bin/youtube-dl --cache-dir /tmp --user-agent " . escapeshellarg($userAgent) .
        " --skip-download --write-sub --sub-lang $lang $videoUrl -o $tmp 2>/dev/null";
    shell_exec($cmd);

    $subfile = "{$tmp}.{$lang}.vtt";
    if (!file_exists($subfile)) {
        return ["<transcript>transcripts disabled for that video</transcript>"];
    }

    $content = file_get_contents($subfile);
    @unlink($subfile);

    if (!$content) {
        return ["<transcript>transcripts disabled for that video</transcript>"];
    }

    // VTT -> <transcript><text start="...">...</text>...</transcript>
    $lines = explode("\n", $content);
    $subs_xml = [];
    $in_block = false;
    $start_time = "";
    $text = "";
    foreach ($lines as $line) {
        $line = trim($line);
        // Pass misc headers and system strings..
        if ($line === "" || $line === "WEBVTT" || strpos($line, "Kind:") === 0 || strpos($line, "Language:") === 0) {
            continue;
        }
        // VTT time-mark
        if (preg_match('/^(\d{2}:\d{2}:\d{2})(?:\.(\d{3}))? -->/', $line, $m)) {
            // If there has contained text from previous block, then finish it.
            if ($in_block && $text !== "") {
                $start_secs = vtt_time_to_seconds($start_time);
                $subs_xml[] = '<text start="' . $start_secs . '">' . htmlspecialchars($text, ENT_QUOTES | ENT_XML1) . '</text>';
                $text = "";
            }
            $start_time = $m[1] . (isset($m[2]) ? '.' . $m[2] : '.000');
            $in_block = true;
            continue;
        }
        // Any string(s) is the subtitles text.
        if ($in_block) {
            $text .= ($text ? " " : "") . $line;
        }
    }
    // Last block
    if ($in_block && $text !== "") {
        $start_secs = vtt_time_to_seconds($start_time);
        $subs_xml[] = '<text start="' . $start_secs . '">' . htmlspecialchars($text, ENT_QUOTES | ENT_XML1) . '</text>';
    }
    // Add <transcript>...</transcript> for compability with rest code
    $xml = "<transcript>\n" . implode("\n", $subs_xml) . "\n</transcript>";

    if (isset($_POST["translation"])) {
        $translate = new TranslateClient(['projectId' => 'subtitle-414118']);
        $translated = $translate->translate($xml, ['target' => 'ru']);
        $xml = $translated['text'];
    }

    return explode("</text>", $xml);
}

// Coverts VTT-time (like "00:01:23.456") to sec with msec
function vtt_time_to_seconds($t) {
    // t = "HH:MM:SS.mmm"
    if (!preg_match('/^(\d{2}):(\d{2}):(\d{2})\.(\d{3})$/', $t, $m)) return 0;
    return intval($m[1]) * 3600 + intval($m[2]) * 60 + intval($m[3]) + floatval('0.' . $m[4]);
}


function parsesubtitles($subtitles)
    {
    $parsed = array('<div class="toccolours mw-collapsible" style="width:8000px; display: table-cell; margin: 0.5em 0 0 2em">[[Категория:WrapBlock]][[Категория:Субтитры]]', "\nСубтитры:\n", '<div class="mw-collapsible-content">', "\n<table  width='100%'>\n");

    foreach ($subtitles as $s) 
	{
	$s = preg_replace("/^.*?\<transcript\>/", "", $s);
	$s = preg_replace_callback("/\<text start=\"(.*?)\".*?\>/", 'sectostr', $s);
	$s = preg_replace("/<\/transcript>/", "", $s);
	array_push($parsed, $s . "</td>\n</tr>\n");
	}

    $out = implode($parsed) . "</table>\n</div></div>\n";
    return $out;
    }

?>

<script>
document.querySelector("button").onclick = function(){
    document.querySelector("textarea").select();
    document.execCommand('copy');
}
</script>