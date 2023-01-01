<?php
/**
* Multi-Category Search
* This MediaWiki extension represents a [[Special:MultiCategorySearch|special page]],
* 	that allows to find pages, included in several specified categories at once.
* Internationalization file, containing message strings for extension.
* Requires MediaWiki 1.8 or higher and MySQL 4.1 or higher.
* Extension's home page: http://www.mediawiki.org/wiki/Extension:Multi-Category_Search
*
* Distributed under GNU General Public License 2.0 or later (http://www.gnu.org/copyleft/gpl.html)
*/

$messages = array();

/*
// For customized URLs, e. g. /Spezial:KategorieMehrfachSuche
$aliases = array();
$aliases['de'] = array( 'MultiCategorySearch' => array( 'KategorieMehrfachSuche' ) );
*/

// English messages by Iaroslav Vassiliev
$messages['en'] = array( 
	'multicategorysearch' => 'Multi-category search',
	'multicatsearch_include' => 'List pages, included in the following categories:',
	'multicatsearch_exclude' => 'Ignore pages, included in the following categories:',
	'multicatsearch_submit_button' => 'Search',
	'multicatsearch_comment' => 'This search lists pages, included in several specified ' .
		'categories at once. You can specify up to 5 categories, and optionally up to 3 categories ' .
		'to exclude from search. Don\'t use "Category:" prefix here, please. The search is ' .
		'case-sensitive.',
	'multicatsearch_no_params' => 'Please, select at least one category.',
	'multicatsearch_no_result' => 'No pages found.',
);

// Spanish messages by José Zarco
$messages['es'] = array( 
	'multicategorysearch' => 'Busqueda Multicategoría',
	'multicatsearch_include' => 'Se listaran las páginas incluidas en las siguientes categorías:',
	'multicatsearch_exclude' => 'Se ignoraran las páginas incluidas en las siguientes categorías:',
	'multicatsearch_submit_button' => 'Buscar',
	'multicatsearch_comment' => 'Esta búsqueda muestra un listado con las páginas que estan especificadas ' .
		'en las categorías seleccionadas. Usted puede especificar hasta 5 categorías, ' .
		'y opcionalmente, usar hasta 3 categorías ' .
		'para excluir de la búsqueda. No use el prefijo "Categoría:" . La búsqueda es ' .
		'sensible a mayúsculas',
	'multicatsearch_no_params' => 'Por favor, seleccione al menos una categoría.',
	'multicatsearch_no_result' => 'No se han encontrado resultados.',
);

// German messages by Astrid Kuhr
$messages['de'] = array(
	'multicategorysearch' => 'Kategorie-Mehrfach-Suche',
	'multicatsearch_include' => 'Suche Seiten, die in folgenden Kategorien enthalten sind:',
	'multicatsearch_exclude' => 'Ignoriere Seiten, die in folgenden Kategorien enthalten sind:',
	'multicatsearch_submit_button' => 'Suche',
	'multicatsearch_comment' => 'Diese Spezialseite sucht Seiten in mehreren ' .
		'Kategorien zugleich. Es können bis zu 5 Kategorien angegeben werden ' .
		'und optional bis zu 3 Kategorien, die von der Suche ausgenommen werden sollen.',
	'multicatsearch_no_params' => 'Bitte mindestens eine Kategorie angeben.',
	'multicatsearch_no_result' => 'Keine Seiten gefunden.',
);

// French messages by Thierry Giroux Veilleux
$messages['fr'] = array(
	'multicategorysearch' => 'Recherche dans une combinaison de catégories',
	'multicatsearch_include' => 'Listez les pages qui se retrouvent dans ces catégories:',
	'multicatsearch_exclude' => 'Ignorez les pages, incluses dans ces catégories:',
	'multicatsearch_submit_button' => 'Recherche',
	'multicatsearch_comment' => 'Cette recherche liste les pages incluses dans plusieurs ' .
		'catégories à la fois. Vous pouvez spécifier au maximum 5 catégories à inclure ' .
		'dans votre recherche et, optionnellement, vous pouvez spécifier jusqu\'à ' .
		'3 catégories à exclure de votre recherche. Merci de ne pas utiliser le préfixe ' .
		'« Catégorie: » dans votre recherche. La recherche est sensible à la casse.',
	'multicatsearch_no_params' => 'Merci de sélectionner au moins une catégorie.',
	'multicatsearch_no_result' => 'La recherche n\'a donné aucun résultat.',
);

// Dutch messages by Nanda Jansen
$messages['nl'] = array( 
	'multicategorysearch' => 'Zoek in verschillende categorieën tegelijk',
	'multicatsearch_include' => 'Zoek pagina’s in de volgende categorieën:',
	'multicatsearch_exclude' => 'Negeer pagina’s in de volgende categorieën:',
	'multicatsearch_submit_button' => 'Zoek',
	'multicatsearch_comment' => 'Met deze Speciale pagina kunt u categorieën combineren. '.
		'Zoek tot in 5 categorieën tegelijk. Negeer er maximaal 3. ' .
		'(Het woord "Categorie" hoeft u niet te gebruiken.)',
	'multicatsearch_no_params' => 'Selecteer minstens een categorie.',
	'multicatsearch_no_result' => 'Geen pagina gevonden.',
);

// Russian messages by Iaroslav Vassiliev
$messages['ru'] = array(
	'multicategorysearch' => 'Поиск страниц, включённых в несколько категорий',
	'multicatsearch_include' => 'Найти страницы, включённые в следующие категории:',
	'multicatsearch_exclude' => 'Исключить страницы, включённые в следующие категории:',
	'multicatsearch_submit_button' => 'Поиск',
	'multicatsearch_comment' => 'Эта служебная страница позволяет найти страницы, включённые ' .
		'сразу в несколько указанных категорий. Можно также указать категории статей для ' .
		'исключения из поиска. Не пишите, пожалуйста, в начале слово "Категория:". Соблюдайте ' .
		'регистр букв в названиях категорий.',
	'multicatsearch_no_params' => 'Пожалуйста, введите хотя бы одну категорию.',
	'multicatsearch_no_result' => 'Ни одна страница не соответствует такому набору категорий.',
);

// Turkish messages by Helmut Oberdiek
$messages['tr'] = array( 
	'multicategorysearch' => 'Çok kategori birden arama',
	'multicatsearch_include' => 'Aşağıdaki kategorilere ait sayfalar listele:',
	'multicatsearch_exclude' => 'Aşağıdaki kategorilere ait sayfalar hariç tut:',
	'multicatsearch_submit_button' => 'Ara',
	'multicatsearch_comment' => 'Bu arama ile belirlenen ' .
		'kategoriler içinde birden arama yapılır. Ana kategori ' .
		'hariç 4 kategoriye kadar belirlenebilir. İstenirse 3 kategoriye kadar ' .
		'arama harici tutulabilir.',
	'multicatsearch_no_params' => 'Lütfen enazından bir kategori seçiniz.',
	'multicatsearch_no_result' => 'Sayfa bulunamadı.',
);

// Hebrew messages by Avner Pinchover
$messages['he'] = array( 
	'multicategorysearch' => 'חיפוש במספר קטגוריות',
	'multicatsearch_include' => 'הראה עמודים הכלולים בקטגוריות הבאות:',
	'multicatsearch_exclude' => 'התעלם מעמודים הכלולים בקטגוריות הבאות',
	'multicatsearch_submit_button' => 'חפש',
	'multicatsearch_comment' => 'חיפוש זה מכיל עמודים הכלולים במספר קטגוריות ' .
		'בעת ובעונה אחת. ניתן לבחור עד 5 קטגוריות, וכמו כן עד 3 קטגוריות' .
		'להוצאה מן החיפוש. בבקשה לא להשתמש בקידומת "קטגוריה:" במערכת זו.' .
		'case-sensitive.',
	'multicatsearch_no_params' => 'אנא, יש לבחור לפחות קטגוריה אחת',
	'multicatsearch_no_result' => 'לא נמצאו עמודים.',
);

// Polish messages by Dawid Kamola
$messages['pl'] = array(
	'multicategorysearch' => 'Wyszukiwanie w wielu kategoriach',
	'multicatsearch_include' => 'Listuj strony, znajdujące się w poniższych kategoriach:',
	'multicatsearch_exclude' => 'Ignoruj strony, znajdujące się w poniższych kategoriach:',
	'multicatsearch_submit_button' => 'Szukaj',
	'multicatsearch_comment' => 'Ta wyszukiwarka listuje strony, ' .
		'znajdujące się w kilku kategoriach na raz.' .
		' Możesz ustalić do 5 kategorii i opcjonalnie wykluczyć 3 kategorie z wyszukiwania.' .
		' Nie używaj prefixu "Kategoria:". Wyszukiwarka zwraca uwagę na wielkość znaków. ',
	'multicatsearch_no_params' => 'Proszę wybrać przynajmniej jedną kategorię.',
	'multicatsearch_no_result' => 'Nie znaleziono żadnej strony.',
);

// Italian messages by Cristian Filagrossi
$messages['it'] = array(
	'multicategorysearch' => 'Ricerche Incrociate',
	'multicatsearch_include' => 'Elenca le voci che appartengono alle seguenti categorie:',
	'multicatsearch_exclude' => 'Escludi le voci che appartengono alle seguenti categorie:',
	'multicatsearch_submit_button' => 'Cerca',
	'multicatsearch_comment' => 'La ricerca elenca le pagine che ' .
		'appartengono contemporaneamente alle categorie specificate. ' .
		'E\'possibile specificare fino a 5 categorie da includere e fino a 3 ' .
		'categorie da escludere dalla ricerca. Non usare il prefisso ' .
		'"Categoria:", per favore. La ricerca è sensibile alle maiuscole e alle minuscole.',
	'multicatsearch_no_params' => 'Per favore seleziona almeno una categoria.',
	'multicatsearch_no_result' => 'Nessun risultato trovato.',
);

// Japanese messages by Kouichi Nishizawa
$messages['ja'] = array( 
	'multicategorysearch' => '複数カテゴリ検索',
	'multicatsearch_include' => '下記のカテゴリを含むページ検索します:',
	'multicatsearch_exclude' => '下記のカテゴリを含むページを無視します:',
	'multicatsearch_submit_button' => '検索',
	'multicatsearch_comment' => 'この画面では、指定したカテゴリを含むページを検索します。' .
		'最大5カテゴリと、必要に応じて検索から除外する3つのカテゴリを指定できます。' .
		'Category:プレフィックスは入力しないでください。' .
		'検索はcase-sensitive（大文字小文字を区別する）です。',
	'multicatsearch_no_params' => '最低ひとつのカテゴリを入力してください。',
	'multicatsearch_no_result' => '該当するページが見つかりませんでした。',
);

// Czech messages by Vojtěch Zlámal
$messages['cs'] = array( 
	'multicategorysearch' => 'Hledání podle více kategorií',
	'multicatsearch_include' => 'Vypsat stránky zařazené do následujících kategorií:',
	'multicatsearch_exclude' => 'Ignorovat stránky zařazené do následujících kategorií:',
	'multicatsearch_submit_button' => 'Hledat',
	'multicatsearch_comment' => 'Toto vyhledávání slouží k zobrazování stránek zařazených současně do několika kategorií. ' .
		'Vyhledávat stránky je možné nejvýše podle 5 kategorií, přičemž je dále možné určit ' .
		'až 3 další kategorie, ze kterých budou stránky vynechány. ' .
		'Nepoužívejte prefix "Kategorie:", prosím. Vyhledávání rozlišuje velká a malá písmena.',
	'multicatsearch_no_params' => 'Prosím, zadejte alespoň jednu kategorii.',
	'multicatsearch_no_result' => 'Nebyly nalezeny žádné stránky.',
);

// Swedish messages by Tomas Kindahl
$messages['sv'] = array(
	'multicategorysearch' => 'Flerkategorisökning',
	'multicatsearch_include' => 'Lista sidor som ingår i följande kategorier:',
	'multicatsearch_exclude' => 'Ignorera sidor som ingår i följande kategorier:',
	'multicatsearch_submit_button' => 'Search',
	'multicatsearch_comment' => 'Denna sökning listar sidor som ingår i flera angivna ' .
		'kategorier samtidigt. Man kan ange upp till 5 kategorier, och valbart upp till 3 kategorier ' .
		'att exkludera från sökningen! Använd inte prefixet "Kategori:" här! Sökningen är ' .
		'skiftlägeskänslig (stora/små bokstäver).',
	'multicatsearch_no_params' => 'Välj minst en kategori!',
	'multicatsearch_no_result' => 'Inga sidor kunde hittas.',
);
?>