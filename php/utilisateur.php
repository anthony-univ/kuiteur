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

// traitement si soumission du formulaire d'abonnement/désabonnement
if (isset($_POST[$_GET['id']])) aggl_traitement_abonnement_desabonnement($bd);

/*------------------------- Etape 2 --------------------------------------------
- recupération des données dans la base données
------------------------------------------------------------------------------*/

$infosUtilisateurAside = agg_get_donnee_reduite_utilisateur($bd, $_SESSION['usID']);
$infosUtilisateurRechercher = agg_get_donnee_reduite_utilisateur($bd, $_GET['id']); // nombre de blablas, de mentions, d'abonnés, d'abonnement
$infos = agg_get_infos_user($bd, $_GET['id']); // toutes les données de la table users concernant l'utlisateur recherche

// requête pour savoir si l'on est abonné au l'utilisateur affiché
$sql = "SELECT eaIDAbonne, eaIDUser
        FROM estabonne 
        WHERE eaIDUser={$_SESSION['usID']} AND eaIDabonne={$_GET['id']}";

$estAbonne = em_bd_send_request($bd, $sql);


/*------------------------- Etape 3 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

em_aff_debut('Cuiteur | Utilisateur', '../styles/cuiteur.css');
em_aff_entete("Le profil de {$infosUtilisateurRechercher[1]}");

em_aff_infos(true, $infosUtilisateurAside);

echo '<ul><li class="pasDeFond">',
agg_aff_donnees_reduite_utilisateur($_GET['id'], $infosUtilisateurRechercher),
    '</li></ul>',

        '<form action="./utilisateur.php?id=', crypteSigneURL($_GET['id']),'" method="POST">',
            '<table>',
                aggl_aff_ligne_formulaire('Date de naissance', em_amj_clair($infos['usDateNaissance'])),
                aggl_aff_ligne_formulaire('Date d\'inscription', em_amj_clair($infos['usDateInscription'])),
                aggl_aff_ligne_formulaire('Ville de résidence', $infos['usVille']),
                aggl_aff_ligne_formulaire('Mini-Bio',   $infos['usBio']),
                aggl_aff_ligne_formulaire('Site Web', $infos['usWeb'], true);
                ($_SESSION['usID'] == $_GET['id'] ? :
                agg_aff_ligne_input_bouton(array('type' => 'submit', 'name' => $_GET['id'], 'value' => (mysqli_num_rows($estAbonne) ? 'Se désabonner' : 'S&apos;abonner'))));
        echo  '</table>',
        '</form>';

// libération des ressources
mysqli_free_result($estAbonne);
mysqli_close($bd);

em_aff_pied();
em_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();

// ----------  Fonctions locales du script ----------- //

/**
 *  TAffichage d'une ligne d'un formulaire avec deux td simple
 *
 * @param string    $champ1        valeur du premier td
 * @param string    $champ2        valeur du deuxième td
 *
 */
function aggl_aff_ligne_formulaire(string $champ1, string $champ2, ?bool $lien=false) {
    echo '<tr class="formUtilisateur">',
            "<td>$champ1 :</td>",
            '<td>', (mb_strlen($champ2)!=0 ? ($lien ? em_html_a($champ2, $champ2) : $champ2): 'Non renseigné(e)'), '</td>',
        '</tr>';
}

/**
 *  Traitement de l'abonnement ou le désabonnement à un utlisateur
 *
 *      Etape 1. Vérification de la validité des données
 *      Etape 2. Mettre à jour la bdd et redirection vers la page cuiteur.php
 *
 * @param mysqli    $bd        Objet permettant l'accès à la base de données
 * @global array    $_POST
 * @global array    $_SESSION
 *
 */
function aggl_traitement_abonnement_desabonnement(mysqli $bd): void {
    /*------------------------- Etape 1 --------------------------------------------
    - Vérification de la validité des données
    ------------------------------------------------------------------------------*/

    if( !isset($_POST[$_GET['id']])) {
        em_session_exit();
    }

    foreach($_POST as &$val){
        $val = trim($val);
    }

    /*------------------------- Etape 2 --------------------------------------------
    - Mettre à jour la bdd et redirection vers la page cuiteur.php
    ------------------------------------------------------------------------------*/
    agg_set_abonnement_desabonnement($bd);

    // redirection vers la page cuiteur.php
    header('Location: cuiteur.php'); 
    exit();
}
?>