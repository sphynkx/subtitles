This is helper for mediawiki-based project. Script gets subtitles from Youtube videos and reformat them in wiki markup. Also script has "enhanced mode"
(with param `?form` in URL) to operate videos without subtitles, option to enable automatic translation subtitles to Russian.


## Setup

Supposed that scpipt places in subdir of mediawiki dir. 

1. Create subdir:
```
  mkdir /var/www/wiki/subtitles
```

2. Place `subtitles.php` in it.


3. Install [google/cloud-translate](https://github.com/googleapis/google-cloud-php) package:
```
  composer require google/cloud-translate
```

4. Go to GoogleCloud and do some magic for creation of autorization creds =)) See [recomendations](https://github.com/googleapis/google-cloud-php/blob/main/AUTHENTICATION.md).
Finally you need the .json-file with creds. This file place somewhere, for example at `/usr/local/etc`. Check is everything is OK by test script:
```
<?php
require 'vendor/autoload.php';
use Google\Cloud\Translate\V2\TranslateClient;

$translate = new TranslateClient(['projectId' => 'subtitle-414118']);

$result = $translate->translate('Hello World', [
    'target' => 'ru'
]);

echo $result['text'] . "\n";
```
Modify `projectId` with your correct project name that you created in GoogleCloud..


5. Configure Nginx for directory subtitles:
```
    location ~ /subtitles/ {
	include /etc/nginx/fastcgi_params;
	fastcgi_pass unix:/run/php-fpm/www.sock;
	fastcgi_index subtitles.php;
	fastcgi_param SCRIPT_FILENAME	$document_root/subtitles/subtitles.php;
	fastcgi_param GOOGLE_APPLICATION_CREDENTIALS /usr/local/etc/key.json;
    }
```
Here we also set variable `GOOGLE_APPLICATION_CREDENTIALS` pointing to .json-file with creds, created above. This variable will be available in the env of user, whos run Nginx.

