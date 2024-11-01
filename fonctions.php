<?php
/*
 * Copyright 2020-2024 Anael MOBILIA
 *
 * This file is part of pdfWebExplorer
 *
 * pdfWebExplorer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * pdfWebExplorer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with pdfWebExplorer If not, see <http://www.gnu.org/licenses/>
 */

/**
 * Fonctions utilisées par l'application
 */

/**
 * Génère une miniature d'un fichier PDF
 * @param string $source PATH du fichier source
 * @param string $destination PATH de l'image destination
 * @throws ImagickException
 */
function genPdfThumbnail(string $source, string $destination)
{
    echo $source . ' -> ' . $destination . PHP_EOL;
    $im = new Imagick($source . '[0]'); // 0-first page, 1-second page
    $im->setImageColorspace(255); // prevent image colors from inverting
    $im->setimageformat('png');
    $im->thumbnailimage(150, 150); // width and height
    $im->writeimage($destination);
    $im->clear();
    $im->destroy();
}

/**
 * Liste des fichiers PDF contenus dans un répertoire
 * @param string $path PATH à analyser (sans récursivité)
 * @param bool $includeArchive Inclure les fichiers archivés ?
 * @return ArrayObject des fichiers (répondant au critère de filtre éventuel)
 */
function getPdfFiles(string $path, bool $includeArchive = false): ArrayObject
{
    $monRetour = new ArrayObject();

    // Filtre éventuel
    $monFiltre = '';
    if (
        isset($_REQUEST['cat'])
        && is_numeric($_REQUEST['cat'])
        && $_REQUEST['cat'] !== CATEGORIES_TOUTES
        && !isset($_FILES[FIELD_UPLOAD])
    ) {
        $monFiltre = (int)$_REQUEST['cat'] . SEPARATEUR_CATEGORIE;
    }

    $listeBrute = scandir($path);
    foreach ($listeBrute as $unItem) {
        // Si ce n'est pas un dossier...
        if (!is_dir($path . $unItem) && substr($unItem, -4) === '.pdf') {
            // Vérification du filtre éventuel
            if (
                $monFiltre === ''    // Pas de filtre
                || strpos($unItem, $monFiltre) === 0 // Filtre OK
            ) {
                // Exclure les fichiers archivés ?
                if (
                    strpos($unItem, CATEGORIE_ARCHIVES) === 0
                    && !$includeArchive
                ) {
                    continue;
                }
                // On l'ajoute au retour
                $monRetour->append($unItem);
            }
        }
    }
    return $monRetour;
}

/**
 * Génère le code HTML pour afficher les fichiers, miniatures, liens...
 * @param ?ArrayObject $forceFile Forcer des fichiers spécifiquement
 * @return ArrayObject code HTML
 */
function getHtmlForFiles(ArrayObject $forceFile = null): ArrayObject
{
    $monRetour = new ArrayObject();
    if(is_null($forceFile)){
        $forceFile = getPdfFiles(PATH_DATAS);
    }
    foreach ($forceFile as $unFichier) {
        $monHtml = '<div class="col ps-0 pe-2 mt-0 mb-2">';
        $nomMiniature = $unFichier . '.png';
        $nomAffiche = str_replace('.pdf', '', $unFichier);
        // Suppression de la catégorie si définie
        if (!empty(CATEGORIES)) {
            $nomAffiche = preg_replace('#^[0-9]' . preg_quote(SEPARATEUR_CATEGORIE, '#') . '(.*)$#', '$1', $nomAffiche, 1);
        }
        if (file_exists(PATH_THUMBS . $nomMiniature)) {
            $maMiniature = URL_THUMBS . $nomMiniature;
        } else {
            // Miniature absente → image par défaut
            $maMiniature = DEFAULT_THUMBS;
        }
        $monHtml .= '<a href="' . URL_DATAS . $unFichier . '" target="blank" class="text-break link-underline link-underline-opacity-0 link-underline-opacity-50-hover">';
        $monHtml .= '<img src="' . $maMiniature . '" width="125" height="125" alt="' . $nomAffiche . '" loading="lazy"/><br />';
        $monHtml .= $nomAffiche . '</a><br />';
        $monHtml .= '<a class="icon-link link-underline link-underline-opacity-0 link-underline-opacity-25-hover" onclick="ajaxCall(\'' . ACTION_RENOMMER . '\', \'' . $unFichier . '\', this.parentNode)" data-bs-toggle="tooltip" data-bs-title="Renommer"> 🖍 </a>';
        $monHtml .= '<a class="icon-link link-underline link-underline-opacity-0 link-underline-opacity-25-hover" onclick="ajaxCall(\'' . ACTION_ARCHIVER . '\', \'' . $unFichier . '\', this.parentNode)" data-bs-toggle="tooltip" data-bs-title="Archiver"> 🗃️️ </a>';
        $monHtml .= '</div>';

        $monRetour->append($monHtml);
    }

    return $monRetour;
}

/**
 * Traiter et enregistrer de nouveaux fichiers
 * @param string $logError log d'erreurs
 * @param string $logSuccess log de succès
 */
function saveUploadedFiles(string &$logError, string &$logSuccess)
{
    $mesFichiers = [];
    // Cas envoi simple => on convertit en array comme si envoi multiple
    if (!is_array($_FILES[FIELD_UPLOAD]['name'])) {
        $mesFichiers['name'] = [$_FILES[FIELD_UPLOAD]['name']];
        $mesFichiers['tmp_name'] = [$_FILES[FIELD_UPLOAD]['tmp_name']];
    } else {
        // Envoi multiple, déjà bien formaté
        $mesFichiers = $_FILES[FIELD_UPLOAD];
    }
    $nbFichiers = count($mesFichiers['name']);
    $nbUploadOk = 0;

    // Pour chaque fichier
    for ($i = 0; $i < $nbFichiers; $i++) {
        // Nettoyage du nom du fichier
        $nom = str_replace(['..', '/', '\\', '<', '>'], '', $mesFichiers['name'][$i]);
        // Passage en minuscule de l'extension
        $nom = pathinfo($nom, PATHINFO_FILENAME) . '.' . strtolower(pathinfo($nom, PATHINFO_EXTENSION));
        // Gestion de la catégorie
        if (isset($_REQUEST['cat'])) {
            $nom = (int)$_REQUEST['cat'] . SEPARATEUR_CATEGORIE . $nom;
        }

        // Vérification du type du fichier
        if (
            mime_content_type($mesFichiers['tmp_name'][$i]) === MIME_TYPE
            && !file_exists(PATH_DATAS . $nom)
        ) {
            // Déplacement du fichier
            if (move_uploaded_file($mesFichiers['tmp_name'][$i], PATH_DATAS . $nom)) {
                $nbUploadOk++;
            } else {
                $logError .= 'Erreur au déplacement du fichier ' . $mesFichiers['tmp_name'][$i] . ' vers ' . PATH_DATAS . $nom . ' !<br />';
            }
        } else {
            $logError .= 'Le fichier ' . $nom . ' n\'est pas de type ' . MIME_TYPE . ' !<br />';
        }
    }
    if ($nbUploadOk > 0) {
        $logSuccess .= 'Envoi réussi de ' . $nbUploadOk . ' fichier' . ($nbUploadOk > 1 ? 's' : '') . '<br />';
    }
}