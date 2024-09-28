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
    <input type "text" name="yturl" size=50>
    <input type="submit" value="Генерировать" />
</form>
ENDE;

if ($_POST)
{
    $url = $_POST["yturl"];
    $id = parseurl($url);
    $wikitext .= "<table border=0><tr><td valign='top'>\n".embedcode($id)."</td><td valign='top'>__TOC__</td></tr></table>\n";
    $wikitext .= parsemeta( getmeta($id) );
    $wikitext .= parsesubtitles( getsubtitles($id) ) . "\n<h3>Ссылки на эту страницу</h3>\n{{Special:WhatLinksHere/{{FULLPAGENAME}}}}\n";
    $wikilink  = $id ? '<a href="/index.php?title=Субтитры:' . $id . '&action=edit">': '<s><b>';
    $wikilink .= 'Ссылку на создание новой вики-страницы';
    $wikilink .= $id ? '</a>' : '</b></s>';
	if (preg_match("/transcripts disabled for that video/sim", $wikitext) > 0 )
	    {
	    echo "<h3>К сожалению, данное видео не содержит встроенных субтитров. Попробуйте другое видео.</h3><br>";
	    if (! isset($_GET["form"]) ) exit(1);//force show textarea contents without correct subtitles - add to URL ?form

///	    exit(1);
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


function parsemeta($html)
    {
    global $url, $id;
    $out='[[File:Youtube.png|24px|left|link=]] ';

    preg_match("/<meta name\=\"title\" content\=\"(.*?)\"/", $html, $title);
    $title[1] = preg_replace('/\]/', "&amp;#93;", $title[1]);##fix if title has brackets and they render by engine
    $out .= "<h3>[" . $url . $id . ' ' . $title[1] . "]</h3>\n";

    preg_match("/<meta itemprop=\"datePublished\" content=\"(.*?)\">/", $html, $date);
    $out .= "\n" . format_date($date[1]) . "&nbsp;";

    preg_match("/Person\"><link itemprop=\"url\" href=\"(.*?)\"><link itemprop=\"name\" content=\"(.*?)\">/", $html, $channel);
    $out .= "[" . $channel[1] . " " . $channel[2] . "]\n\n";

    preg_match("/\"lengthSeconds\"\:\"(\d*?)\"/", $html, $dur);
    $out .= "Длительность: " . sectostr($dur[1], true) ." (" . $dur[1] . " сек.)\n";

    preg_match("/\"shortDescription\"\:\"(.*?)\"/", $html, $desc);
    $desc = preg_replace("/\\\\n/m", "<br>\n:", $desc[1]);
    $out .= "<div {{DivWrapOpts}}>[[Категория:WrapBlock]]\nОписание:\n<div class=\"mw-collapsible-content\">\n:".$desc."\n</div></div>\n<br>\n";

    return $out;
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


function getsubtitles($id)
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
