<?php
/////////////////////////////////////////////////////////////////////////////
////////////////////////////////////LOGIK////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////
require_once '../../logic/first.logic.php'; //autoloader und Session
require_once '../../logic/session_team.logic.php'; //Auth

$akt_team = new Team ($_SESSION['team_id']);
$akt_team_kontakte = new Kontakt ($_SESSION['team_id']);

//Werden an teamdaten.tmp.php übergeben
$emails = $akt_team_kontakte->get_emails_with_details();
$daten = $akt_team ->get_teamdaten();

/////////////////////////////////////////////////////////////////////////////
////////////////////////////////////LAYOUT///////////////////////////////////
/////////////////////////////////////////////////////////////////////////////
$page_width = "800px";
include '../../templates/header.tmp.php';
include '../../templates/teamdaten.tmp.php';
?>

<!--Navigation-->
<p>
  <?=Form::link('tc_teamdaten_aendern.php', '<i class="material-icons">create</i> Teamdaten ändern')?>
</p>

<?php include '../../templates/footer.tmp.php';