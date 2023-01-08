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
if(isset($_GET['taID'])) $_GET['taID'] = decrypteSigneURL($_GET['taID']);

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

$infos = agg_get_donnee_reduite_utilisateur($bd, $_SESSION['usID']);

/*------------------------- Etape 3 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

em_aff_debut('Cuiteur | Tendances', '../styles/cuiteur.css');
em_aff_entete(isset($_GET['taID']) ? "{$_GET['taID']}": '');

em_aff_infos(true, $infos);
    
agg_aff_tendances_ou_blablas($bd);

// libération des ressources
mysqli_close($bd);

em_aff_pied();
em_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();

// ----------  Fonctions locales du script ----------- //

/**
 *  Affichage des tendances ou des blablas contenant le tag dans $_GET
 *
 * @param mysqli     $bd                              objet permettant d'acceder à la bdd
 * @global array    $_GET
 */
function agg_aff_tendances_ou_blablas(mysqli $bd): void {
    if(isset($_GET['taID'])) { // affichage des blablas contenant le tag
        
        $tag = em_bd_proteger_entree($bd, $_GET['taID']);
        
        // sous-requête qui recupère les blId des blablas contenant le tag puis la requête principale qui recupère les infos de ces blablas
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
                        FROM blablas LEFT OUTER JOIN tags ON blID=taIDBlabla
                        WHERE taID='$tag')
        GROUP BY blID ORDER BY blID DESC";
        
        $res = em_bd_send_request($bd, $sql);

        echo '<ul>';
        if (mysqli_num_rows($res) == 0){
            echo '<li>Aucun blablas avec le tag ', $tag, '</li>';
        }
        else{
            em_aff_blablas($bd, $res, './tendances.php', array('taID' => $tag));
        }

        echo '</ul>';
        mysqli_free_result($res);
        
    }else { // affichage tendances par date
        $sql = "SELECT blDate, taID, COUNT(taID) AS nbOccurences
                FROM (SELECT blDate, blID
                        FROM blablas
                        ORDER BY blDate DESC) AS BLABLAS, tags
                WHERE taIDBlabla=blID
                GROUP BY taID
                ORDER BY NbOccurences DESC";

        $res = em_bd_send_request($bd, $sql);

        //affichage des tags du jour
        agg_aff_tendances($res, 'du jour');
        mysqli_data_seek($res, 0);
        
        //affichage des tags de la semaine
        agg_aff_tendances($res, 'de la semaine');
        mysqli_data_seek($res, 0);
        
        //affichage des tags du mois
        agg_aff_tendances($res, 'du mois');
        mysqli_data_seek($res, 0);
        
        //affichage des tags de l'année
        agg_aff_tendances($res, 'de l\'ann&eacute;e');
    
        mysqli_free_result($res);
    }
}
?>