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

// si utilisateur déjà authentifié, on le redirige vers la page cuiteur_1.php
if (!em_est_authentifie()){
    header('Location: ../index.php');
    exit();
}

$bd = em_bd_connect();

// traitement si soumission du formulaire de recherche
$er = isset($_POST['btnRechercher']) ? aggl_traitement_recherche($bd) : array();

// traitement si soumission du formulaire d'abonnement/desabonnement
if (isset($_POST['btnValider'])) agg_traitement_abonnement_desabonnement($bd);

/*------------------------- Etape 2 --------------------------------------------
- recupération des données dans la base données
------------------------------------------------------------------------------*/

$infos = agg_get_donnee_reduite_utilisateur($bd, $_SESSION['usID']);

/*------------------------- Etape 3 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

em_aff_debut('Cuiteur | Recherche', '../styles/cuiteur.css');
em_aff_entete('Rechercher des utlisateurs');

em_aff_infos(true, $infos);

aggl_aff_formulaires($er, $bd);

// libération des ressources
mysqli_close($bd);

em_aff_pied();
em_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();

// ----------  Fonctions locales du script ----------- //

/**
 *  Affichage des formulaires de recherche et le resulat de la recherche
 *
 *         Etape 1. Effectuer la recherche demander par l'utlisateur dans la BDD
 *         Etape 2. Affichage du resultat de la recherche
 * 
 * @param array     $err         Un tableau contenant des erreurs trouvés lors de la recherche
 * @param mysqli    $bd          Objet permettant d'accéder à la BDD 
 * @global array    $_POST
 */
function aggl_aff_formulaires(array $err , mysqli $bd): void {

    echo    '<form action="./recherche.php" method="POST">',
            '<table class="alignGauche">',
                '<tr>',
                    '<td class="alignGauche"><input type="text" name="champRecherche" value="', (isset($_POST['champRecherche']) ? $_POST['champRecherche'] : ''), '"></td>',
                    '<td><input type="submit" name="btnRechercher" value="Rechercher"></td>',
                '</tr>',
            '</table>',
        '</form>';

    // si l'utilisateur a pas effectué de recherche
    if (!isset($_POST['champRecherche'])) {
        return;
    }

    // s'il y a des erreurs ==> on les affiches   
    if (isset($_POST['champRecherche']) && count($err) > 0) {
        agg_aff_message_validation_formulaire($err);
        return;
    }

    /*------------------------- Etape 1 --------------------------------------------
    - Effectuer la recherche demander par l'utlisateur dans la BDD
    ------------------------------------------------------------------------------*/
    
    $recherche = em_bd_proteger_entree($bd, $_POST['champRecherche']);

    $sql = "SELECT usID, usPseudo, usNom, eaIDAbonne, eaIDUser
            FROM users LEFT OUTER JOIN estabonne ON eaIDabonne=usID AND eaIDuser={$_SESSION['usID']}
            WHERE usPseudo LIKE '%$recherche%' OR usNom LIKE '%$recherche%'
            ORDER BY usPseudo";
    
    $res = em_bd_send_request($bd, $sql);
    
    /*------------------------- Etape 2 --------------------------------------------
    - Affichage du resultat de la recherche
    ------------------------------------------------------------------------------*/
    
    echo '<p class="titreFormulaire">Résultats de la recherche</p>';
    
    if (mysqli_num_rows($res)==0) {
        echo '<p> Pas de résultats trouvés ...</p>';
        return;
    }
    
    echo '<form action="./recherche.php" method="POST">',
    '<ul>',
    agg_aff_donnees_reduite_utlisateurs($bd ,$res),
    '</ul><table>',
    agg_aff_ligne_input_bouton(array('type' => 'submit', 'name' => 'btnValider', 'value' => 'Valider')),
        '</table>',
    '</form>';

    mysqli_free_result($res);
}

/**
 *  Traitement de la recherche
 *
 *
 * @param mysqli    $bd        Objet permettant l'accès à la base de données
 * @global array    $_POST
 * 
 * @return array               Un tableau contenant des erreurs potentielle
 */
function aggl_traitement_recherche(mysqli $bd): array {
    /*------------------------- Etape 1 --------------------------------------------
    - vérification de la validité des données
    ------------------------------------------------------------------------------*/

    if( !em_parametres_controle('post', array('champRecherche', 'btnRechercher'))) {
        em_session_exit();
    }

    foreach($_POST as &$val){
        $val = trim($val);
    }

    $erreurs = array();

    // vérification du champ de recherche
    $noTags = strip_tags($_POST['champRecherche']);
    if ($noTags != $_POST['champRecherche']){
        $erreurs[] = 'Le champ de recherche ne peuvent pas contenir de code HTML.';
    }

    if (mb_strlen($_POST['champRecherche'], 'UTF-8') <=0) {
        $erreurs[] = 'Le champ de recherche ne doit pas être vide.';
    }

    return $erreurs;
}
?>