<?php
/////////////////////////////////////////////////////////////////////////////
////////////////////////////////////LOGIK////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////
require_once '../../logic/first.logic.php'; //autoloader und Session
require_once '../../logic/session_la.logic.php'; //Auth

//Formularauswertung
if(isset($_POST['team_erstellen'])) {
    $error = false;
    $teamname = $_POST['teamname'];
    $passwort = $_POST['passwort'];
    $email = $_POST['email'];
    
    //Felder dürfen nicht leer sein
    if(empty($teamname) or empty($email) or empty($passwort)) {
        Form::error("Bitte alle Felder ausfüllen");
        $error = true;
    }

    //Email wird auf gültigkeit überprüft
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Form::error("Ungültige Email");
        $error = true;
    }

    //Nichtligateams bekommen ein Stern hinter ihrem Namen, damit sie nicht Teamnamen für Ligateams wegnehmen.
    if(!empty(Team::teamname_to_teamid($teamname))){
        Form::error("Der Teamname existiert bereits");
        $error = true;
    }

    //Team wird erstellt
    if(!$error) {
        $team_id = db::get_auto_increment("teams_liga");
        Team::create_new_team($teamname,$passwort,$email);
        Form::affirm("Das Team \"" . db::escape($teamname) . "\" wurde erfolgreich erstellt.<br> Email:" . db::escape($email) . "<br> Passwort: $passwort");
        header ("Location: lc_teamdaten.php?team_id=" . $team_id);
        die();
    }
}

/////////////////////////////////////////////////////////////////////////////
////////////////////////////////////LAYOUT///////////////////////////////////
/////////////////////////////////////////////////////////////////////////////
include '../../templates/header.tmp.php';
?>

<div class="w3-card-4 w3-panel">
    <form method="post">
    <h3>Neues Ligateam</h3>

    <label class="w3-text-primary" for="teamname">Teamname:</label><br>
    <input required class="w3-input w3-border w3-border-primary" type="text" id="teamname" value="<?=$_POST['teamname'] ?? ''?>" name="teamname">
    <p>
    <label class="w3-text-primary" for="passwort">Passwort:</label><br>
    <input required class="w3-input w3-border w3-border-primary" type="text" id="passwort" value="<?=$_POST['passwort'] ?? ''?>" name="passwort">
    </p>
    <p>
    <label class="w3-text-primary" for="email">E-Mail:</label><br>
    <input class="w3-input w3-border w3-border-primary" type="email" id="email" value="<?=$_POST['email'] ?? ''?>" name="email">
    </p>
    <p>
    <input class="w3-button w3-block w3-secondary" required type="submit" name="team_erstellen" value="Team erstellen">
    </p>
    </form>
</div>

<div class="w3-card-4 w3-panel">
    <p>
    <a class='w3-button w3-block w3-primary' href='lc_start.php'><i class="material-icons">chevron_left</i>Zurück<i class="material-icons" style="visibility: hidden">chevron_right</i></a>
    </p>
</div>    

<?php include '../../templates/footer.tmp.php';