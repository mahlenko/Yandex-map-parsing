<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['email_admin'] = 'mahlenko-weblive@yandex.ru';

$config['protocol'] = 'smtp';
$config['smtp_host'] = 'smtp.yandex.ru';
$config['smtp_user'] = 'it@tradecar.ru';
$config['smtp_pass'] = 'pyfewkqlvbhgepqy';
$config['smtp_port'] = '465';
$config['smtp_crypto'] = 'ssl';

$config['mailtype'] = 'html';

// присылать ли отчет на почту
// $config['dsn'] = true;