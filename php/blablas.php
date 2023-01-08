<?php
/* ------------------------------------------------------------------------------
    Architecture de la page
    - étape 1 : vérifications diverses et traitement des soumissions
    - étape 2 : recupération des données dans la base données
    - étape 3 : génération du code HTML de la page
------------------------------------------------------------------------------*/

ob_start(); //démarre la bufferisation
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_cuiteur.php';

/*------------------------- Etape 1 --------------------------------------------
- vérifications diverses et traitement des soumissions
------------------------------------------------------------------------------*/
if(isset($_GET['blablas_affiche'])) $_GET['blablas_affiche'] = decrypteSigneURL($_GET['blablas_affiche']);
if(isset($_GET['id'])) $_GET['id'] = decrypteSigneURL($_GET['id']);

// si utilisateur déjà authentifié, on le redirige vers la page cuiteur_1.php
if (!em_est_authentifie()){
    header('Location: ../index.php');
    exit();
}

agg_verif_parametre();
$bd = em_bd_connect();

/*------------------------- Etape 2 --------------------------------------------
- recupération des données dans la base données
------------------------------------------------------------------------------*/

$infosUtilisateurAside = agg_get_donnee_reduite_utilisateur($bd, $_SESSION['usID']);
$infosUtilisateurRechercher = agg_get_donnee_reduite_utilisateur($bd, $_GET['id']);

$res = agg_get_donnees_blablas($bd, $_GET['id'], false, false);

/*------------------------- Etape 3 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

em_aff_debut('Cuiteur | Blablas', '../styles/cuiteur.css');
em_aff_entete("Les blablas de {$infosUtilisateurRechercher[1]}");

em_aff_infos(true, $infosUtilisateurAside);

echo '<ul> <li class="pasDeFond">',
agg_aff_donnees_reduite_utilisateur($_GET['id'], $infosUtilisateurRechercher),
    '</li>';

if (mysqli_num_rows($res) == 0){
    echo '<li>Votre fil de blablas est vide</li>';
}
else{
    em_aff_blablas($bd, $res, './blablas.php', array('id' => $_GET['id']));
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