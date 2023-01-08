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
if(isset($_GET['id'])) $_GET['id'] = decrypteSigneURL($_GET['id']);

// si utilisateur déjà authentifié, on le redirige vers la page cuiteur_1.php
if (!em_est_authentifie()){
    header('Location: ../index.php');
    exit();
}

agg_verif_parametre();
$bd = em_bd_connect();

// traitement si soumission du formulaire d'abonnement/desabonnement
if (isset($_POST['btnValider'])) agg_traitement_abonnement_desabonnement($bd);

/*------------------------- Etape 2 --------------------------------------------
- recupération des données dans la base données
------------------------------------------------------------------------------*/

$infos = agg_get_donnee_reduite_utilisateur($bd, $_SESSION['usID']);
$infosUtilisateurDemandes = agg_get_donnee_reduite_utilisateur($bd, $_GET['id']); 

// requête pour savoir si l'on est abonné au l'utilisateur affiché
$sql = "SELECT eaIDUser,eaIDAbonne
       FROM estabonne
       WHERE eaIDUser={$_SESSION['usID']} AND eaIDabonne={$_GET['id']}";

$res = em_bd_send_request($bd, $sql);
$estabonne = mysqli_num_rows($res)==0 ? false : true; 

/*------------------------- Etape 3 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

em_aff_debut('Cuiteur | Abonnements', '../styles/cuiteur.css');
em_aff_entete(($_SESSION['usID'] === $_GET['id'] ? "Vos abonnements" : "Les abonnements de {$infosUtilisateurDemandes[1]}"));

em_aff_infos(true, $infos);

echo '<form action="./abonnements.php?id=', crypteSigneURL($_GET['id']),'" method="POST">',
    '<ul>',
        '<li class="pasDeFond">',
        agg_aff_donnees_reduite_utilisateur($_GET['id'], $infosUtilisateurDemandes, $estabonne, true),
        '</li>',
        aggl_aff_abonnements($bd);

// libération des ressources
mysqli_free_result($res);
mysqli_close($bd);

em_aff_pied();
em_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();

// ----------  Fonctions locales du script ----------- //

/**
 *  Affichage du formulaire des abonnés de l'utilisateur
 * 
 * @param mysqli    $bd          Objet permettant d'accéder à la BDD 
 * @global array    $_POST
 */
function aggl_aff_abonnements(mysqli $bd): void {

    $sql = "SELECT usID, usPseudo, usNom, 
            estabonne.eaIDUser,
            estabonne.eaIDAbonne
            FROM ((estabonne AS origin
            INNER JOIN users ON origin.eaIDAbonne=usID)
            LEFT OUTER JOIN estabonne ON estabonne.eaIDAbonne=usID AND estabonne.eaIDUser={$_SESSION['usID']})
            WHERE origin.eaIDUser={$_GET['id']}
            ORDER BY usPseudo";
    
    $res = em_bd_send_request($bd, $sql);
    
    agg_aff_donnees_reduite_utlisateurs($bd ,$res);

    echo '</ul>',
    '<table>', (mysqli_num_rows($res)!=0 ?
    agg_aff_ligne_input_bouton(array('type' => 'submit', 'name' => 'btnValider', 'value' => 'Valider'), 1) : '<tr><td>Vous n\'avez pas d\'abonnements...</td></tr>'),
    '</table>',
    '</form>';

    mysqli_free_result($res);
}
?>