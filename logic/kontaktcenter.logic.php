<?php
// Max Anzahl bevor alle im BCC angeschrieben werden
$grenze_bcc = 12; //TODO in Config oder Env

// Für die Turnierauswahl
$turniere = Turnier::get_turniere('alle', false, false);

// Für Sortierung der Teams nach Blöcken
$akt_spieltag = Tabelle::get_aktuellen_spieltag();
$teams = Tabelle::get_rang_tabelle($akt_spieltag); // Sortierung nach Rangtabelle

// Damit sich $_SESSION von team- und ligacenter nicht vermischen
if (Config::$ligacenter) {
    $list_id = 'lc_emails' . $_SESSION['logins']['la']['id'];
} elseif (Config::$teamcenter) {
    $list_id = 'tc_emails' . $_SESSION['logins']['team']['id'];
}


// Formularauswertung Emailauswahl

// Emails zurücksetzen
if (isset($_POST['reset'])) {
    unset ($_SESSION[$list_id]);
}

//Turnier wurde ausgewählt
if (isset($_POST['turnier_id']) && is_numeric($_POST['turnier_id'])) {
    unset ($_SESSION[$list_id]);
    $turnier = new Turnier((int) $_POST['turnier_id']);
    if (empty($turnier->details)) {
        Form::error("Turnier wurde nicht gefunden");
        header('Location: ' . dbi::escape($_SERVER['PHP_SELF']));
        die();
    }
    $array = Kontakt::get_emails_turnier($_POST['turnier_id']);
    $_SESSION[$list_id]['type'] = 'Turnier in ' . $turnier->details['ort'] . ' (' . date("d.m.Y", strtotime($turnier->details['datum'])) . ', ' . $turnier->details['tblock'] . ')';
    $_SESSION[$list_id]['emails'] = $array['emails'];
    $_SESSION[$list_id]['empfaenger'] = $array['teamnamen'];

    if (isset($_POST['la'])) {
        array_unshift($_SESSION[$list_id]['emails'], Env::LAMAIL);
        array_unshift($_SESSION[$list_id]['empfaenger'], 'Ligaausschuss');
    }

    Form::notice("Achtung: Nichtligateams müssen seperat angeschrieben werden!");
}


// Rundmail wurde ausgewählt
if (isset($_POST['rundmail'])) {
    unset ($_SESSION[$list_id]);
    $_SESSION[$list_id]['type'] = 'Rundmail';
    $_SESSION[$list_id]['empfaenger'] = Team::get_liste();
    $_SESSION[$list_id]['emails'] = Kontakt::get_emails_rundmail();

    array_unshift($_SESSION[$list_id]['emails'], Env::LAMAIL);
    array_unshift($_SESSION[$list_id]['empfaenger'], '<b>Ligaausschuss</b>');
}

// Teams wurden ausgewählt
if (isset($_POST['teams_emails'])) {
    unset ($_SESSION[$list_id]);
    $_SESSION[$list_id]['type'] = 'Teamauswahl';

    $emails = $teamnamen = [];
    foreach (($_POST['team'] ?? []) as $team_id) {
        $akt_team = new Kontakt($team_id);
        $team_emails = $akt_team->get_emails();
        foreach ($team_emails as $email) {
            // Doppelte Email-Einträge vermeiden
            if (!in_array($email, $emails)) {
                $emails[] = $email;
            }
        }
        $teamnamen[] = Team::id_to_name($team_id);
    }
    $_SESSION[$list_id]['emails'] = $emails;
    $_SESSION[$list_id]['empfaenger'] = $teamnamen;
    if (isset($_POST['la'])) {
        array_unshift($_SESSION[$list_id]['emails'], Env::LAMAIL);
        array_unshift($_SESSION[$list_id]['empfaenger'], '<b>Ligaausschuss</b>');
    }

}

//Mailversand
if (isset($_POST['send_mail'], $_SESSION[$list_id])) {
    $error = false;
    $emails = $_SESSION[$list_id]['emails'];
    $betreff = $_POST['betreff'];
    $text = $_POST['text'];

    if (empty($emails) || empty($betreff) || empty($text)) {
        Form::error("Kontaktformular unvollständig");
        $error = true;
    }

    if (!$error) {
        $mailer = MailBot::start_mailer();
        if (Config::$ligacenter) {
            $mailer->setFrom(Env::LAMAIL, 'Ligaausschuss');
            $mailer->addBCC(Env::LAMAIL_ANTWORT);
        } elseif (Config::$teamcenter) {
            $akt_kontakt = new Kontakt($_SESSION['logins']['team']['id']); //Absender Mails bekommen
            $absender = $akt_kontakt->get_emails();
            foreach ($absender as $email) {
                $mailer->AddReplyTo($email, $_SESSION['logins']['team']['name']); //Antwort an den Absender
                if (!in_array($email, $emails)) {
                    $mailer->addBCC($email); //Als Kontroll-Email an den Absender
                }
                $mailer->setFrom("noreply@einrad.hockey", $_SESSION['logins']['team']['name']);
            }
        }

        //BCC oder Adressat?
        if (count($emails) > $grenze_bcc) {
            foreach ($emails as $email) {
                $mailer->addBCC($email);
            }
        } else {
            foreach ($emails as $email) {
                $mailer->addAddress($email);
            }
        }

        // Text und Betreff hinzufügen
        $mailer->Subject = $betreff;
        $mailer->Body = $text . "\r\nVersendet aus dem Kontaktcenter von einrad.hockey";


        Handler::log(Config::LOG_EMAILS, "Betreff: $betreff" . " an " . implode(',', $emails));
        // Email-versenden
        if (MailBot::send_mail($mailer)) {
            Form::info("Die E-Mail wurde versandt.");
            unset($_SESSION[$list_id]);
            header('Location: ' . dbi::escape($_SERVER['PHP_SELF']));
            die();
        }

        Form::error("Es ist ein Fehler aufgetreten. Mail konnte nicht versendet werden.");
    }
}

// Zum Ausfüllen des Absendeforumulars
if (isset($_SESSION[$list_id])) {
    $anzahl_emails = count($_SESSION[$list_id]['emails']);
    $adressaten = $bcc = [];
    if (Config::$ligacenter) {
        $from = Env::LAMAIL;
        $tos = $_SESSION[$list_id]['emails'];
    } elseif (Config::$teamcenter) {
        $from = $_SESSION['logins']['team']['name'];
        $tos = $_SESSION[$list_id]['empfaenger'];
    }
    if (empty($_SESSION[$list_id]['emails'])) {
        Form::error("Es wurden keine E-Mail-Adressen gefunden.");
    }
}

if (Config::$ligacenter){
    $las = Ligaleitung::get_all('ligaausschuss');
    $signatur = "\r\n\r\n\r\nDein Ligaausschuss\r\n--\r\n";
    foreach ($las as $la){
        $signatur .= $la['vorname'] . ' ' . $la['nachname']
            . (!empty($la['teamname']) ? ' (' . $la['teamname'] . ')' : '') . "\r\n";
    }
}