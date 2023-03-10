<?php
namespace Gsbfrais\Controllers;

use Gsbfrais\models\{FicheFraisManager, FraisForfaitManager};

class FraisForfaitController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function saisirFraisForfait():void
    {
        if ($this->profilUtilAuthentifie != 'visiteur médical') {
            http_response_code(403);
            throw new \Exception('Fonctionnalité saisirFraisForfait (FraisForfaitController) non autorisée');
        }
        $ficheFraisManager = new FicheFraisManager();
        $fraisForfaitManager = new FraisForfaitManager();

        // Création de la fiche de frais du mois si elle n'existe pas encore
        if ($ficheFraisManager->estPremierFraisMois($this->idUtilAuthentifie , $this->mois)) {
            $ficheFraisManager->ajouteFicheFrais($this->idUtilAuthentifie , $this->mois);
        }

        $errorMessage = '';
        $infoMessage = '';

        if (count($_POST) > 0) {
            $lesFrais = $_POST['lesFrais'];
            $this->errors = [];
    
            $errorMessage = $this->verifierQteFraisForfait($lesFrais);
    
            // Mise à jour de la base de données si aucune erreur
            if (empty($errorMessage) == true) {
                $fraisForfaitManager = new FraisForfaitManager();
                foreach ($lesFrais as $codeTypeFrais => $quantite) :
                    $fraisForfaitManager->modifieFraisForfait($this->idUtilAuthentifie , $this->mois, $codeTypeFrais, $quantite);
                endforeach;
                $infoMessage = 'Votre saisie est enregistrée';
            }
        }

        $lesFraisForfait = $fraisForfaitManager->getLesFraisForfait($this->idUtilAuthentifie , $this->mois);

        $this->render('fraisForfait/ajouter', [
            'title' => 'Saisie frais forfaitisés',
            'periode' => date("m/Y"),
            'lesFraisForfait' => $lesFraisForfait,
            'errorMessage' => $errorMessage,
            'infoMessage' => $infoMessage
        ]);
    }

    private function verifierQteFraisForfait(array $lesFrais):string
    {
        $error='';
        foreach ($lesFrais as $code=>$quantite) :
            if (ctype_digit($quantite) == false) {
                $error = "Les quantités des frais doivent être numériques ...";
                break;
            }
            if($code=="NUI" && $quantite > date("t")){
                $error = "Le nombre de nuitées est supérieur au nombre de jour du mois";
            }
        endforeach;
        
        return $error;
    }
}
