<?php
// This file is utf-8 encoded and contains some special characters.
// Editing this file with an ASCII editor will potentially destroy it!
/**
 * File containing the french doc of the xGlossary extension.
 * File released under the terms of the GNU GPL v3.
 *
 * @file
 */

// Do not access this file directly…
if (!defined('MEDIAWIKI')) {
	die('This file is a MediaWiki extension, it is not a valid entry point');
}

// Redefine/extend the GLOSSARY_HELP static var of xGlossaryI18n class.
// Note: Do not use any template here, you don’t know which wiki will have set
//       them! Only use standard base wiki syntax.
// Don’t forget the version number in the main title!
xGlossaryI18n::$GLOSSARY_HELP["fr"] =
'=Aide de l’extension xGlossary (v0.1.3)=
Cette page documente l’ensemble de l’extension xGlossary (licence GPL 3).
:\'\'\'Note\'\'\' : Cette page ne documente que les \'\'fonctions d’extension\'\', et pas les \'\'templates\'\' additionnels que vous pourriez définir (comme ceux que nous définirons peut-être pour [http://wiki.blender.org blenderwiki]), pour faciliter/améliorer ces fonctionnalités de base. Cependant, vous trouverez à la fin de cette page quelques suggestions…

Cette extension définit les fonctions suivantes :
*[[#Les pages de glossaire|<code>#glossary:…</code>]] – La fonction glossaire principale.
*[[#Les entrées de glossaire|<code>#glossary_entry:…</code>]] – La fonction de définition d’entrée.
*[[#Les liens de glossaire|<code>#glossary_link:…</code>]] – La fonction de lien au glossaire.
*[[#L’aide du glossaire|<code>#glossary_help:…</code>]] – La fonction affichant cette page d’aide !
*[[#Les tests du glossaire|<code>#glossary_test:…</code>]] – Une fonction effectuant quelques tests basiques (uniquement utile pour le développement/débogage).
*Il y a également des [[#Paramètres globaux|paramètres globaux]] qui contrôlent certains aspects de son comportement général…

À propos des paramètres des fonctions-wiki :
*Certains sont <code>(obligatoire)</code>s, d’autres sont <code>(optionnel)</code>s ; en général, ces paramètres ne sont pas “wiki-parsés” par l’extension xGlossary, mais il y a quelques exceptions importantes, notées comme <code>(obligatoire[wiki])</code> ou <code>(optionnel[wiki])</code>. Des paramètres obligatoires absents ou vides produiront des messages d’erreur dans le xhtml résultant.
*Certains paramètres sont en fait une collection de valeurs. Dans ce cas, ils utilisent la syntaxe commune suivante : un ou plusieurs ensembles d’options entourés de parenthèses, chaque ensemble constitué d’une ou plusieurs paires “clé=valeur” séparées par des points-virgule “;”. Cela implique que si vous voulez utiliser l’un des caractères “(”, “)” ou “;” au sein d’une valeur, \'\'vous devrez l’échapper avec un \'\'backslash\'\' “\”\'\'.
*Tous les paramètres non-“wiki-parsés” sont bien évidemment échappés – ni xhtml ni code wiki ne sont permis, ce sont des bouts de texte brut !

==Introduction==
Comme l’avez sans doute deviné, cette extension a été conçue et codée pour implémenter un simple “glossaire” pour les sites utilisant MediaWiki comme moteur de documentation de projet. Voici les principes généraux :
*Vous créez une ou plusieurs pages en tant que glossaires, en y plaçant l’appel de fonction <code>#glossary:</code>. Chaque glossaire contient des “entrées”, triées et regroupées au sein de “groupes”.
*Ensuite, vous créez plusieurs sous-pages (c-à-d des pages dont le nom débute exactement comme celui de leur page de glossaire), qui contiennent les entrées (en tant qu’appels <code>#glossary_entry:…</code>). Par défaut, chaque sous-page définit un groupe. Cependant, les entrées contenues dans une sous-page donnée ne se retrouveront pas forcément dans le groupe définit par cette page (voyez ci-dessous pour plus de détails).
*Chaque entrée est constituée de divers paramètres. Encore une fois, voyez plus loin pour les détails.
*Ensuite, n’importe où dans le wiki, vous pouvez utiliser <code>#glossary_link:…</code> pour créer des liens vers une entrée donnée (c’est à vous de lui fournir un chemin-wiki valide – “embarquer” cette fonction dans un template-wiki peut se révéler fort pratique !). Ces liens peuvent contenir la partie “shortdesc” de l’entrée, pour être affichée par ex. dans une “info-bulle”…

\'\'\'Important :\'\'\' Actuellement, les pages mises en cache ne sont pas invalidées, quand vous modifiez par exemple une entrée dans une sous-page, celle du glossaire ne sera pas immédiatement mise à jour en conséquence, à moins que vous ne la purgiez (<code>?&action=purge</code>) explicitement (c’est également valable pour les textes “shortdesc” des liens de glossaire).

==Les pages de glossaire==
Vous créez une page de glossaire en y insérant un appel à la fonction <code>#glossary:</code>. Ce “tag” sera remplacé par le contenu (les entrées) de toutes ses sous-pages, regroupées et triées.

Par défaut, chaque sous-page définit automatiquement un groupe, mais cela peut être modifié par le paramètre optionnel “groups”, voyez ci-dessous. \'\'\'Il est important de comprendre que la sous-page dans laquelle est définie une entrée n’a aucune importance dans la résultat produit par <code>#glossary:</code> !\'\'\' Vous pouvez donc définir vos entrées où vous voulez – cependant, vous devriez évidemment les garder bien arrangées, pour le bénéfice des futurs éditeurs !

Mis à part les paramètres globaux, décrits [[#Paramètres globaux|ci-dessous]], cette fonction prend trois paramètres optionnels :
;<code>groups</code> (optionnel)
:Si non-vide, il écrase les groupes découverts d’après les sous-pages, voyez [[#Les groupes de glossaire|ci-dessous]] pour plus de détails.
;<code>disp_lang</code> (optionnel)
:Le code ISO de la langue que vous souhaitez utiliser (cela peut affecter les messages, ou même l’ensemble du résultat, voyez les [[#Paramètres globaux|paramètres globaux]]). S’il est vide, la valeur pour l’utilisateur courant est utilisée.
;<code>keep_emptgrp</code> (optionnel)
:S’il faut afficher les groupes vides (“yes”) ou pas (“no”). Notez que cela écrase également un paramètre global.

Il y a une chose importante à savoir à propos de cette fonction : actuellement, elle se fiche de regrouper/trier de “vraies” entrées, ou d’autres éléments ! Elle “parse” simplement le xhtml résultant des sous-pages, pour extraire et ordonner tout élément xhtml contenant un attribut nommé “<code>xg_sortkey</code>”. Donc n’importe quoi contenant un tel attribut devrait être (sera) affiché et trié par cette fonction – et rien d’autre (c-à-d que tout ce qui se trouve en-dehors de ces éléments est ignoré/inutilisé).

===Les groupes de glossaire===
Comme annoncé ci-dessus, les groupes d’un glossaire regroupent les entrées. Ils peuvent être automatiquement détectés d’après les noms des sous-pages, ou explicitement spécifiés dans un des paramètres de l’appel <code>#glossary:</code>, en utilisant la syntaxe suivante :

Chaque groupe est entouré de parenthèses (comme “<code>(name=A;ref=a)(name=Meshes;ref=meshes;sort=mesh)</code>”), contenant les paramètres suivants :
;<code>name</code> (obligatoire)
:Le nom du groupe (ce qui sera affiché).
;<code>ref</code> (obligatoire)
:La clé de référence du groupe (ce qui sera utilisé par les liens).
;<code>sort</code> (optionnel)
:La clé de tri du groupe (ce qui sera utilisé lors de l’ordonnancement des groupes). Si vide, la valeur de <code>ref</code> sera utilisée.

Les groupes ont un \'\'nom\'\' (ce qui est affiché), une \'\'référence\'\' (leur id de lien), et une \'\'clé de tri\'\' (utilisée lors de leur ordonnancement dans le glossaire). Lorsqu’ils sont auto-générés, nom, référence et clé de tri sont tous créés à partir du nom de la sous-page (moins la “racine”, le nom de la page de glossaire).

Il y a une subtilité avec les groupes : vous pouvez avoir des sortes de “sous-groupes” – pas sous une forme “arborescente”, mais des sous-groupes “affinés”. Par ex., vous pouvez avoir un groupe général “m”, et un groupe plus spécifique “mesh”, qui se retrouvera juste après le groupe “m”, et regroupera toutes les entrées dont la clé de tri commence par “mesh”.

Comme pour les entrées, les clés de référence et de tri devraient uniquement contenir des caractères alpha-numériques en minuscule (cela facilite les manipulations d’url).

Il y a deux clés de tri spéciales et prédéfinies : “__first” et “__last” – pour les anglophobes absolus, elles placent le groupe respectivement en tête et en queue de liste…

Pour finir, vous vous demandez peut-être “qu’est ce que vous faites des entrées ne trouvant place dans aucun groupe ?” Eh bien, il y a un groupe spécial, appelé par défaut “Misc” (ou “Divers” en français, avec pour id “__misc” et pour clé de tri “__last”), qui se chargera de toutes ces pauvres entrées orphelines !

==Les entrées de glossaire==
<code>#glossary_entry:</code> est la fonction “rendant” une entrée en xhtml. Elle devrait être un simple template-wiki, si ces templates pouvaient produire directement du xhtml ! Cependant, elle effectue également des opérations plus avancées, comme avec l’option des [[#Synonymes|synonymes]]…

Elle est conçue pour être utilisée dans les sous-pages de glossaire. Elle attend un “template” avec les paramètres suivants :
;<code>disp_lang</code> (optionnel)
:Le code iso de la langue à utiliser pour les templates/messages/… (langue de l’utilisateur courant, par défaut).
;<code>langs</code> (obligatoire)
:Une liste, séparée par des virgules, de codes de langue, par ex. “EN, FR”. Il peut évidemment n’y en avoir qu’une !
;<code>title</code> (obligatoire)
:Le titre de l’entrée.
;<code>ref</code> (obligatoire)
:La référence utilisée dans le liens (devrait être une simple valeur alpha-numérique, sans fioritures comme les accents…).
;<code>sort</code> (optionnel)
:La clé de tri (utilisée par [[#Les pages de glossaire|<code>#glossary:</code>]] pour trier les entrées !), vaut <code>ref</code> si vide.
;<code>dict</code> (optionnel)
:Des (sous-)entrées “dictionnaire”, surtout utiles pour les auteurs/traducteurs, voyez [[#Dict|ci-dessous]].
;<code>syns</code> (optionnel)
:Définit des entrées “synonymes”, qui ne contiendront qu’un lien vers la “vraie” entrée. Particulièrement utiles pour les glossaires traduits, voyez [[#Synonymes|ci-dessous]].
;<code>shortdesc</code> (obligatoire[wiki])
:Une courte description de cette entrée, “parsée” comme texte wiki. Elle sera incluse dans l’info bulle des liens de glossaire.
;<code>longdesc</code> (optionnel[wiki])
:Une description plus longue de cette entrée, si nécessaire (elle sera aussi “wiki-parsée” !).

===Dict===
“Dict” est un petit morceau d’information à propos d’un terme spécifique, similaire à une entrée de dictionnaire simplifiée. Il est conçu pour contenir des données surtout utiles pour les auteurs/traducteurs de la documentation (ça ne devrait pas être une définition, mais plutôt pointer un synonyme de l’entrée, ou une traduction dans une autre langue, …).

Chaque élément “dict” est entouré de parenthèses, suivant la même syntaxe que les [[#Les groupes de glossaire|définitions de groupes]], et attend les paramètres suivants :
;<code>langs</code> (obligatoire)
:La(les) langue(s) du terme (liste séparée par des virgules).
;<code>term</code> (obligatoire)
:Le terme définit ici…
;<code>approx</code> (optionnel)
:Est-il approximatif (donnera un “~” si “yes”, rien sinon) ?
;<code>uncertain</code> (optionnel)
:Est-il incertain (donnera un “(?)” si “yes”, rien sinon) ?
;<code>usage</code> (optionnel)
:Valeur d’“usage”, entre 1 (utilisation fortement découragée) et 5 (utilisation fortement encouragée). Produira autant d’étoiles que la valeur.
;<code>note</code> (optionnel[wiki])
:Un court commentaire, qui sera “wiki-parsé” (attention aux “(”, “)” et “;”, qui \'\'\'doivent\'\'\' être échappés…).

===Synonymes===
Les “synonymes” sont des entrées générées automatiquement, qui se contentent de se lier à leur entrée “créatrice” – donc comme son nom le suggère, c’est un raccourcis pour créer des synonymes à une entrée ! Le message de redirection peut être personnalisé et traduit, voyez la [[#Paramètres globaux|description du paramètre global <code>mEnsynRedirMsg</code>]].

Comme avec les éléments “dict”, chaque synonyme est entouré de parenthèses, suivant la même syntaxe que les [[#Les groupes de glossaire|définitions de groupes]], et attend les paramètres suivants :
;<code>langs</code> (obligatoire)
:Les langues du synonyme, comme ci-dessus.
;<code>title</code> (obligatoire)
:Le titre du synonyme…
;<code>ref</code> (obligatoire)
:La référence de lien du synonyme.
;<code>sort</code> (optionnel)
:La clé de tri du synonyme, vaut <code>ref</code> si vide.

Notez que dans les sous-pages, les entrées-synonymes apparaissent juste sous leurs créatrices. Cependant, dans la page principale du glossaire, elles seront correctement regroupées et triées comme attendu !

==Les liens de glossaire==
Les liens xGlossary sont un type “spécialisé” de lien pointant vers des entrées dans les pages de glossaire.

L’une de leur fonctionnalités clé est qu’ils peuvent retourner le contenu de la “shortdesc” de l’entrée liée, ce qui, avec un peu de JavaScript, peut être transformé en une info bulle apparaissant quand la souris survole le lien, par exemple.

Notez que quand vous utilisez la référence d’une entrée synonyme générée automatiquement, vous aurez \'\'la shortdesc de la “vraie” entrée\'\', pas le “texte de redirection” de l’entrée synonyme !

Voici les paramètres de cette fonction :
;<code>disp_lang</code> (optionnel)
:Le code iso de la langue à utiliser pour les templates/messages/… (par défaut, la langue de l’utilisateur courant).
;<code>ref</code> (obligatoire)
:Le chemin-wiki complet vers l’entrée liée (c-à-d nom/de/page#“ref”_de_l’entrée). Voyez [[#Suggestions de templates|ci-dessous]] pour des idées d’automatisation de la création de ce chemin à partir de la seule référence de l’entrée…
;<code>text</code> (obligatoire[wiki])
:Le texte du lien.
;<code>show_sdesc</code> (optionnel)
:Si réglé à “yes” ou “no”, cela écrasera la valeur du [[#Paramètres globaux|paramètre global mLinkShowShortDesc]].

Notez que vous pouvez empêcher la récupération automatique de la “shortdesc” en mettant le [[#Paramètres globaux|mLinkShowShortDesc global]] à “false”…

==L’aide du glossaire==
Eh bien, cela affiche cette page d’aide ! Créez simplement une page vide avec l’appel à <code>#glossary_help:</code> (et éventuellement un paramètre disp_lang) pour la voir.

==Les tests du glossaire==
<code>#glossary_test:</code> va lancer quelques tests basiques, et afficher leurs résultats. Elle ne peut tester toutes les fonctionnalités, mais c’est un bon point de départ à vérifier si vous rencontrez des problèmes…

==Paramètres globaux==
Ce sont les réglages que vous pouvez définir dans votre fichier <code>LocalSettings.php</code>, \'\'après avoir importé <code>glossary.setup.php</code>\'\'. Notez que nous utilisons une seule variable globale, <code>wgxGlossarySettings</code>, qui contient tous les réglages !

;$wgxGlossarySettings->mKeepEmptyGroups
:(par défaut : <code>false</code>)
:S’il faut conserver les groupes vides dans la page de glossaire principale. Cela peut être re-défini par chaque appel <code>#glossary:</code>, voyez [[#Les pages de glossaire|ci-dessus]].

;$wgxGlossarySettings->mMiscGroupName
:(par défaut : <code>array("en" => "Misc", "fr" => "Divers")</code>)
:Le nom du groupe “__misc”, dans toutes les langues nécessaires (celle par défaut est l’anglais ; seules les valeurs anglaises et françaises sont définies actuellement).

;$wgxGlossarySettings->mMiscGroupSortKey
:(par défaut : <code>"__last"</code>)
:La clé qui sera utilisée pour trier le groupe “__misc” (utilisez “__first” pour le mettre en tête, “__last” pour le mettre en queue… Faites attention à ne pas utiliser une même clé de tri que l’un de vos groupes “normaux” !).

;$wgxGlossarySettings->mEnsynRedirMsg
:(par défaut : <code>array("en" => "See the “&#91;&#91;$1|$2&#93;&#93;” entry.", "fr" => "Voyez l’entrée “&#91;&#91;$1|$2&#93;&#93;”.")</code>)
:Les messages de “redirection” dans les [[#Synonymes|entrées synonymes générées automatiquement]].

;$wgxGlossarySettings->mLinkShowShortDesc
:(par défaut : <code>true</code>)
:S’il faut ou non montrer la partie “shortdesc” de l’entrée dans les liens de glossaire. Cela peut être re-défini par chaque appel <code>#glossary_link:</code>, voyez [[#Les liens de glossaire|ci-dessus]].

;$wgxGlossarySettings->mLinkShowShortDescInGlossary
:(par défaut : <code>true</code>)
:S’il faut ou non montrer la partie “shortdesc” de l’entrée dans les liens de glossaire, au sein des pages de glossaire. Les afficher implique \'\'\'deux\'\'\' rendus de ces pages de glossaire, vous pourriez donc désactiver cela pour des raisons de performance…

;$wgxGlossarySettings->mShowPerfs
:(par défaut : <code>false</code>)
:S’il faut ou non afficher les infos de performance (i.e. les temps d’exécution de certaines fonctions clé)…

===Templates===
Tous les éléments du système de glossaire sont rendus à travers un système de “template” simplifié, ce qui signifie que vous pouvez personnaliser de façon assez avancée le code xhtml produit par les fonctions de xGlossary. Ces templates sont égalements i18n-ables.

\'\'\'Attention :\'\'\'Nous parlons ici de templates xGlossary, pas de templates MediaWiki ! Il y a deux options dans $wgxGlossarySettings qui contrôlent ces templates::

;$wgxGlossarySettings->mTemplateVarsNames
:La définition des noms des variables des templates. Voici son contenu actuel :
<pre>array(
	// The place holder for syntax-errors messages.
	"xg_err"         => "{{{xg_err}}}",
	// General glossary index and content.
	"xg_idx"         => "{{{xg_idx}}}",
	"xg_content"     => "{{{xg_content}}}",
	// The glossary group reference, name and content.
	"xg_grname"      => "{{{xg_grname}}}",
	"xg_grref"       => "{{{xg_grref}}}",
	"xg_grcontent"   => "{{{xg_grcontent}}}",
	// The glossary entry elements.
	"xg_enref"       => "{{{xg_enref}}}",        // The reference of the entry.
	"xg_enediturl"   => "{{{xg_enediturl}}}",    // The “edit” url of owner sub-page.
	"xg_enlangs"     => "{{{xg_enlangs}}}",      // The languages of the entry.
	"xg_entitle"     => "{{{xg_entitle}}}",      // The title of the entry.
	"xg_ensort"      => "{{{xg_ensort}}}",       // The sort key of the entry.
	"xg_enshortdesc" => "{{{xg_enshortdesc}}}",  // The short description.
	"xg_endict"      => "{{{xg_endict}}}",       // The dict content.
	"xg_enlongdesc"  => "{{{xg_enlongdesc}}}",   // The long description.
	// The synonym entry specificities.
	"xg_ensynorgref" => "{{{xg_ensynorgref}}}",  // The reference to the org entry.
	"xg_ensynmsg"    => "{{{xg_ensynmsg}}}",     // The message of the “redirection”.
	// The dict elements.
	"xg_dctlangs"    => "{{{xg_dctlangs}}}",     // The languages of this def.
	"xg_dctterm"     => "{{{xg_dctterm}}}",      // The term of this def.
	"xg_dctapprox"   => "{{{xg_dctapprox}}}",    // The approx state ("" or "?").
	"xg_dctuncrt"    => "{{{xg_dctuncrt}}}",     // The uncertain state ("" or "~").
	"xg_dctusage"    => "{{{xg_dctusage}}}",     // The usage value ("*" to "*****").
	"xg_dctnote"     => "{{{xg_dctnote}}}",      // The notes about this def.
	// The link elements.
	"xg_lnkpath"     => "{{{xg_lnkpath}}}",      // The wiki-path to the entry.
	"xg_lnktext"     => "{{{xg_lnktext}}}",      // The text used by the link.
	"xg_lnkshortdesc"=> "{{{xg_lnkshortdesc}}}", // The shortdesc of the linked entry.
);</pre>
:Les clés (chaînes de gauche) ne devraient \'\'\'jamais\'\'\' être modifiées.
:Les chaînes de droite sont les chaînes qui serviront comme “tenant-lieu” pour les variables de templates. Vous ne devriez quasiment jamais avoir besoin de les modifier, de toute façon.

;$wgxGlossarySettings->mTemplates
:Les templates eux-mêmes, qui sont des morceaux de code xhtml, avec les “tenant-lieu” à être remplacés par les variables calculées.
:Il s’agit d’un tableau 2D, avec au premier niveau, les codes ISO de chaque langue («en”, “fr”, etc.), puis les templates i18n.
:Voici un exemple simplifié :
<pre>array(
	"en" =&gt; array(
		/*
		 * A “template” for warning the user about errors (lacking parameters, etc.).
		 * {{{xg_err}}} will be replaced by the error message.
		 */
		xGlossaryI18n::TMPL_ERROR =&gt;
\'&lt;span class="xg_error"&gt;{{{xg_err}}}&lt;/span&gt;\',
		/*
		 * A “template” for the glossary page.
		 * {{{xg_idx}}} will be replaced by an unordered list of links to all known groups.
		 * {{{xg_content}}} will be replaced by all generated groups.
		 */
		xGlossaryI18n::TMPL_GLOSSARY =&gt; 
\'&lt;div class="xg_glossary"&gt;&lt;!--
	--&gt;&lt;div class="xg_index"&gt;&lt;!--
		--&gt;{{{xg_idx}}}&lt;!--
	--&gt;&lt;/div&gt;&lt;!--
	--&gt;&lt;div class="xg_content"&gt;&lt;!--
		--&gt;{{{xg_content}}}&lt;!--
	--&gt;&lt;/div&gt;&lt;!--
--&gt;&lt;/div&gt;,\'
	),
);</pre>
:Vous noterez dans le code ci-dessus que les “tenant-lieu” utilisent les clés de <code>$wgxGlossarySettings->mTemplateVarsNames</code>, et pas ses valeurs. Elles seront remplacées au moment de l’initialisation par les bonnes valeurs, pour vous permettre de définir d’autres noms de “tenant-lieu”, sans avoir à toucher aux templates proprement dit…
:Notez également que tous les retours à la ligne sont commentés en xhtml, pour éviter que MediaWiki rajoute des &lt;p&gt;&lt;/p&gt; partout (Grrr !).
:<code>xGlossaryI18n::TMPL_XXX</code> sont des constantes pour chaque templates :
<pre>// Templates “id”.
const TMPL_ERROR          = "xg_tmpl_error";
const TMPL_GLOSSARY       = "xg_tmpl_glossary";
const TMPL_GROUP          = "xg_tmpl_group";
const TMPL_ENTRY          = "xg_tmpl_entry";
const TMPL_ENTRYSYN       = "xg_tmpl_entrysyn";
const TMPL_DICT           = "xg_tmpl_dict";
const TMPL_LINK           = "xg_tmpl_link";</pre>

Pour plus d’informations et d’exemples sur les templates xGlossary, lisez le code du fichier <code>glossary.i18n.php</code> – de toute façon, si vous prévoyez de modifier ces réglages, vous êtes sans aucun doute un “site admin” ;) .

Même si c’est évident, mentionnons également la feuille de style CSS comme moyen de personnaliser la partie graphique de cette extension !

==À propos de JS==
xGlossary utilise Java Script à deux endroits (les groupes d’entrées repliables des pages de glossaire, et l‘info bulle des liens de glossaire) – mais JS n’est \'\'absolument pas nécessaire au bon fonctionnement de xGlossary\'\' !

Le code Java Script fourni avec cette extension utilise jQuery, vous devrez donc inclure cette librairie dans vos pages si vous voulez l’utiliser “tel quel”. Cependant, j’ai essayé de bien préparer la production de xGlossary afin qu’elle soit utilisable par n’importe quel code JS, en utilisant des classes “xg_js_xxx” pour marquer les éléments pour JS, il devrait donc être assez facile d’adapter tout ça pour une autre “boîte à outil” JS…

==Suggestions de templates==
Voici quelques suggestions de templates (MediaWiki, cette fois !) utilisant des fonctions xGlossary, et automatisant certaines actions :

;Afficher la sélection de langue
:Si vous avez un site multilingue avec une façon standard de nommer les pages pour les différents langages, vous pourriez vouloir créer un glossaire pour chacune. Par ex. avec le wiki de Blender, nous utilisons {espace_de_nom}:{langue/}{nom_de_page}, vous pouvez donc créer un template qui détecte la langue de la page de glossaire courante, et règle en conséquence le paramètre “disp_lang” de l’appel <code>#glossary:</code> qu’il contient. La même chose est valable pour les entrées et liens de glossaire, évidemment !

;Définir le bon chemin pour un lien de glossaire
:En supposant que vous avez une page bien déterminée de glossaire, vous pouvez créer un template Glossary/Link qui, avec juste la référence d’une entrée, va créer le bon chemin vers la page de glossaire, avec la référence comme “fragment” url.
';







