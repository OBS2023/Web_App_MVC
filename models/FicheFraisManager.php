<?php
namespace Gsbfrais\models;

class FicheFraisManager extends Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Regarde si un visiteur possède déjà une fiche de frais pour le mois passé en argument
     * 
     * @param int $idVisiteur identifiant du visiteur médical
     * @param int $mois  mois sous la forme aaaamm
     * 
     * @return bool true si le visiteur possède une fiche, false sinon
     */
    public function estPremierFraisMois(int $idVisiteur, int $mois): bool
    {
        $estPremier = false;
        $sql = "select count(*) as nb 
                from fichefrais 
                where fichefrais.mois = :mois and fichefrais.id_visiteur = :id_visiteur";
        $stmt = $this->db->prepare($sql);
        $ret = $stmt->execute([
            ':id_visiteur' => $idVisiteur,
            ':mois' => $mois
        ]);
        if ($ret == false) {
            http_response_code(500);
            throw new \Exception('Problème requête estPremierFraisMois (FicheFraisManager)', 99500);
        }
        $ligne = $stmt->fetch();
        if ($ligne->nb == 0) {
            $estPremier = true;
        }
        return $estPremier;
    }

    /**
     * Retourne le mois (aaaamm) correspondant à la denière fiche de frais d'un visiteur
     * 
     * @param int $idVisiteur identifiant du visiteur médical
     * 
     * @return mixed le mois sous la forme aaaamm ou null si pas de fiche de frais
     */
    public function getDernierMoisSaisi(int $idVisiteur): mixed
    {
        $sql = "select max(mois) as dernierMois 
                from fichefrais 
                where fichefrais.id_visiteur = :id_visiteur";
        $stmt = $this->db->prepare($sql);
        $ret = $stmt->execute([
            ':id_visiteur' => $idVisiteur
        ]);
        if ($ret == false) {
            http_response_code(500);
            throw new \Exception('Problème requête getDernierMoisSaisi (FicheFraisManager)', 99500);
        }
        $ligne = $stmt->fetch();
        return $ligne->dernierMois;
    }

    /**
     * Retourne les mois (aaaamm) pour lesquels un visiteur a une fiche de frais
     *
     * @param int $idVisiteur identifiant du visiteur médical
     * 
     * @return array tableau d'objets contenant tous les mois pour lesquels le visiteur a établi une fiche de frais
     */
    public function getLesMoisDisponibles(int $idVisiteur): array
    {
        $sql = "select mois, CONCAT(LPAD(mois % 100, 2, 0), '/', mois div 100) as libelle
        from  fichefrais 
        where fichefrais.id_visiteur = :id_visiteur
        order by fichefrais.mois desc";
        $stmt = $this->db->prepare($sql);
        $ret = $stmt->execute([
            ':id_visiteur' => $idVisiteur
        ]);
        if ($ret == false) {
            http_response_code(500);
            throw new \Exception('Problème requête getLesMoisDisponibles (FicheFraisManager)', 99500);
        }
        return $stmt->fetchAll();
    }

    /**
     * Retourne les informations de la fiche de frais d'un visiteur pour un mois donné
     * 
     * @param int $idVisiteur identifiant du visiteur médical
     * @param int $mois mois sous la forme aaaamm
     * 
     * @return object La fiche de frais du visiteur pour la période considérée, sous la forme d'un objet
     */
    public function getFicheFrais(int $idVisiteur, int $mois): object
    {
        $sql = "select nom, prenom, ficheFrais.code_statut, ficheFrais.date_modif,  
                    (select sum(FraisForfait.quantite * TypeFraisForfait.prix_unitaire) 
                        from FraisForfait
                        join TypeFraisForfait on FraisForfait.code_typefrais = TypeFraisForfait.code 
                        where FraisForfait.id_visiteur = fichefrais.id_visiteur
                        and FraisForfait.mois = fichefrais.mois) as montantFraisForfait,
                    (select ifnull(sum(FraisHorsForfait.montant),0)
                        from FraisHorsForfait 
                        where FraisHorsForfait.id_visiteur = fichefrais.id_visiteur
                        and FraisHorsForfait.mois = fichefrais.mois) as montantFraisHorsForfait,
                    ficheFrais.nb_justificatifs, statutfichefrais.libelle as libelleStatutFiche, ficheFrais.montant_valide
                from  fichefrais 
                join statutfichefrais on ficheFrais.code_statut = statutfichefrais.code 
                join utilisateur on ficheFrais.id_visiteur = utilisateur.id
              where fichefrais.id_visiteur =:id_visiteur and fichefrais.mois = :mois";
        $stmt = $this->db->prepare($sql);
        $ret = $stmt->execute([
            ':id_visiteur' => $idVisiteur,
            ':mois' => $mois
        ]);
        if ($ret == false) {
            http_response_code(500);
            throw new \Exception('Problème requête getFicheFrais (FicheFraisManager)', 99500);
        }
        $rep = $stmt->fetch();
        return $rep;
    }

    
    /**
     * Crée une nouvelle fiche de frais et les lignes de frais au forfait pour un visiteur et un mois donnés.
     * Récupère le dernier mois en cours de traitement, met à 'CL' son statut, crée une nouvelle fiche de frais
     * avec un statut à 'CR' et crée les lignes de frais forfait de quantités nulles
     *
     * @param int $idVisiteur int identifiant du visiteur médical
     * @param int $mois int Mois sous la forme aaaamm
     * 
     * @return void
     */
    public function ajouteFicheFrais(int $idVisiteur, int $mois): void
    {
        // Clôture la fiche du mois précédent si nécessaire
        $moisPrecedent = $this->getDernierMoisSaisi($idVisiteur);
        if (is_null($moisPrecedent) == false) {
            $this->clotureFicheFrais($idVisiteur, $moisPrecedent);
        }
        
        // Crée une nouvelle fiche de frais pour le mois
        $sql = "insert into fichefrais(id_visiteur,mois,nb_justificatifs,montant_valide,date_modif,code_statut) 
                values(:id_visiteur, :mois, 0, 0, date(now()), 'CR')";
        $stmt = $this->db->prepare($sql);
        $ret = $stmt->execute(array(
            ':id_visiteur' => $idVisiteur,
            ':mois' => $mois
        ));

        if ($ret == false) {
            http_response_code(500);
            throw new \Exception('Problème requête ajouteFicheFrais (FicheFraisManager)', 99500);
        }

        // Crée les frais forfaitisés correspondants avec quantité = 0
        $fraisForfaitManager = new FraisForfaitManager();
        $typeFraisManager = new TypeFraisForfaitManager();
        $lesTypesFrais = $typeFraisManager->getLesTypesFrais();
        foreach ($lesTypesFrais as $unTypeFrais) {
            $idTypeFrais = $unTypeFrais->code;
            $fraisForfaitManager->ajouteFraisForfait($idVisiteur, $mois, $idTypeFrais, 0);
        }
    }

    public function getLesFichesFraisACloturer($mois) 
    {
        $sql = "select * from ficheFrais where mois = :mois and code_statut = 'CR'";
        $stmt = $this->db->prepare($sql);
        $ret = $stmt->execute([
            ':mois' => $mois,
        ]);
        if ($ret == false) {
            http_response_code(500);
            throw new \Exception('Problème requête clotureToutesLesFicheFrais (FicheFraisManager)', 99500);
        }
        return $stmt->fetchAll();
    }

    /**
     * Clôture d'une fiche de frais à l'état "créé"
     * @param int $idVisiteur id du visiteur concerné
     * @param int $mois mois à clôturer
     * @return void
     */
    public function clotureFicheFrais(int $idVisiteur, int $mois): void
    {
        $sql = "update ficheFrais 
                set code_statut = 'CL', date_modif = now() 
                where id_visiteur = :id_visiteur 
                and mois = :mois
                and code_statut = 'CR'";
        $stmt = $this->db->prepare($sql);
        $ret = $stmt->execute([
            ':id_visiteur' => $idVisiteur,
            ':mois' => $mois,
        ]);
        if ($ret == false) {
            http_response_code(500);
            throw new \Exception('Problème requête clotureToutesLesFicheFrais (FicheFraisManager)', 99500);
        }
    }

    /**
     * Retourne la liste des visiteurs pour lesquels une fiche de frais est en attente de validation
     * 
     * @param int $mois mois pour sélection des fiches de frais
     * 
     * @return array tableau d'objets contenant tous les visiteurs ayant une fiche de frais 
     *               pour la période en attente de validation 
     */
    public function getLesVisiteursPourValidation(int $mois):array
    {
        $sql = "select distinct id_visiteur as id, concat(nom, ' ', prenom) as nom_complet
                from fichefrais
                join utilisateur on utilisateur.id = fichefrais.id_visiteur
                where code_statut in ('CL', 'CR')
                and mois = :mois
                and id_profil = 2
                order by nom, prenom";
        $stmt = $this->db->prepare($sql);
        $ret = $stmt->execute([
            ':mois' => $mois,
        ]);
        if ($ret == false) {
            http_response_code(500);
            throw new \Exception('Problème requête getLesVisiteursPourValidation (FicheFraisManager)', 99500);
        }
        $liste = $stmt->fetchAll();
        return $liste;
    }

    /**
     * Validation d'une fiche de frais
     * 
     * @param int $idVisiteur Id du visiteur médical de la fiche de frais à modifier
     * @param int $mois mois de la fiche de frais à modifier
     * @param float $montant Montant validé par le service comptabilité
     * @param int $nbJustificatifs Nombre de justificatifs reçus par le service comptabilité
     * 
     * @return void
     */
    public function validerFicheFrais(int $idvisiteur, int $mois, float $montant, int $nbJustificatifs): void
    {
        $sql = "update fichefrais set
                 montant_valide = :montant, 
                 nb_justificatifs = :nb_justificatifs, 
                 date_modif = now(),
                 code_statut = 'VA' 
                where id_visiteur = :id_visiteur and mois = :mois";
               
        $stmt = $this->db->prepare($sql);

        $ret=$stmt->execute([
                ':id_visiteur' => $idvisiteur,
                ':mois' => $mois,
                'montant' => $montant,
                'nb_justificatifs' => $nbJustificatifs
        ]);
        if ($ret == false) {
            http_response_code(500);
            throw new \Exception('Problème requête validerFicheFrais (FicheFraisManager)', 99500);
        }
    }

    /**
     * Retourne la liste des mois antérieurs au mois courant pour lesquels 
     * il existe des fiches de frais clôturées
     * 
     * @return array tableau d'objets contenant tous les mois répondant aux critères
     */
    public function getLesMoisPourValidationFichesFrais():array
    {
        $sql = "select distinct mois, CONCAT(LPAD(mois % 100, 2, 0), '/', mois div 100) as libelle
        from  fichefrais 
        where code_statut = 'CL'
        and mois < ((CURDATE() + 0) div 100)
        order by fichefrais.mois desc";

        $stmt = $this->db->prepare($sql);
        $ret = $stmt->execute();
        if ($ret == false) {
            http_response_code(500);
            throw new \Exception('Problème requête getLesMoisPourValidation (FicheFraisManager)', 99500);
        }
        return $stmt->fetchAll();
    }

    /**
     * Retourne les caractéristiques des fiches de frais clôturées du mois passé en paramètre
     * Infos retournées pour chaque fiche de frais :
     * - nom, prénom et id du visiteur
     * - nombre de justificatifs reçus par le service comptabilité
     * - date de dernière modification de la fiche de frais
     * - montant total des frais forfaitisés
     * - montant total des frais hors forfait
     * 
     * @param int $mois Le mois pour la sélection des fiches de frais
     * 
     * @return tableau d'objets contenant les caractéristiques des fiches de frais du mois passé en paramètre
     */
    public function getLesFichesFraisAValider(int $mois):array
    {

        $sql = "select nom, prenom, id_visiteur, mois, nb_justificatifs, date_format(date_modif, '%m/%d/%Y') as date_modif,
                (
                    select sum(quantite * prix_unitaire)
                    from fraisforfait
                    join typefraisforfait on fraisforfait.code_typefrais = typefraisforfait.code
                    where fraisforfait.id_visiteur = fichefrais.id_visiteur
                    and fraisforfait.mois = fichefrais.mois 
                ) as montant_forfait,
                (
                    select sum(montant)
                    from fraishorsforfait
                    where fraishorsforfait.id_visiteur = fichefrais.id_visiteur
                    and fraishorsforfait.mois = fichefrais.mois
                ) as  montant_horsforfait
        from  fichefrais
        join utilisateur on utilisateur.id = fichefrais.id_visiteur
        where code_statut = 'CL'
        and mois = :mois
        and id_region = (select id_region
                          from utilisateur
                          where nom = :ref)
        order by fichefrais.id_visiteur desc";

        $stmt = $this->db->prepare($sql);
        $ret = $stmt->execute([
            ':mois' => $mois,
            ':ref' => $_SESSION['nomUtil']
        ]);
        if ($ret == false) {
            http_response_code(500);
            throw new \Exception('Problème requête getLesMoisPourValidation (FicheFraisManager)', 99500);
        }
        return $stmt->fetchAll();
    }
}
