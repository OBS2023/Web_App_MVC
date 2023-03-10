﻿<?php
/**
 * Vérifie si un visiteur est authentifié
 * 
 * @return bool true si un visiteur est authentifié, false sinon
 */
function estVisiteurConnecte():bool
{
    if (isset($_SESSION['idUtil'])) {
        return true;
    } else {
        return false;
    }
}


/**
 * Vérifie si une date est dépassée depuis plus de un an
 *
 * @param $laDate La date à tester
 * @return bool true si la date passée en paramètre remonte à plus d'un an, false sinon
 */
function estDateDepassee(string $laDate):bool
{
    $dateActuelle = date("d/m/Y");
    @list($jour, $mois, $annee) = explode('/', $dateActuelle);
    $annee--;
    $dateMoinsUnAn = $annee . $mois . $jour;

    @list($aa, $mm, $jj) = explode('-', $laDate);
    $dateFournie = $aa . $mm . $jj;

    if ($dateFournie < $dateMoinsUnAn) {
        return true;
    } else {
        return false;
    }
}


/**
 * Vérifie si une chaine correspond à une date au format jj/mm/aaaa
 *
 * @param string $laDate La date à tester
 * @return bool true si la date est correcte, false sinon
 */
function estDateValide(string $laDate):bool
{
    $tabDate = explode('-', $laDate);
    $dateOK = true;
    if (count($tabDate) != 3) {
        $dateOK = false;
    } elseif (!checkdate($tabDate[1], $tabDate[2], $tabDate[0])) {
        $dateOK = false;
    }
    return $dateOK;
}

function donneMoisPrecedent($mois)
{
    // Détermine mois et année précédent le mois en-cours
    $mm = substr($mois, 4, 2);
    $aa = substr($mois, 0, 4);
    $mm--;
    if ($mm == 0) {
        $aa--;
        $mm = 1;
    }
    return (int)(sprintf("%04d%02d", $aa, $mm));
}

/**
 * Formatte un tableau d'erreurs sous forme d'une chaine de caractères
 * 
 * @param array $lesErreurs tableau contenant les différents message d'erreur
 * @return string chaine de caractère contenant les messages d'erreurs (un message par ligne)
 */
function formatteMessagesErreur($lesErreurs):string
{
    $str = "";
    foreach ($lesErreurs as $erreur) {
        $str .=  '-' . $erreur . '<br>';
    }
    return $str;
}