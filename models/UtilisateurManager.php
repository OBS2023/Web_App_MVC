<?php

namespace Gsbfrais\models;

class UtilisateurManager extends Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Retourne les informations d'un utilisateur
     * 
     * @param string $login login de l'utilisateur pour lequel on souhaite les informations
     * 
     * @return mixed les informations du visiteur ou false si l'utilisateur n'existe pas
     */
    public function getUtilisateur(string $login): mixed
    {
        $utilisateur = false;

        $sql = "select utilisateur.id, utilisateur.nom, utilisateur.prenom, utilisateur.login, utilisateur.mot_passe, profil.nom as nom_profil 
                from utilisateur
                join profil on profil.id = id_profil
                where login=:login";
        $stmt = $this->db->prepare($sql);
        $ret = $stmt->execute(array(
            ':login' => $login,
        ));
        if ($ret == false) {
            http_response_code(500);
            throw new \Exception('Problème requête getUtilisateur (UtilisateurManager)');
        }
        $utilisateur =  $stmt->fetch();
        return $utilisateur;
    }


    public function getLesUtilisateurs()
    {
        $sql = "SELECT  utilisateur.id, utilisateur.nom, utilisateur.prenom, date_embauche, date_depart, profil.nom as nom_profil, region.nom as nom_region
        FROM utilisateur 
        join profil on profil.id = id_profil
        join region on region.id = id_region";

        $stmt = $this->db->prepare($sql);
        $ret = $stmt->execute();
        if ($ret == false) {
            http_response_code(500);
            throw new \Exception('Problème requête getLesUtilisateurs (UtilisateurtManager)', 99500);
        }
        return $stmt->fetchAll();
    }

    public function ajouter($nom,$prenom,$mot_pass,$login,$date_embauche,$date_depart,$id_profil,$id_region): Void
    {
        $sql = "insert into utilisateur (nom, prenom, mot_pass,login,date_embauche,date_depart)
                values(nom, prenom, mot_pass,login,date_embauche,date_depart)";
        $stmt = $this->db->prepare($sql);
        $ret = $stmt->execute(array(
            'nom' => $nom,
            'prenom' => $prenom,
            'mot_pass' => $mot_pass,
            'login' => $login,
            'date_embauche' => $date_embauche,
            'date_depart' => $date_depart,
            'id_profil' => $id_profil,
            'id_region' => $id_region
        ));
        if ($ret == false) {
            http_response_code(500);
            throw new \Exception('Problème requête ajouter (UtilisateurManager)', 99500);
        }
    }


}
