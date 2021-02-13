<?php

/**
 * Class MailBot
 */
class MailBot
{
    /**
     * Initiert den PHP-Mailer mit Grundlegende Einstellungen für den Mailversand
     *
     * @return \PHPMailer\PHPMailer\PHPMailer
     */
    public static function start_mailer(): \PHPMailer\PHPMailer\PHPMailer
    {
        $mailer = PHPMailer::load_phpmailer();
        $mailer->isSMTP();
        $mailer->Host = Config::SMTP_HOST;
        $mailer->SMTPAuth = true;
        $mailer->Username = Config::SMTP_USER;
        $mailer->Password = Config::SMTP_PW;
        $mailer->SMTPSecure = 'tls';
        $mailer->Port = Config::SMTP_PORT;
        $mailer->CharSet = 'UTF-8';
        return $mailer;
    }

    /**
     * Versendet eine Mail mit dem PHPMailer
     *
     * @param \PHPMailer\PHPMailer\PHPMailer $mailer PHPMailer Objekt
     * @return bool Wurde die Mail erfolgreich versendet?
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public static function send_mail(\PHPMailer\PHPMailer\PHPMailer $mailer): bool
    {
        if (Config::ACTIVATE_EMAIL) {
            if ($mailer->send()) {
                Form::log('emails.log', 'Mail erfolgreich versendet');
                return true;
            } else {
                Form::log('emails.log', 'Fehler:' . $mailer->ErrorInfo);
                return false;
            }
        } else { // Debugging
            if (!(Config::$ligacenter ?? false)) {
                $mailer->Password = '***********'; // Passwort verstecken
                $mailer->ClearAllRecipients();
                Form::log('emails.log', 'Mail erfolgreich versendet');
            }
            db::debug($mailer);
            return false;
        }
    }

    /**
     * Der Mailbot nimmt Emails aus der Datenbank und versendet diese
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public static function mail_bot()
    {
        $sql = "
                SELECT * 
                FROM mailbot 
                WHERE mail_status = 'warte'
                ORDER BY zeit 
                LIMIT 50
                ";
        $mails = dbi::$db->query($sql)->fetch();
        foreach($mails as $mail){
            $mailer = self::start_mailer();
            $mailer->isHTML(true); // Für die Links
            $mailer->setFrom($mail['absender'], 'Einradhockey-Mailbot'); // Absenderemail und -name setzen
            $mail_addresses = explode(',', $mail['adressat']); // Aus der Datenbank rausholen
            $anz_mail_addresses = count($mail_addresses);
            foreach ($mail_addresses as $mail_address) {
                if ($anz_mail_addresses > 15) {
                    $mailer->addBCC($mail_address);
                }
                $mailer->addAddress($mail_address); // Empfängeradresse
            }
            $mailer->Subject = $mail['betreff']; // Betreff der Email
            $mailer->Body = $mail['inhalt']; // Inhalt der Email

            // Email-versenden
            if (self::send_mail($mailer)) {
                self::set_status($mail['mail_id'], 'versendet');
            } else {
                self::set_status($mail['mail_id'], 'Fehler', $mailer->ErrorInfo);
                Form::error($mailer->ErrorInfo);
            }
        }
        Form::affirm('Mailbot wurde ausgeführt.');
    }

    /**
     * Fügt eine Email zur Datenbank hinzu
     *
     * Nur für automatische Emails verwenden. Die Mails werden erst bei der nächsten Ausführung des Mailbots versendet.
     *
     * @param string $betreff
     * @param string $inhalt
     * @param string|array $adressaten
     * @param string $absender
     */
    public static function add_mail(string $betreff, string $inhalt, string|array $adressaten, string $absender = Config::SMTP_USER)
    {
        if (!empty($adressaten)) return; //Nur wenn Mailadressen vorhanden sind, wird eine Mail hinzugefügt

        if (is_array($adressaten)) $adressaten = implode(",", $adressaten); // in String umwandeln
        $sql = "
                INSERT INTO mailbot (betreff, inhalt, adressat, absender, mail_status)
                VALUES (?, ?, ?, ?, 'warte')
                ";
        $params = [$betreff, $inhalt, $adressaten, $absender];
        dbi::$db->query($sql, $params)->log();
    }

    /**
     * Ändert den Status einer Email in der Datenbank
     *
     * @param int $mail_id
     * @param string $mail_status
     * @param string|null $fehler
     */
    public static function set_status(int $mail_id, string $mail_status, string $fehler = NULL)
    {
        $sql = "
            UPDATE mailbot 
            SET mail_status = ?, zeit = zeit, fehler = ? 
            WHERE mail_id = ?
            ";
        dbi::$db->query($sql, $mail_id, $mail_status, $fehler)->log();

    }

    /**
     * Erstellt eine Warnung im Ligacenter, wenn der Mailbot manche mails nicht versenden kann.
     */
    public static function warning_mail()
    {
        $sql = "
            SELECT mail_id
            FROM mailbot 
            WHERE mail_status = 'fehler'
            ";
        if ($anzahl = dbi::$db->query($sql)->num_rows() > 0)
            Form::attention("Der Mailbot kann $anzahl Mail(s) nicht versenden - siehe Datenbank.");
    }

    /**
     * Schreibt eine Mail in die Datenbank an alle spielberechtigten Teams, wenn es (zum Übergang zur Meldephase) noch
     * freie Plätze gibt.
     *
     * @param Turnier $turnier
     */
    public static function mail_plaetze_frei(Turnier $turnier)
    {
        if ($turnier->get_anzahl_freie_plaetze() > 0 && in_array($turnier->details['art'], ['I', 'II', 'III'])) {
            $team_ids = Team::get_liste_ids();
            foreach ($team_ids as $team_id) {
                // Noch Plätze frei
                if (
                    !$turnier->check_team_angemeldet($team_id)
                    && $turnier->check_team_block($team_id)
                    && !$turnier->check_doppel_anmeldung($team_id)
                ) {
                    $betreff = "Freie Plätze: " . $turnier->details['tblock'] . "-Turnier in " . $turnier->details['ort'];
                    ob_start();
                        include(__dir__ . "../templates/mails/mail_anfang.tmp.php");
                        include(__dir__ . "../templates/mails/mail_plaetze_frei.tmp.php");
                        include(__dir__ . "../templates/mails/mail_ende.tmp.php");
                    $inhalt = ob_get_clean();
                    $emails = (new Kontakt ($team_id))->get_emails('info');
                    self::add_mail($betreff, $inhalt, $emails);
                }
            }
        }
    }

    /**
     * Erstellt eine Mail in der Datenbank an Teams, welche in von der Warteliste aufgerückt sind
     *
     * @param Turnier $turnier
     * @param int $team_id
     */
    public static function mail_warte_zu_spiele(Turnier $turnier, int $team_id)
    {
        $betreff = "Spielen-Liste: " . $turnier->details['tblock'] . "-Turnier in " . $turnier->details['ort'];
        ob_start();
            include(__dir__ . "../templates/mails/mail_anfang.tmp.php");
            include(__dir__ . "../templates/mails/mail_warte_zu_spiele.tmp.php");
            include(__dir__ . "../templates/mails/mail_ende.tmp.php");
        $inhalt = ob_get_clean();
        $akt_kontakt = new Kontakt ($team_id);
        $emails = $akt_kontakt->get_emails('info');
        self::add_mail($betreff, $inhalt, $emails);
    }

    /**
     * Erstellt eine Mail in der Datenbank an alle vom Losen betroffenen Teams
     *
     * @param Turnier $turnier
     */
    public static function mail_gelost(Turnier $turnier)
    {
        if (in_array($turnier->details['art'], ['I', 'II', 'III'])) {
            $team_ids = Team::get_liste_ids();
            foreach ($team_ids as $team_id) {
                // Team angemeldet?
                if ($turnier->check_team_angemeldet($team_id)) {
                    // Auf Warteliste gelandet
                    if ($turnier->get_liste($team_id) == 'warte') {
                        $liste = "Warteliste";
                        // Auf Spielen-Liste gelandet
                    } elseif ($turnier->get_liste($team_id) == 'spiele') {
                        $liste = "Spielen-Liste";
                    } else {
                        return;
                    }
                    $betreff = "$liste: " . $turnier->details['tblock'] . "-Turnier in " . $turnier->details['ort'];
                    ob_start();
                        include(__dir__ . "../templates/mails/mail_anfang.tmp.php");
                        include(__dir__ . "../templates/mails/mail_gelost.tmp.php");
                        include(__dir__ . "../templates/mails/mail_ende.tmp.php");
                    $inhalt = ob_get_clean();
                    $emails = (new Kontakt ($team_id))->get_emails('info');
                    self::add_mail($betreff, $inhalt, $emails);
                }
            }
        }
    }

    /**
     * Erstellt eine Mail in der Datenbank an alle spielberechtigten Teams, wenn ein neues Turnier eingetragen wird
     *
     * @param Turnier $turnier
     */
    public static function mail_neues_turnier(Turnier $turnier)
    {
        if (in_array($turnier->details['art'], ['I', 'II', 'III'])) {
            $team_ids = Team::get_liste_ids();
            foreach ($team_ids as $team_id) {
                // Noch Plätze frei?
                if ($turnier->check_team_block($team_id) && !$turnier->check_doppel_anmeldung($team_id)) {
                    $betreff = "Neues " . $turnier->details['tblock'] . "-Turnier in " . $turnier->details['ort'];
                    ob_start();
                        include(__dir__ . "../templates/mails/mail_anfang.tmp.php");
                        include(__dir__ . "../templates/mails/mail_neues_turnier.tmp.php");
                        include(__dir__ . "../templates/mails/mail_ende.tmp.php");
                    $inhalt = ob_get_clean();
                    $emails = (new Kontakt ($team_id))->get_emails('info');
                    self::add_mail($betreff, $inhalt, $emails);
                }
            }
        }
    }

    /**
     * Erstellt eine Mail in der Datenbank, wenn ein Team trotz Freilos beim übergang in die Datenbank abgemeldet wird.
     *
     * @param Turnier $turnier
     * @param int $team_id
     */
    public static function mail_freilos_abmeldung(Turnier $turnier, int $team_id)
    {
        if (in_array($turnier->details['art'], ['I', 'II', 'III'])) {
            $betreff = "Falscher Freilosblock: " . $turnier->details['tblock'] . "-Turnier in " . $turnier->details['ort'];
            ob_start();
                include(__dir__ . "../templates/mails/mail_anfang.tmp.php");
                include(__dir__ . "../templates/mails/mail_freilos_abmeldung.tmp.php");
                include(__dir__ . "../templates/mails/mail_ende.tmp.php");
            $inhalt = ob_get_clean();
            $emails = (new Kontakt ($team_id))->get_emails('info');
            self::add_mail($betreff, $inhalt, $emails);
        }
    }

    /**
     * Erstellt eine Mail in der Datenbank an den Ligaausschuss, wenn ein Team Turnierdaten ändert.
     *
     * @param Turnier $turnier
     */
    public static function mail_turnierdaten_geaendert(Turnier $turnier)
    {
        $betreff = "Turnierdaten geändert: " . $turnier->details['tblock'] . "-Turnier in " . $turnier->details['ort'];
        ob_start();
            include(__dir__ . "../templates/mails/mail_turnierdaten_geaendert.tmp.php");
        $inhalt = ob_get_clean();
        self::add_mail($betreff, $inhalt, Config::LAMAIL);
    }
}