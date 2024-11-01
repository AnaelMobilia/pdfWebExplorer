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

require "config.php";

// Mettre à jour les miniatures
foreach (getPdfFiles(PATH_DATAS, true) as $unFichier) {
    $miniatureFichier = PATH_THUMBS . $unFichier . ".png";
    // Génération des miniatures manquantes
    if (!file_exists($miniatureFichier)) {
        try {
            genPdfThumbnail(PATH_DATAS . $unFichier, $miniatureFichier);
        } catch (ImagickException $e) {
            echo "Erreur à la génération de la miniature pour " . $unFichier . " (" . $e->getMessage() . " - " . $e->getTraceAsString() . ")".PHP_EOL;
        }
    }
}


die();