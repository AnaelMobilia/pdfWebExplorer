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
 * Configuration de l'application
 */
// Connexion limitée
define('SLOW_CONNEXION', isset($_REQUEST['slow']));

// URL par défaut
define('BASE_URL', "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']));
// URL avec le mode d'affichage
define('BASE_URL_AFFICHAGE', BASE_URL . "?" . (SLOW_CONNEXION ? "slow" : ""));
// Répertoire & URL pour les les fichiers PDF
define('FOLDER_DATAS', '/fichiers/');
define('PATH_DATAS', __DIR__ . FOLDER_DATAS);
define('URL_DATAS', BASE_URL . substr(FOLDER_DATAS, 1));
// Répertoire & URL pour les les miniatures des fichiers PDF
define('FOLDER_THUMBS', '/miniatures/');
define('PATH_THUMBS', __DIR__ . FOLDER_THUMBS);
define('URL_THUMBS', BASE_URL . substr(FOLDER_THUMBS, 1));
// Miniature par défaut
define('DEFAULT_THUMBS', URL_THUMBS . "default_image.png");
// Type MIME des fichiers acceptés
define('MIME_TYPE', 'application/pdf');

// Nom de champs utilisés en JS
define('FIELD_SEARCH', 'recherche');
define('FIELD_UPLOAD', 'upload');

// Catégories de document
define('CATEGORIES', ["categorie 1", "categorie 2"]);
define('CATEGORIES_TOUTES', -1);