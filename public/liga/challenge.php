<?php
/////////////////////////////////////////////////////////////////////////////
////////////////////////////////////LOGIK////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////
require_once '../../logic/first.logic.php'; //autoloader und Session
require_once '../../logic/challenge.logic.php'; //Erstellt Challenge-Objekt nach der Validation

$color[0] = "background-color: #a6b2d8;";
$color[1] = "background-color: #b8c1e0;";
$color[2] = "background-color: #cad0e8;";

/////////////////////////////////////////////////////////////////////////////
////////////////////////////////////LAYOUT///////////////////////////////////
/////////////////////////////////////////////////////////////////////////////
$titel = "km-Challenge | Deutsche Einradhockeyliga";
$content = 'Hier sind die aktuellen Ergebnisse der Kilometer-Challenge zu sehen.';
include '../../templates/header.tmp.php';
?>

<!-- Überschrift -->
<h1 class="w3-text-primary">km-Challenge</h1>
<span class="w3-text-gray w3-small"><?=$challenge->challenge_start?> bis <?=$challenge->challenge_end?></span>

<!-- Countdown -->
<?=Form::countdown($challenge->challenge_end)?>

<!-- Button zum Teamcenter -->
<p class="w3-text-gray">
    Hier habt ihr die Möglichkeit die geradelten Kilometer einzutragen. Gewertet werden eure Einträge als Einzelperson, aber immer auch im Team.
    Also ran ans Rad und viel Spaß beim Sammeln.
</p>

<a href='../teamcenter/tc_challenge.php' class="w3-button w3-secondary">Kilometer eintragen!</a>

<div class='w3-card w3-panel w3-leftbar w3-border-red w3-pale-red'>
    <h3>Fehler im Formular korrigiert</h3>
    <p>Aufgrund eines Fehlers konnten manche Radgrößen (z.B. 27,5 Zoll) nicht im Formular eingegeben werden. Dieser Fehler ist nun behoben.</p>
</div>

<div class='w3-card w3-panel w3-leftbar w3-border-yellow w3-pale-yellow'>
    <h3>Korrektur von Eingaben</h3>
    <p>Solltet ihr aufgrund des Formularfehlers eine andere Radgröße 
    anstelle der richtigen angegeben haben, dann schickt und bitte eine Mail mit Namen, Datum und richtiger Radgröße an <br />
    <?=Form::mailto(Config::TECHNIKMAIL)?> oder <?=Form::mailto(Config::OEFFIMAIL)?>
    <br /><br />
    Vielen Dank für euer Verständnis!
    </p>
</div>

<h3 class="w3-text-secondary w3-margin-top">Teams</h3>
<div class="w3-responsive w3-card">
    <table class="w3-table w3-striped">
        <tr class="w3-primary">
            <th class="w3-center">Platz</th>
            <th class="w3-left-align">Team</th>
            <th class="w3-center w3-hide-small">Mitglieder</th>
            <th class="w3-center w3-hide-small">Einträge</th>
            <th class="w3-right-align">Kilometer</th>               
        <tr>
        <?php 
            if ($teamliste == NULL) {
                echo "<tr>";
                echo "<td colspan='5' class='w3-center'>Bisher keine Einträge vorhanden.</td>";
                echo "<tr>";
            } else {
                foreach ($teamliste as $team){?> 
                    <tr style="<?=$color[$team["platz"] - 1] ?? ''?>">
                        <td class="w3-center"><?=$team["platz"]?></td>
                        <td style="white-space: nowrap;" class="w3-left-align"><?=$team["teamname"]?></td>
                        <td class="w3-center w3-hide-small"><?=$team["mitglieder"]?></td>
                        <td class="w3-center w3-hide-small"><?=$team["einträge"]?></td>
                        <td class="w3-right-align"><?=number_format($team["kilometer"], 1, ',', '.');?></td>
                    </tr>
                <?php } //end foreach 
            } //end if
            ?>
    </table>
</div>


<h3 class="w3-text-secondary w3-margin-top">Spieler/innen</h3>
<div class="w3-responsive w3-card">
    <table class="w3-table w3-striped">
        <tr class="w3-primary">
            <th class="w3-center">Platz</th>
            <th class="w3-left-align">Spieler/in</th>
            <th style="white-space: nowrap;" class="w3-left-align">Team</th>
            <th class="w3-center w3-hide-small">Einträge</th>
            <th class="w3-right-align">Kilometer</th>
        <tr>
        <?php 
            if ($alle_spielerliste == NULL) {
                echo "<tr>";
                echo "<td colspan='5' class='w3-center'>Bisher keine Einträge vorhanden.</td>";
                echo "<tr>";
            } else {            
                foreach ($alle_spielerliste as $spieler){?> 
                    <tr style="<?=$color[$spieler["platz"] - 1] ?? ''?>">
                        <td class="w3-center"><?=$spieler["platz"]?></td>
                        <td class="w3-left-align"><?=$spieler['vorname']?></td>
                        <td class="w3-left-align"><?=$spieler['teamname']?></td>
                        <td class="w3-center w3-hide-small"><?=$spieler['einträge']?></td>
                        <td class="w3-right-align"><?=number_format($spieler["kilometer"], 1, ',', '.');?></td>
                    </tr>
            <?php } //end foreach 
            } //end if
        ?>
    </table>
</div>

<?php include '../../templates/footer.tmp.php';