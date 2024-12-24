<?php

/**
 * Tutoriel file 3.
 *
 * Description : Merging a Segment with some data
 * You need PHP 8.1 at least
 * You need Zip Extension or PclZip library
 *
 * @copyright  GPL License 2008 - Julien Pauli - Cyril PIERRE de GEYER - Anaska (http://www.anaska.com)
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version 1.3
 */

use Odtphp\Odf;

// Make sure you have Zip extension or PclZip library loaded.
// First : include the library.
require_once '../vendor/autoload.php';

$odf = new Odf("tutorial3.odt");

$odf->setVars('titre', 'Quelques articles de l\'encyclopédie Wikipédia');

$message = "PHP (sigle de PHP: Hypertext Preprocessor), est un langage de scripts libre 
principalement utilisé pour produire des pages Web dynamiques via un serveur HTTP, mais 
pouvant également fonctionner comme n'importe quel langage interprété de façon locale, 
en exécutant les programmes en ligne de commande.";

$odf->setVars('message', $message);

$listeArticles = array(
	array(	'titre' => 'PHP',
			'texte' => 'PHP (sigle de PHP: Hypertext Preprocessor), est un langage de scripts (...)',
	),
	array(	'titre' => 'MySQL',
			'texte' => 'MySQL est un système de gestion de base de données (SGBD). Selon le (...)',
	),
	array(	'titre' => 'Apache',
			'texte' => 'Apache HTTP Server, souvent appelé Apache, est un logiciel de serveur (...)',
	),		
);

$article = $odf->setSegment('articles');
foreach($listeArticles AS $element) {
	$article->titreArticle($element['titre']);
	$article->texteArticle($element['texte']);
	$article->merge();
}
$odf->mergeSegment($article);

// We export the file. Note that the output will appear on stdout.
// If you want to capture it, use something as " > output.odt".
$odf->exportAsAttachedFile();
