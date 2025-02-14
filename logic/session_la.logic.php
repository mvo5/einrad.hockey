<?php
// Dies hier muss in jeder geschützten Seite direkt nach init.php eingefügt werden!
if(!isset($_SESSION['logins']['la'])) {
  $_SESSION['lc_redirect'] = db::escape($_SERVER['REQUEST_URI']); //Damit man nach dem Login direkt auf die gewünschte Seite geführt wird
  Html::info("Du wirst nach deinem Login weitergeleitet.");
  header('Location: ../ligacenter/lc_login.php?redirect');
  die();
}
MailBot::warning(); // Sendet eine Warnung, wenn Mails nicht versendet werden konnten.

HTML::$titel = 'Ligacenter';

Helper::$ligacenter = true; // Dies zeigt allen Dateien (insbeondere .tmp.php) , das man sich im Ligacenter befindet.