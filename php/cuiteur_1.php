<?php

ob_start(); //démarre la bufferisation

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

$bd = em_bd_connect();

$sql = 'SELECT  DISTINCT auteur.usID AS autID, auteur.usPseudo AS autPseudo, auteur.usNom AS autNom, auteur.usAvecPhoto AS autPhoto, 
                blTexte, blDate, blHeure,
                origin.usID AS oriID, origin.usPseudo AS oriPseudo, origin.usNom AS oriNom, origin.usAvecPhoto AS oriPhoto
        FROM (((users AS auteur
        INNER JOIN blablas ON blIDAuteur = usID)
        LEFT OUTER JOIN users AS origin ON origin.usID = blIDAutOrig)
        LEFT OUTER JOIN estabonne ON auteur.usID = eaIDAbonne)
        LEFT OUTER JOIN mentions ON blID = meIDBlabla
        WHERE   auteur.usID = 23
        OR      eaIDUser = 23
        OR      meIDUser = 23
        ORDER BY blID DESC';

$res = em_bd_send_request($bd, $sql);

em_aff_debut('Cuiteur', '../styles/cuiteur.css');
em_aff_entete();
em_aff_infos();
echo '<ul>';

if (mysqli_num_rows($res) == 0){
    echo '<li>Votre fil de blablas est vide</li>';
}
else{
    em_aff_blablas($res);
}

echo '</ul>';

// libération des ressources
mysqli_free_result($res);
mysqli_close($bd);

em_aff_pied();
em_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();



?>
