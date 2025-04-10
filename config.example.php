<?php
/*
 * Copyright 2020-2025 Anael MOBILIA
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
// URL par défaut
define('BASE_URL', 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']));
// Répertoire & URL pour les fichiers PDF
const FOLDER_DATAS = '/fichiers/';
const PATH_DATAS = __DIR__ . FOLDER_DATAS;
define('URL_DATAS', BASE_URL . substr(FOLDER_DATAS, 1));
// Répertoire & URL pour les miniatures des fichiers PDF
const FOLDER_THUMBS = '/miniatures/';
const PATH_THUMBS = __DIR__ . FOLDER_THUMBS;
define('URL_THUMBS', BASE_URL . substr(FOLDER_THUMBS, 1));
// Miniature par défaut
const DEFAULT_THUMBS = URL_THUMBS . 'default_image.png';
// Type MIME des fichiers acceptés
const MIME_TYPE = 'application/pdf';

// Nom de champs utilisés en JS
const FIELD_SEARCH = 'recherche';
const FIELD_UPLOAD = 'upload';

// Catégories de document
const CATEGORIES = ['catégorie 1', 'catégorie 2'];
const CATEGORIES_TOUTES = -1;
const CATEGORIE_ARCHIVES = 'archive';

// Actions sur les fichiers
const ACTION_RENOMMER = 'renommer';
const ACTION_ARCHIVER = 'archiver';

// Séparateur dans le nom du fichier
const SEPARATEUR_CATEGORIE = ' - ';

// Chargement des fonctions
require __DIR__ . '/fonctions.php';