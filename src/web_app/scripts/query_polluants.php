<?php 

include 'config.php';

$tube_id = $_GET['tube_id'];

# Connexion à PostgreSQL
$conn = pg_connect("dbname='" . $pg_db . "' user='" . $pg_lgn . "' password='" . $pg_pwd . "' host='" . $pg_host . "'");
if (!$conn) {
    echo "Not connected";
    exit;
}

$sql = "
/** 
Combien de polluants sont mesurés
*/
select count(*) as nb_poll
from (
    select distinct id_polluant
    from c_template.mesures
    where tube_id = " . $tube_id . "
) as a;
";

$res = pg_query($conn, $sql);
if (!$res) {
    echo "An SQL error occured.\n";
    exit;
}

$myarray = array();
while ($row = pg_fetch_assoc( $res )/*pg_fetch_row($contests)*/) {
  $myarray[] = $row;
}
header('Content-Type: application/json');
echo json_encode($myarray);

?>