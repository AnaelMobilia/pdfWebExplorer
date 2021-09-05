<?php
/*
 * Copyright 2020-2021 Anael MOBILIA
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
 * @param $source string PATH du fichier source
 * @param $destination string PATH de l'image destination
 * @throws ImagickException
 */
function genPdfThumbnail($source, $destination)
{
    echo $source . " -> " . $destination . "\r\n";
    $im = new Imagick($source . "[0]"); // 0-first page, 1-second page
    $im->setImageColorspace(255); // prevent image colors from inverting
    $im->setimageformat("png");
    $im->thumbnailimage(150, 150); // width and height
    $im->writeimage($destination);
    $im->clear();
    $im->destroy();
}

/**
 * Liste des fichiers PDF contenus dans un répertoire
 * @param $path string PATH à analyser (sans récursivité
 * @return ArrayObject des fichiers
 */
function getPdfFiles($path)
{
    $monRetour = new ArrayObject();

    $listeBrute = scandir($path);
    foreach ($listeBrute as $unItem) {
        // Si ce n'est pas un dossier...
        if (!is_dir($path . $unItem) && substr($unItem, -4) == ".pdf") {
            // On l'ajoute au retour
            $monRetour->append($unItem);
        }
    }
    return $monRetour;
}

/**
 * Génère le code HTML pour afficher les fichiers, miniatures, liens...
 * @param boolean Faut-il cacher les miniatures
 * @return ArrayObject code HTML
 */
function getHtmlForFiles($hideThumbs)
{
    $monRetour = new ArrayObject();
    foreach (getPdfFiles(PATH_DATAS) as $unFichier) {
        $monHtml = "";
        $nomMiniature = $unFichier . ".png";
        $nomAffiche = str_replace(".pdf", "", $unFichier);
        if (file_exists(PATH_THUMBS . $nomMiniature)) {
            $maMiniature = URL_THUMBS . $nomMiniature;
        } else {
            // Miniature absente -> image par défaut
            $maMiniature = DEFAULT_THUMBS;
        }
        $monHtml .= "<a href=\"" . URL_DATAS . $unFichier . "\" target=\"blank\" class=\"text-break\">";
        if (!$hideThumbs) {
            $monHtml .= "<img src=\"" . $maMiniature . "\" width=\"100\" height=\"100\" alt=\"" . $nomAffiche . "\" loading=\"lazy\"/><br />";
        }
        $monHtml .= $nomAffiche . "</a>\r\n";

        $monRetour->append($monHtml);
    }

    return $monRetour;
}