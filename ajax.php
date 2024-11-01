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

// Vérification de la méthode demandée et de la qualité des paramètres
if (
    !isset($_GET['action'], $_GET['filename'])
    || !in_array($_GET['action'], [ACTION_ARCHIVER, ACTION_RENOMMER], true)
    || strpos($_GET['filename'], '..') !== false
    || !(substr($_GET['filename'], -4) === '.pdf')
    || !file_exists(PATH_DATAS . $_GET['filename'])
    || ($_GET['action'] === ACTION_RENOMMER
        && (
            empty($_GET['newName'])
            || strpos($_GET['newName'], '..') !== false
            || !(substr($_GET['newName'], -4) === '.pdf')
            || $_GET['newName'] === $_GET['filename']
        )
    )
) {
    header('HTTP/2 400 Bad Request');
    die();
}

if ($_GET['action'] === ACTION_ARCHIVER) {
    // Calcul du nouveau nom
    [, $name] = explode(SEPARATEUR_CATEGORIE, $_GET['filename'], 2);
    $newName = CATEGORIE_ARCHIVES . SEPARATEUR_CATEGORIE . $name;
    // Renommage du fichier s'il n'existe pas déjà
    if (!file_exists(PATH_DATAS . $newName)) {
        rename(PATH_DATAS . $_GET['filename'], PATH_DATAS . $newName);
        header('HTTP/2 200 OK');
        die();
    }
    header('HTTP/2 403 Forbidden');
    die();
} elseif ($_GET['action'] === ACTION_RENOMMER) {
    // Calcul du nouveau nom
    //[$cat,] = explode(SEPARATEUR_CATEGORIE, $_GET['filename'], 2);
    //$newName = $cat . SEPARATEUR_CATEGORIE . $_GET['newName'];
    $newName = $_GET['newName'];
    // Renommage du fichier s'il n'existe pas déjà
    if (!file_exists(PATH_DATAS . $newName)) {
        rename(PATH_DATAS . $_GET['filename'], PATH_DATAS . $newName);
        header('HTTP/2 200 OK');
        $forceFile = new ArrayObject();
        $forceFile->append($newName);
        foreach (getHtmlForFiles($forceFile) as $file) {
            echo $file;
        }
        die();
    }
    header('HTTP/2 403 Forbidden');
    die();
}