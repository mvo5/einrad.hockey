<?php

class Helper
{
    /**
     * Authentification
     * $teamcenter und $ligacenter werden in session_*.logic.php ggf überschrieben
     */
    public static bool $ligacenter = false; // Befindet sich der Ligaausschuss auf einer Seite im Ligacenter?
    public static bool $teamcenter = false; // Befindet sich das Team auf einer Seite im Teamcenter?


    /**
     * Teamcenter freischalten? (PW geändert, Ligavertreter angegeben?)
     * Ansonsten redirect zu Passwort ändern bzw. Ligavertreter eintragen in session_team.logic.php
     */
    public static bool $teamcenter_no_redirect = false;


    /**
     * Weiterleitung zu einer anderen Seite.
     * Beendet die weitere Ausführung des Skriptes.
     *
     * Verwendung zB nach Formularverarbeitungen.
     *
     * @param string|null $path
     */
    public static function reload(?string $path = null): void
    {
        $url = ($path === null) ? dbi::escape($_SERVER['PHP_SELF']) : Env::BASE_URL . $path;
        header("Location: $url");
        die();
    }


    /**
     * Weiterletiung zur 404.php not_found
     * zB bei ungültiger Team-ID in Get-Variable.
     * @param string $text
     */
    public static function not_found(string $text): void
    {
        trigger_error($text, E_USER_NOTICE);
        $_SESSION['error']['text'] = $text;
        $_SESSION['error']['url'] = $_SERVER['REQUEST_URI'];
        self::reload('/errors/404.php');
    }


    /**
     * Erstellt einen Log in einer Logdatei im System-Ordner
     *
     * @param string $file_name Name der Logdatei
     * @param string $line Einzutragender Text in die Logdatei
     * @param bool $hide_akteur Soll ein Name hinterlegt werden?
     */
    public static function log(string $file_name, string $line, $hide_akteur = false): void
    {
        $path = Env::BASE_PATH . '/system/logs/';
        $log_file = fopen($path . $file_name, 'ab');
        $akteur = ($hide_akteur) ? '' : ' [' . self::get_akteur() . ']';
        $line = date('[Y-m-d\TH:i:s]') . $akteur . ":\n" . $line . "\n\n";

        fwrite($log_file, $line);
        fclose($log_file);
    }


    /**
     * Welcher Akteur benutzt gerade die Seite? zB für Logs.
     *
     * @param bool $hide_la_name Soll der Loginname oder nur allgemein Ligaausschuss ermittelt werden?
     * @return string
     */
    public static function get_akteur(bool $hide_la_name = false): string
    {
        // Sind wir im Ligacenter?
        if (self::$ligacenter){
            return ($hide_la_name) ? "Ligaausschuss" : $_SESSION['logins']['la']['login'];
        }

        // Sind wir im Teamcenter?
        if (self::$teamcenter) {
            return $_SESSION['logins']['team']['name'];
        }

        // Welche Akteure können sonst alles am Werk sein?
        $akteure = [
            $_SESSION['logins']['team']['name'] ?? '',
            $_SESSION['logins']['la']['login'] ?? '',
            $_SESSION['logins']['ligabot'] ?? '',
            $_SESSION['logins']['cronjob'] ?? ''
        ];
        return implode(" | ", array_filter($akteure)) ?: 'Unbekannt';
    }

}

