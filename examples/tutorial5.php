<?php

/**
 * Tutoriel file 5.
 *
 * Description : Merging a Segment with some data and additional pictures
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

$odf = new Odf("tutorial5.odt");

$odf->setVars('titre', 'Quelques articles de l\'encyclopédie Wikipédia');

$message = "La force de cette encyclopédie en ligne réside dans son nombre important de 
contributeurs. Ce sont en effet des millions d'articles qui sont disponibles dans la langue 
de Shakespeare et des centaines de milliers d'autres dans de nombreuses langues dont 
le français, l'espagnol, l'italien, le turc ou encore l'allemand.";

$odf->setVars('message', $message);

$listeArticles = array(
	array(	'titre' => 'PHP',
			'texte' => 'PHP (sigle de PHP: Hypertext Preprocessor), est un langage de scripts (...)',
			'image' => './images/php.gif'
	),
	array(	'titre' => 'MySQL',
			'texte' => 'MySQL est un système de gestion de base de données (SGBD). Selon le (...)',
			'image' => './images/mysql.gif'
	),
	array(	'titre' => 'Apache',
			'texte' => 'Apache HTTP Server, souvent appelé Apache, est un logiciel de serveur (...)',
			'image' => './images/apache.gif'
	)
);

$article = $odf->setSegment('articles');
foreach($listeArticles AS $element) {
	$article->titreArticle($element['titre']);
	$article->texteArticle($element['texte']);
	$article->setImage('image', $element['image'], -1);
	$article->merge();
}
$odf->mergeSegment($article);

// We export the file. Note that the output will appear on stdout.
// If you want to capture it, use something as " > output.odt."
$odf->exportAsAttachedFile();
