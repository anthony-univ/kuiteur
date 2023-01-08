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

/*------------------------- Etape 3 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

em_aff_debut('Cuiteur | Mentions', '../styles/cuiteur.css');
em_aff_entete(($_SESSION['usID'] === $_GET['id'] ? "Vos mentions" : "Les mentions de {$infosUtilisateurRechercher[1]}"));

em_aff_infos(true, $infosUtilisateurAside);

echo '<ul> <li class="pasDeFond">',
agg_aff_donnees_reduite_utilisateur($_GET['id'], $infosUtilisateurRechercher),
    '</li>';

agg_aff_mentions($bd);

echo '</ul>';

// libération des ressources
mysqli_close($bd);

em_aff_pied();
em_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();

// ----------  Fonctions locales du script ----------- //

/**
 *  Affichage des blablas contenant l'utilisateur mentionné
 *
 * @param mysqli     $bd                              objet permettant d'acceder à la bdd
 * @global array    $_GET
 */
function agg_aff_mentions(mysqli $bd): void {

     // sous-requête qui recupère les blId des blablas conentent la mentions puis la requête principale qui recupere les infos de ces blablas
     $sql = "SELECT DISTINCT auteur.usID AS autID, auteur.usPseudo AS autPseudo, auteur.usNom AS autNom, auteur.usAvecPhoto AS autPhoto, 
            blTexte, blDate, blHeure,
            origin.usID AS oriID, origin.usPseudo AS oriPseudo, origin.usNom AS oriNom, origin.usAvecPhoto AS oriPhoto,
            GROUP_CONCAT(DISTINCT taID) AS TAGS, GROUP_CONCAT(DISTINCT meIDUser) AS IDMENTIONS,
            GROUP_CONCAT(DISTINCT users2.usPseudo) AS PSEUDOMENTIONS, blID 
            FROM (((((users AS auteur INNER JOIN blablas ON blIDAuteur = usID) 
            LEFT OUTER JOIN users AS origin ON origin.usID = blIDAutOrig) 
            LEFT OUTER JOIN mentions ON blID = meIDBlabla)
            LEFT OUTER JOIN tags ON blID = taIDBlabla) 
            LEFT OUTER JOIN users AS users2 ON meIDUser = users2.usID) 

            WHERE blID IN (SELECT blID
                            FROM blablas LEFT OUTER JOIN mentions ON blID=meIDBlabla
                            WHERE meIDUser={$_GET['id']})
            GROUP BY blID ORDER BY blID DESC";

     $res = em_bd_send_request($bd, $sql);

    if (mysqli_num_rows($res) == 0){
        echo '<li>Aucun blablas contient cette mention</li>';
    }
    else{
        em_aff_blablas($bd, $res, './mentions.php', array('id' => $_GET['id']));
    }

    mysqli_free_result($res);
}
?>