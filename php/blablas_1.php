<?php

ob_start(); //démarre la bufferisation

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

$bd = em_bd_connect();

$sql = 'SELECT usPseudo, usNom, blTexte, blDate, blHeure
        FROM users
        INNER JOIN blablas ON blIDAuteur = usID
        WHERE usID = 2
        ORDER BY blID DESC';

$res = em_bd_send_request($bd, $sql);

em_aff_debut('Cuiteur | Blablas');

echo '<h1>', 'Les blablas de ';


// Récupération des données et encapsulation dans du code HTML envoyé au navigateur 
$i = 0;
while ($t = mysqli_fetch_assoc($res)) {
    if ($i == 0){
        echo em_html_proteger_sortie($t['usPseudo']), '</h1><ul>';
    }
    echo    '<li>', 
                em_html_proteger_sortie($t['usPseudo']), ' ', em_html_proteger_sortie($t['usNom']), '<br>',
                em_html_proteger_sortie($t['blTexte']), '<br>',
                em_amj_clair($t['blDate']), ' à ', em_heure_clair($t['blHeure']),
            '</li>';
    ++$i;
}
echo '</ul>';

// libération des ressources
mysqli_free_result($res);
mysqli_close($bd);

em_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();



?>
