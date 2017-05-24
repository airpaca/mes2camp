<?php 

/* Récupération de variables */
$type = $_GET['type'];
$sql_queries = $_GET['sql_queries'];

/* Chargement du fichier de config en fonction de l'utilisateur */
if ($user == "admin") {
    include 'config_su.php';
} else {
    include 'config.php';
}

/* Connexion à PostgreSQL */
$conn = pg_connect("dbname='" . $pg_db . "' user='" . $pg_lgn . "' password='" . $pg_pwd . "' host='" . $pg_host . "'");
if (!$conn) {
    echo "Not connected";
    exit;
}

/* Update des tubes */
$sql = $sql_queries;

$res = pg_query($conn, $sql);
if (!$res) {
    echo "Erreure de mise à jour.";
    exit;
}

echo "Tubes mis à jour.";

?>
