<?php

ob_start(); //démarre la bufferisation

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

em_aff_debut('Cuiteur | Blablas', '../styles/cuiteur.css');

// Les valeurs contenues dans $_POST et $_GET sont de type 'string'.
// Donc, si cette page est appelée avec l'URL blabla_4.php?id=4, la valeur de $_GET['id'] sera de type string
// => is_int($_GET['id']) renverra false
// => Il faut utiliser is_numeric() pour déterminer si la valeur de $_GET['id'] est une chaine numérique
// C'est ce qui est fait dans la fonction em_est_entier()
if (count($_GET) != 1 || ! isset($_GET['id']) || ! em_est_entier(($_GET['id'])) || $_GET['id'] <= 0){
    em_aff_entete('Erreur');
    em_aff_infos();
    echo    '<ul>',
                '<li>Cette page doit être appelée avec, en paramètre dans l\'URL, un entier nommé "id" supérieur à 0, identifiant un utilisateur.</li>',
            '</ul>';
    em_aff_pied();
    em_aff_fin();
    exit;    //==> FIN DU SCRIPT
}

$bd = em_bd_connect();

$id = (int)$_GET['id']; //on n'est jamais trop prudent

// 1ère jointure externe pour que la requête renvoie un enregistrement quand un utilisateur enregistré n'a pas encore publié de blabla
// Notez l'utilisation de guillemets doubles
$sql = "SELECT  auteur.usID AS autID, auteur.usPseudo AS autPseudo, auteur.usNom AS autNom, auteur.usAvecPhoto AS autPhoto, 
                blTexte, blDate, blHeure,
                origin.usID AS oriID, origin.usPseudo AS oriPseudo, origin.usNom AS oriNom, origin.usAvecPhoto AS oriPhoto
        FROM (users AS auteur
        LEFT OUTER JOIN blablas ON blIDAuteur = usID)
        LEFT OUTER JOIN users AS origin ON origin.usID = blIDAutOrig
        WHERE auteur.usID = $id
        ORDER BY blID DESC";

$res = em_bd_send_request($bd, $sql);

if (mysqli_num_rows($res) == 0){
    // libération des ressources
    mysqli_free_result($res);
    mysqli_close($bd);
    
    em_aff_entete('Erreur');
    em_aff_infos();
    echo    '<ul>',
                '<li>L\'utilisateur ', $id, ' n\'existe pas</li>',
            '</ul>';
    em_aff_pied();
    em_aff_fin();
    exit;   //==> FIN DU SCRIPT
}

$t = mysqli_fetch_assoc($res);

em_aff_entete(em_html_proteger_sortie("Les blablas de {$t['autPseudo']}"));
em_aff_infos();
echo '<ul>';

if ($t['blTexte'] == null){
    echo '<li>', em_html_proteger_sortie($t['autPseudo']), ' n\'a pas publié de message</li>';
}
else{
    // déplace le pointeur interne de résultat associé au jeu de résultat représenté par $res, en le faisant pointer sur la ligne 0
    mysqli_data_seek($res , 0); //pour relire la ligne 0
    
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
