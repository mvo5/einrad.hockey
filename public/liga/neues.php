<?php
/////////////////////////////////////////////////////////////////////////////
////////////////////////////////////LOGIK////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////
require_once '../../logic/first.logic.php'; //autoloader und Session
$fortschritt = round(100*(time()-strtotime(Config::SAISON_ANFANG))/(strtotime(Config::SAISON_ENDE)-strtotime(Config::SAISON_ANFANG)));
$tage = round((strtotime(Config::SAISON_ANFANG) - time())/(24*60*60));

$neuigkeiten = Neuigkeit::get_neuigkeiten(); //Alle Neuigkeiten werden übergeben, da kein Argument überliefert
                                            //Es werden die 10 letzten Neuigkeiten angzeigt

$turniere = Turnier::get_turniere('ergebnis', false, true);
$anz_next_turniere = count($turniere);
$next_turniere = array_slice($turniere, 0, 3);

$turniere = Turnier::get_turniere('ergebnis', true, true);
$anz_last_turniere = count($turniere);
$last_turniere = array_slice($turniere, 0, 3);

// Zuordnen der Farben für 1. 2. 3. Platz der Statistiken
$colors = array("w3-text-tertiary", "w3-text-grey", "w3-text-brown");
$icons = array("looks_one", "looks_two", "looks_3");

$statistik['max_gew'] = Neuigkeit::get_statistik_gew_spiele();
$statistik['max_turniere'] = Neuigkeit::get_statistik_turniere();
$statistik['ges_tore'] = Neuigkeit::get_alle_tore();
$statistik['ges_spiele'] = Neuigkeit::get_alle_spiele();

//Zeitanzeige der Neuigkeiteneinträge verschönern
foreach ($neuigkeiten as $neuigkeiten_id => $neuigkeit){
    $delta_zeit = (time() - strtotime($neuigkeiten[$neuigkeiten_id]['zeit']))/(60*60); //in Stunden
    if ($delta_zeit < 24){
        $zeit = ($delta_zeit <= 1.5) ? "gerade eben" : "vor " . round($delta_zeit) ." Stunden";
    }elseif ($delta_zeit < 7*24){
            $zeit = ($delta_zeit <= 1.5*24) ? "vor einem Tag" : "vor " . round($delta_zeit/24) ." Tagen";
    }else{
        $zeit = date("d.m.Y", strtotime($neuigkeiten[$neuigkeiten_id]['zeit']));
    }
    $neuigkeiten[$neuigkeiten_id]['zeit'] = $zeit;
}

/////////////////////////////////////////////////////////////////////////////
////////////////////////////////////LAYOUT///////////////////////////////////
/////////////////////////////////////////////////////////////////////////////
$titel = "Neuigkeiten | Deutsche Einradhockeyliga";
$content = "Hier findet man die Neuigkeiteneinträge des Ligaausschusses und der Teams der Deutschen Einradhockeyliga.";
include '../../templates/header.tmp.php';
?>

<!-- Links (u. a zum Ein- und Ausblenden der Infobar bei Mobils) -->
<p class="w3-hide-large w3-hide-medium "><?= Form::link("ueber_uns.php", "Erfahre mehr über uns") ?></p>
<button id="einblenden"
        class="w3-right w3-hide-large w3-hide-medium w3-button w3-text-primary"
        onclick="einblenden()"
>
    Infobar anzeigen
</button>
<button id="ausblenden"
        class="w3-right w3-hide w3-hide-large w3-hide-medium w3-button w3-text-primary"
        onclick="ausblenden()"
>
    Infobar ausblenden
</button>

<!-- Responsive Container -->
<div class="w3-row-padding w3-stretch">
    <!-- Infobar -->
    <div class="w3-col l4 m5 w3-hide-small" id="infobar">

        <!-- Interesse -->
        <div class="w3-panel w3-card-4 w3-border-primary w3-responsive w3-round">
            <div class="w3-stretch w3-container w3-primary w3-hover-tertiary">
                <a href='ueber_uns.php' class="no">
                    <h1><?= Form::icon("help_outline", tag: "h1") ?> Interesse</h1>
                </a>
            </div>
            <p>Die Einradhockeyliga steht jedem Einradhockeybegeisterten offen!</p>
            <p><?=Form::link("ueber_uns.php", " Mehr Infos", false, "info")?></p>
        </div>

        <!-- Anstehende Turniere -->
        <div class="w3-panel w3-card-4 w3-border-primary w3-responsive w3-round">
            <div class="w3-stretch w3-container w3-primary w3-hover-tertiary">
                <a href='turniere.php' class="no">
                    <h2><?= Form::icon("event", tag: "h2") ?> Turniere</h2>
                </a>
            </div>
            <?php if (empty($next_turniere)){ ?><p class="w3-text-grey">Es liegen keine Ergebnisse vor</p><?php } //end if?>
            <?php $i=0; foreach ($next_turniere as $turnier){  $i++;?>
                <p class="w3-text-dark-gray">
                    <?=date("d.m", strtotime($turnier['datum']))?>
                    <?=Form::link(
                            'turnier_details.php?turnier_id=' . $turnier['turnier_id'],
                            ' ' . $turnier['tname'] .' '. $turnier['ort'],
                            true,
                            "open_in_new")?>
                    <i>(<?=$turnier['tblock']?>)</i>
                </p>
                <?php if ($i > 4){ break; }?>
            <?php } //end foreach?>
        </div>

        <!-- Ergebnisse -->
        <div class="w3-panel w3-card-4 w3-border-primary w3-responsive w3-round">
            <div class="w3-stretch w3-container w3-primary w3-hover-tertiary">
                <a href='ergebnisse.php' class="no">
                    <h2><?= Form::icon("sports_hockey", tag: "h2") ?> Ergebnisse</h2>
                </a>
            </div>
            <p class="w3-text-grey w3-border-top w3-border-grey"></p>
            <?php if (empty($last_turniere)){?>
                <p class="w3-text-grey">
                    Es liegen keine Ergebnisse vor
                </p>
            <?php } //end if?>
            <?php $i=0; foreach ($last_turniere as $turnier){ $i++;?>
                <p class="w3-text-dark-gray">
                    <?=date("d.m", strtotime($turnier['datum']))?>
                    <?= Form::link(
                            'ergebnisse.php#' . $turnier['turnier_id'],
                            $turnier['tname'] .' '. $turnier['ort'],
                            true,
                            'open_in_new') ?>
                    <i>(<?=$turnier['tblock']?>)</i>
                </p>
                <?php if ($i > 4){ break; }?>
            <?php } //end foreach?>
        </div>

        <!-- Statistik -->
        <div class="w3-panel w3-card-4 w3-border-primary w3-responsive w3-round">
            <div class="w3-stretch w3-container w3-primary">
                <h2><?= Form::icon("insert_chart_outlined", tag: "h2") ?> Statistik</h2>
            </div>

            <!-- Allgemeine Statistik -->
            <div class="w3-section">
                <div class="w3-responsive">
                    <table class="w3-table w3-bordered">
                        <tr class="w3-bottombar w3-text-grey w3-large w3-border-primary">
                            <td colspan="3"><?= Form::icon("insert_chart_outlined") ?> Allgemein</td>
                        </tr>
                        <tr>
                            <td class="w3-text-primary"><?= Form::icon("check") ?></td>
                            <td><?= $anz_last_turniere ?></td>
                            <td class="w3-small">gespielte Turniere</td>
                        </tr>
                        <tr>
                            <td class="w3-text-primary"><?= Form::icon("double_arrow") ?></td>
                            <td><?= $anz_next_turniere ?></td>
                            <td class="w3-small">anstehende Turniere</td>
                        </tr>
                        <tr>
                            <td class="w3-text-primary"><?= Form::icon("sports_baseball") ?></td>
                            <td><?= $statistik['ges_tore'] ?></td>
                            <td class="w3-small">Tore</td>
                        </tr>
                        <tr>
                            <td class="w3-text-primary"><?= Form::icon("sports_hockey") ?></td>
                            <td><?= $statistik['ges_spiele'] ?></td>
                            <td class="w3-small">Spiele</td>
                        </tr>
                        <tr>
                            <td class="w3-text-primary"><?= Form::icon("schedule") ?></td>
                            <td style="white-space: nowrap;"><?= $statistik['ges_spiele'] * (30+24+18)/3 ?></td>
                            <td class="w3-small">Spielminuten*</td>
                        </tr>
                    </table>
                </div>
                <span class="w3-text-grey w3-small">* geschätzt</span>
            </div>

            <!-- Wer hat am meisten Turniere gespielt? -->
            <?php if (!empty($statistik['max_turniere'])) {?>
                <div class="w3-section">
                    <div class="w3-responsive">
                        <table class="w3-table w3-centered w3-bordered">
                            <tr class="w3-bottombar w3-text-grey w3-large w3-border-primary">
                                <td><?= Form::icon("leaderboard") ?></td>
                                <td>Turnierspieler</td>
                                <td><?= Form::icon("assistant_photo") ?></td>
                            </tr>
                            <?php $i = 0; foreach ($statistik['max_turniere'] as $team){?>
                                <tr class="<?= $colors[$i] ?>">
                                    <td><?= Form::icon($icons[$i++]) ?></td>
                                    <td style="white-space: nowrap;" class="w3-small"><?=$team['teamname']?></td>
                                    <td><?=$team['gespielt']?></td>
                                </tr>
                            <?php } //end foreach?>
                        </table>
                    </div>
                    <span class="w3-text-grey w3-small">
                        <?= Form::icon("assistant_photo") ?> Anzahl gespielter Turniere
                    </span>
                </div>
            <?php }else{ ?>
                <p class="w3-text-grey w3-center">Keine gespielten Turniere</p>
            <?php } //end if?>

            <!-- Wer hat am meisten Spiele gewonnen? -->
            <?php if (!empty($statistik['max_gew'])) { ?>
                <div class="w3-section">
                    <div class="w3-responsive">
                        <table class="w3-table w3-centered w3-bordered">
                            <tr class="w3-bottombar w3-text-grey w3-large w3-border-primary">
                                <td><?= Form::icon("leaderboard") ?></td>
                                <td>Spielgewinner</td>
                                <td><?= Form::icon("sports_hockey") ?></td>
                            </tr>
                            <?php $i=0; foreach ($statistik['max_gew'] as $team_id => $gew_spiele){ ?>
                                <tr class="<?= $colors[$i] ?>">
                                    <td><?= Form::icon($icons[$i++]) ?></td>
                                    <td style="white-space: nowrap;" class="w3-small"><?= Team::teamid_to_teamname($team_id) ?></td>
                                    <td><?= $gew_spiele ?></td>
                                </tr>
                            <?php } //end foreach?>
                        </table>
                    </div>
                    <span class="w3-text-grey w3-small">
                        <?= Form::icon("sports_hockey") ?> Anzahl gewonnener Spiele
                    </span>
                </div>
            <?php }//endif?>
        </div>

        <!-- Links -->
        <div class="w3-panel w3-card-4 w3-border-primary w3-responsive w3-round">
            <div class="w3-stretch w3-container w3-primary">
                <h2><?= Form::icon("public", tag: "h2") ?> Links</h2>
            </div>
            <p class="w3-text-grey w3-border-top w3-border-grey"><?= Form::icon("bookmark") ?> Ligen</p>
            <p><?=Form::link(Config::LINK_SWISS, " Schweizer Einradhockeyliga", true, "link")?></p>
            <p><?=Form::link(Config::LINK_AUSTRALIA, " Australische Einradhockeyliga", true, "link")?></p>
            <p><?=Form::link(Config::LINK_FRANCE, " Französische Einradbasketballliga", true, "link")?></p>

            <p class="w3-text-grey w3-border-top w3-border-grey"><?= Form::icon("bookmark") ?> Verbände</p>
            <p><?=Form::link(Config::LINK_EV, " Einradverband Deutschland", true, "link")?></p>
            <p><?=Form::link(Config::LINK_EV_SH, " Einradverband Schleswig-Holstein", true, "link")?></p>
            <p><?=Form::link(Config::LINK_EV_BY, " Einradverband Bayern", true, "link")?></p>

            <p class="w3-text-grey w3-border-top w3-border-grey"><?= Form::icon("bookmark") ?> Förderation</p>
            <p><?=Form::link(Config::LINK_IUF, " International Unicycle Federation", true, "link")?></p>


        </div>

    </div>

    <!-- Neuigkeiten-Einträge -->
    <div class="w3-col l8 m7">
        <?php foreach($neuigkeiten as $neuigkeit){ //Schleife für jede Neuigkeit?>
            <div class='w3-card-4 w3-panel w3-responsive w3-round'>

                <!-- Überschrift -->
                <div class="w3-stretch w3-container w3-primary w3-center">
                    <h2><?=$neuigkeit['titel']?></h2>
                </div>

                <!-- Autor -->
                <p class="w3-text-grey w3-margin-top w3-border-top w3-border-grey">
                    <?= Form::icon("create") ?> <?= ($neuigkeit['eingetragen_von']) ?>
                </p>

                <!-- Bild -->
                <?php if ($neuigkeit['link_jpg'] != ''){?>
                    <div class='w3-center w3-card w3-section'>
                        <a href='<?=$neuigkeit['bild_verlinken'] ?: $neuigkeit['link_jpg']?>'>
                            <img class='w3-image w3-hover-opacity' alt="<?=$neuigkeit['titel']?>" src=<?=$neuigkeit['link_jpg']?>>
                        </a>
                    </div>
                <?php } //end if?>

                <!-- Text -->
                <div class="w3-section">
                    <?= nl2br($neuigkeit['inhalt']) ?>
                </div>

                <!-- PDF -->
                <?php if ($neuigkeit['link_pdf'] != ''){?>
                    <span><?= Form::link($neuigkeit['link_pdf'], " Link zum Dokument", true, "insert_drive_file") ?></span>
                <?php } //end if?>

                <!-- Zeitstempel -->
                <div class='w3-text-grey w3-border-bottom w3-border-grey w3-margin-bottom w3-right-align'>
                    <?= Form::icon("schedule") ?> <?=$neuigkeit['zeit']?>
                </div>

                <!-- Link zum Bearbeiten falls man im Ligacenter oder Teamcenter eingeloggt ist -->
                <?php if (isset($_SESSION['la_id'])) {?>
                        <p>
                            <a href='../ligacenter/lc_neuigkeit_bearbeiten.php?neuigkeiten_id=<?= $neuigkeit['neuigkeiten_id'] ?>' class='no'>
                                <button class="w3-button w3-block w3-tertiary"><?=Form::icon("create")?> Bearbeiten</button>
                            </a>
                        </p>
                <?php }elseif (!empty($_SESSION['teamname']) && $_SESSION['teamname'] == $neuigkeit['eingetragen_von']) { ?>
                    <p>
                        <a href='../teamcenter/tc_neuigkeit_bearbeiten.php?neuigkeiten_id=<?= $neuigkeit['neuigkeiten_id'] ?>' class='no'>
                            <button class="w3-button w3-block w3-tertiary"><?=Form::icon("create")?> Bearbeiten</button>
                        </a>
                    </p>
                <?php } //end if?>
            </div>
        <?php } //end for?>
    </div>
</div>

<?php include '../../templates/footer.tmp.php';