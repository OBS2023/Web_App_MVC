<?php
namespace Gsbfrais\Controllers;

use Gsbfrais\models\{FicheFraisManager, FraisForfaitManager, FraisHorsForfaitManager};

class FicheFraisController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function voirFicheFrais()
    {        
        $ficheFraisManager = new FicheFraisManager();
        $fraisForfaitManager = new FraisForfaitManager();
        $fraisHorsForfaitManager = new FraisHorsForfaitManager();

        $lesMois = $ficheFraisManager->getLesMoisDisponibles($this->idUtilAuthentifie);
        /* pré-sélection du mois le plus récent */
        $moisSelectionne = $lesMois[0]->mois;

        $lesFraisForfait = [];
        $lesFraisHorsForfait = [];
        $laFicheFrais = [];
        $statutFicheFrais = '';
        $montantTotalValide = 0;

        // Un mois a été sélectionné par l'utilisateur (le formulaire est posté)
        if (count($_POST) > 0) {
            $moisSelectionne = filter_input(INPUT_POST, 'lstMois', FILTER_VALIDATE_INT);
            if (empty($moisSelectionne) == true) {
                http_response_code(400);
                throw new \Exception("erreur d'accès à voirFicheFrais (FicheFraisController)");
            }
            $lesFraisForfait = $fraisForfaitManager->getLesFraisForfait($this->idUtilAuthentifie, $moisSelectionne);
            $lesFraisHorsForfait = $fraisHorsForfaitManager->getLesFraisHorsForfait($this->idUtilAuthentifie, $moisSelectionne);
            $laFicheFrais = $ficheFraisManager->getFicheFrais($this->idUtilAuthentifie, $moisSelectionne);

            $statut = $laFicheFrais->code_statut;
            
            if ($statut == 'CL')
            {
                $statutFicheFrais = 'Clôturée';
            }
            elseif ($statut == 'CR')
            {
                $statutFicheFrais = 'Saisie en cours';
            }
            elseif ($statut == 'RB')
            {
                $statutFicheFrais = 'Validée';
            }
            elseif ($statut == 'VA')
            {
                $statutFicheFrais = 'remboursée';
            }
            
            foreach ($lesFraisHorsForfait as $unFraisHorsForfait): 
                $montantTotalValide = $montantTotalValide + $unFraisHorsForfait->montant;
            endforeach;
        }

        $this->render('fichefrais/consulter', [
            'title' => 'Mes fiches de frais',
            'periode' => date("m/Y"),
            'moisSelectionne' => $moisSelectionne,
            'lesMois' => $lesMois,
            'laFicheFrais' => $laFicheFrais,
            'lesFraisForfait' => $lesFraisForfait,
            'lesFraisHorsForfait' => $lesFraisHorsForfait,
            'statutFicheFrais' => $statutFicheFrais,
            'montantTotalValide' => $montantTotalValide,
        ]);
    }

    public function cloturerFichesFrais()
    {
        if ($this->profilUtilAuthentifie != 'comptable') {
            http_response_code(403);
            throw new \Exception('Fonctionnalité cloturerFichesFrais (FicheFraisController) non autorisée');
        }

        $moisAcloturer = donneMoisPrecedent($this->mois);

        $infoMessage = '';
        if (count($_POST) > 0) {
            $ficheFraisManager = new FicheFraisManager();
            $lesFichesFrais = $ficheFraisManager->getLesFichesFraisACloturer($moisAcloturer);
            $nbFichesCloturees = 0;
            foreach($lesFichesFrais as $fiche) {
                $ficheFraisManager->clotureFicheFrais($fiche->id_visiteur, $fiche->mois);
                $nbFichesCloturees++;
            }
            $infoMessage = $nbFichesCloturees . " fiches de frais ont été clôturées";
            
        }
        
        $periode = substr($moisAcloturer, 4, 2) . "-" . substr($moisAcloturer, 0, 4);
        $this->render('fichefrais/cloturer', [
            'title' => 'Mes fiches de frais',
            'periode' => $periode,
            'infoMessage' => $infoMessage
        ]);
    }

    public function validerVoirLesFichesFrais()
    {
        if ($this->profilUtilAuthentifie != 'comptable') {
            http_response_code(403);
            throw new \Exception('Fonctionnalité validerVoirLesFichesFrais (FicheFraisController) non autorisée');
        }

        $ficheFraisManager = new FicheFraisManager();
        $lesMois = $ficheFraisManager->getLesMoisPourValidationFichesFrais();
        $lesFichesFrais = [];
        $moisSelectionne = '';
        
        if (count($_POST) > 0){ 
            // On arrive de la vue ficheFrais/liste-pour-validation, après avoir sélectionné le mois 
            $moisSelectionne = filter_input(INPUT_POST, 'lstMois', FILTER_VALIDATE_INT);
        } elseif (count($_GET) > 0){ 
            // On arrive de la vue ficheFrais/valider après avoir validé une fiche ou cliqué sur le lien de retour
            $moisSelectionne = filter_input(INPUT_GET, 'mois', FILTER_VALIDATE_INT);
        }
        if ($moisSelectionne != '') {
            if (empty($moisSelectionne) == true) {
                http_response_code(400);
                throw new \Exception("erreur d'accès à validerVoirLesFichesFrais (FicheFraisController)");
            } 
            $lesFichesFrais = $ficheFraisManager->getLesFichesFraisAValider($moisSelectionne);
        }
        
        
        $this->render('fichefrais/liste-pour-validation', [
            'title' => 'Validation des fiches de frais',
            'errorMessage' => '',
            'moisSelectionne' => $moisSelectionne,
            'lesMois' => $lesMois,
            'lesFichesFrais' => $lesFichesFrais,
        ]); 
    }

    public function validerFicheFrais(int $idVisiteur, int $mois)
    {
        if ($this->profilUtilAuthentifie != 'comptable') {
            http_response_code(403);
            throw new \Exception('Fonctionnalité cloturerFichesFrais (FicheFraisController) non autorisée');
        }
        
        $ficheFraisManager = new FicheFraisManager();
        $fraisForfaitManager = new FraisForfaitManager();
        $fraisHorsForfaitManager = new FraisHorsForfaitManager();
        
        $laFiche = $ficheFraisManager->getFicheFrais($idVisiteur, $mois);
        $lesFraisForfait = $fraisForfaitManager->getLesFraisForfait($idVisiteur, $mois);
        $lesFraisHorsForfait = $fraisHorsForfaitManager->getLesFraisHorsForfait($idVisiteur, $mois);

        $nbJustificatifs = '';
        $montant = '';
        $periode = substr($mois, 4, 2) . "/" . substr($mois, 0, 4);

        if (count($_POST) > 0) {
            if (isset($_POST['btnRetourListe'])) {
                header("Location: " . ROOT_URL . 'validerVoirLesFichesFrais&mois='.$mois);
            }
            /* Gestion des erreurs */
            $errorMessage = '';
            $montant = filter_input(INPUT_POST, 'montantValide', FILTER_VALIDATE_FLOAT);
            $nbJustificatifs = filter_input(INPUT_POST, 'nbJustificatifs', FILTER_VALIDATE_INT);
            if (empty($montant) == true) {
                $errorMessage .= "Le montant doit être renseigné et numérique<br>";
            }
            if ($nbJustificatifs === false or $nbJustificatifs < 0) {
                $errorMessage .= "Le nombre de justificatifs doit être un entier positif ou nul";
            }
            if (empty($errorMessage) == true)
            {
                $ficheFraisManager->validerFicheFrais($idVisiteur, $mois, $montant, $nbJustificatifs);
                header("Location: " . ROOT_URL . 'validerVoirLesFichesFrais&mois='.$mois);
                return;
            }
        }

        $this->render('fichefrais/valider', [
            'title' => 'Validation fiche de frais',
            'errorMessage' => '',
            'idVisiteur' => $idVisiteur,
            'mois' => $mois,
            'periode' => $periode,
            'laFiche' => $laFiche,
            'lesFraisForfait' => $lesFraisForfait,
            'lesFraisHorsForfait' => $lesFraisHorsForfait,
            'nbJustificatifs' => '',
            'montantValide' => '',
            'nbJustificatifs' => $nbJustificatifs,
            'montantValide' => $montant
        ]); 
    }

}
