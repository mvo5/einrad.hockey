<?php

// Inititalisierung
$team_id = ($teamcenter) ? $_SESSION['team_id'] : (int)$_GET['team_id'];
$team = new Team ($team_id);
$path = ($ligacenter) ? '../liga/lc_teamdaten_aendern.php' : '../teamcenter/tc_teamdaten_aendern.php';
if (empty($team->details)) {
    die("Team wurde nicht gefunden");
}

$kontakte = new Kontakt ($team->id);
$emails = $kontakte->get_emails_with_details();

// Trikotfarben verwalten
if (
    isset($_POST['color_1'])
    or isset($_POST['color_2'])
    or isset($_POST['no_color_2'])
    or isset($_POST['no_color_1'])
) {
    if (isset($_POST['color_1']))
        $team->set_detail('trikot_farbe_1', $_POST['color_1']);
    if (isset($_POST['color_2']))
        $team->set_detail('trikot_farbe_2', $_POST['color_2']);
    if (isset($_POST['no_color_1']))
        $team->set_detail('trikot_farbe_1', '');
    if (isset($_POST['no_color_2']))
        $team->set_detail('trikot_farbe_2', '');
    Form::affirm("Trikotfarbe geändert.");
    header("Location:" . $path . '#trikotfarbe');
    die();
}

// Teamdaten ändern
if (isset($_POST['teamdaten_aendern'])) {

    $error = false;
    if (empty($_POST['ligavertreter'])) {
        Form::error('Es muss ein Ligavertreter angegeben werden');
        $error = true;
    }
    if (empty($_POST['dsgvo'])) {
        Form::error('Der Ligavertreter muss den Datenschutz-Hinweisen zustimmen.');
        $error = true;
    }

    if (!$error) {
        $array = ['plz', 'ort', 'verein', 'homepage', 'ligavertreter'];
        foreach ($array as $entry) {
            if ($team->details[$entry] != $_POST[$entry]) {
                $team->set_detail($entry, $_POST[$entry]);
            }
        }
    }

    // Emails
    foreach ($kontakte->get_emails_with_details() as $email) {
        if ($email['public'] != ($_POST['public' . $email['teams_kontakt_id']] ?? '')) {
            $kontakte->set_public($email['teams_kontakt_id'], $_POST['public' . $email['teams_kontakt_id']]);
        }
        if ($email['get_info_mail'] != ($_POST['info' . $email['teams_kontakt_id']] ?? '')) {
            $kontakte->set_info($email['teams_kontakt_id'], $_POST['info' . $email['teams_kontakt_id']]);
        }
        if ("Ja" == ($_POST['delete' . $email['teams_kontakt_id']]) ?? '') {
            if ($kontakte->delete_email($email['teams_kontakt_id'])) {
                Form::affirm($email['email'] . " wurde gelöscht");
            } else {
                Form::error("Es muss mindestens eine E-Mail-Adresse hinterlegt sein");
            }
        }
    }
    Form::affirm("Teamdaten wurden gespeichert.");
    header('Location: ' . $path);
    die();
}

// Verarbeitung des Formulars zum Eintragen einer Email
if (isset($_POST['neue_email'])) {
    $email = $_POST['email'];
    $infomail = $_POST['get_info_mail'];
    $public = $_POST['public'];

    if (filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
        $kontakte->create_new_team_kontakt($email, $public, $infomail);
        Form::affirm("E-Mail-Adresse wurde hinzugefügt");
        header('Location: ' . $path);
        die();
    } else {
        Form::error("E-Mail-Adresse wurde nicht akzeptiert");
    }
}

// Teamfoto hochladen
if (isset($_POST['teamfoto'])) {
    if (!empty($_FILES["jpgupload"]["tmp_name"])) {
        //Bild wird hochgeladen, target_file_jpg = false, falls fehlgeschlagen.
        $target_file_jpg = Neuigkeit::upload_bild($_FILES["jpgupload"]);
        if ($target_file_jpg === false) {
            Form::error("Fehler beim Fotoupload");
        } else {
            $team->set_teamfoto($target_file_jpg);
            Form::affirm("Teamfoto wurde hochgeladen");
            header('Location: ' . $path);
            die();
        }
    }
}

// Teamfoto löschen
if (isset($_POST['delete_teamfoto'])) {
    $team->delete_teamfoto($daten['teamfoto']);
    Form::affirm("Teamfoto wurde gelöscht");
    header('Location: ' . $path);
    die();
}