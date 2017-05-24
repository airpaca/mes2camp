<?php 

/* Récupération de variables */
if (isset($_POST['login'])) {
    $login = strip_tags($_POST['login']);
    $pwd = strip_tags($_POST['pwd']);   
};
    
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

/* Sélection des données pour l'utilisateur */   
$sql = "
/** 
Recherche des droits pour un utilisateur
*/
select login_utilisateur, nom_utilisateur, prenom_utilisateur, nom_privilege
from c_template.utilisateurs as a
left join c_template.utilisateurs_privileges as b using (id_privilege)
where 
	login_utilisateur = '" . $login . "' 
	and password_utilisateur =  crypt('" . $pwd . "', password_utilisateur)
; 
";
   
$res = pg_query($conn, $sql);
if (!$res) {
    echo "An SQL error occured.\n";
    exit;
}

$array_res = array();
while ($row = pg_fetch_assoc( $res )) {
  $array_res[] = $row;
}   
    
/* Export en JSON */
header('Content-Type: application/json');
echo json_encode($array_res);    
    
?>    