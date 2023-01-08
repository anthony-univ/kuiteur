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

// traitement si soumission du formulaire d'abonnement/desabonnement
if (isset($_POST['btnValider'])) agg_traitement_abonnement_desabonnement($bd);

/*------------------------- Etape 2 --------------------------------------------
- recupération des données dans la base données
------------------------------------------------------------------------------*/

$infos = agg_get_donnee_reduite_utilisateur($bd, $_SESSION['usID']);

/*------------------------- Etape 3 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

em_aff_debut('Cuiteur | Suggestions', '../styles/cuiteur.css');
em_aff_entete('Suggestions');

em_aff_infos(true, $infos);

echo '<form action="./suggestions.php" method="POST">',
    '<ul>',
aggl_aff_suggestions($bd);


// libération des ressources
mysqli_close($bd);

em_aff_pied();
em_aff_fin();

// facultatif car fait automatiquement par PHP
ob_end_flush();


// ----------  Fonctions locales du script ----------- //
/**
 *  Affichage du formulaire des suggestions
 * 
 * @param mysqli    $bd          Objet permettant d'accéder à la BDD 
 * @global array    $_POST
 */
function aggl_aff_suggestions(mysqli $bd): void {

    $res = agg_get_suggestions($bd, SUGGESTIONS_AFFICHE);
    
    $nbLignes = mysqli_num_rows($res);
    
    $usIDAlreadyPresent = '';

    while($t = mysqli_fetch_assoc($res)) {
        $t = em_html_proteger_sortie($t);
        $usIDAlreadyPresent = $usIDAlreadyPresent.$t['usID'].',';
        
        // recupération des données de l'utilisateur
        $infos = agg_get_donnee_reduite_utilisateur($bd, $t['usID']);
        echo '<li>',   
        agg_aff_donnees_reduite_utilisateur($t['usID'], $infos, $t['eaIDUser']!=null, true),
            '</li>';   
    }

    $usIDAlreadyPresent = substr($usIDAlreadyPresent, 0, -1);
    
    mysqli_free_result($res);
    
    if($nbLignes < SUGGESTIONS_AFFICHE) {
        $res = agg_get_suggestions_complement($bd, SUGGESTIONS_COMPLEMENT, $usIDAlreadyPresent);
        
        while($t = mysqli_fetch_assoc($res)) {
            if ($nbLignes==SUGGESTIONS_AFFICHE) break;
            
            $t  = em_html_proteger_sortie($t);
            
            // recupération des données de l'utilisateur
            $infos = agg_get_donnee_reduite_utilisateur($bd, $t['usID']);
            
            echo '<li>',   
            agg_aff_donnees_reduite_utilisateur($t['usID'], $infos, $t['eaIDUser']!=NULL, true),
                '</li>';   
            
            $nbLignes++;
        }
        
        echo '</ul>', 
        '<table>',($nbLignes!=0 ?
        agg_aff_ligne_input_bouton(array('type' => 'submit', 'name' => 'btnValider', 'value' => 'Valider'), 1) : '<tr><td>Plus de suggestions...</td></tr>'),
        '</table>',
        '</form>';
        mysqli_free_result($res);   
    }  
}
?>