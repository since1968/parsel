<?php// comment out these lines in production//ini_set ('display_errors', 1);//ERROR_REPORTING(E_ALL);/****************************************************************************** Pepper Parsel  Developer		: Marc A. Garrett Version		: 0.9.0 Date			: 05.09.06	 Plug-in Name	: Parsel  http://since1968.com/  TO DO: - getHTML_LanguagesRecent() and getHTML_LanguagesCommon() share a lot of string parsing code. I should probably break shared code into a separate function.  I used Shaun Inman's Default Pepper and User Agent plug-ins as a model for this plug-in. I also used Harold Hope's language array from his PHP language detection script (http://techpatterns.com/downloads/php_language_detection.php) for converting HTTP_ACCEPT_LANGUAGE into human-friendly output.  INSTALL AT YOUR OWN RISK.More info at: http://since1968.com/This work is licensed under the Creative Commons Attribution-ShareAlike License. To view a copy of this license, visit http://creativecommons.org/licenses/by-sa/2.5/ or send a letter to Creative Commons, 543 Howard Street, 5th Floor, San Francisco, California, 94105, USA.  For more inforamtion on the Accept-Language header see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html  ******************************************************************************/if (!defined('MINT')) { header('Location:/'); }; // Prevent viewing this file$install_plugin = "since1968_Parsel";class since1968_Parsel extends Pepper {		var $version = 90;	var $info = array	(		'pepperName'	=> 'Parsel',		'pepperURL'		=> 'http://since1968.com',		'pepperDesc'	=> "<p>The Parsel Pepper tracks the primary browser language of your visitors.</p><p>For more information on the Accept-Language Header see <a href='http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html'>w3.org</a>.</p>",		'developerName'	=> 'Marc A. Garrett',		'developerUrl'	=> 'http://since1968.com'		);			var $panes = array	(		'Most Common',		'Most Recent'		);			var $prefs = array	(		'show_as_percent' => 1,	// show aggregated values as percentages		'show_raw_string' => 1	// show raw output string		);			var $manifest = array	// we keep same table and column from parsel 0.8 and older, so might not require uninstall	(		'visit' => array		(			'since1968_language' => "VARCHAR( 100 ) NOT NULL default ''"			)		);		/****************************************************************************** isCompatible()		 		******************************************************************************/function isCompatible(){	if ($this->Mint->version >= 128)	{		return array		(			'isCompatible' => true			);	}	else	{		return array		(			'isCompatible' => false,			'explanation' => '<p>This Pepper is only compatible with Mint 1.2.8 and higher.</p>'			);	}}/**************************************************************************	 onRecord()**************************************************************************/function onRecord(){	$language = '';	$language = $this->Mint->escapeSQL($_SERVER['HTTP_ACCEPT_LANGUAGE']);	// trim to 100 characters so string isn't too long for database	// (this is probably overkill)	$language = substr($language, 0, 100);	return array	(		'since1968_language' => $language		)	}	/**************************************************************************	 onJavaScript()	 **************************************************************************/	function onJavaScript() { }		/**************************************************************************	 onDisplay()	 	 **************************************************************************/	function onDisplay($pane,$tab,$column='',$sort='') {		$html = '';				$languages = $this->get_Languages();				switch($pane) {		/* Languages ********************************************************/			case 'Languages': 				switch($tab) {				/* Most Common ************************************************/					case 'Most Common':						$html .= $this->getHTML_LanguagesCommon($languages);						break;				/* Most Recent ************************************************/					case 'Most Recent':						$html .= $this->getHTML_LanguagesRecent($languages);						break;					}				break;			}		return $html;		}		/**************************************************************************	 onWidget()	 	 **************************************************************************/	function onWidget() { }		/**************************************************************************	 onDisplayPreferences()	 	 Should return an assoicative array (indexed by pane name) that contains the	 HTML contents of that pane's preference. Preferences used by all panes in 	 this plug-in should be indexed as 'global'. Any pane that isn't represeneted	 by an index in the return array will simply display the string "This pane	 does not have any preferences" (or similar).	 	 **************************************************************************/function onDisplayPreferences(){	$show_as_percent = ( $this->prefs['show_as_percent'] ? ' checked="checked"' : '' );	$show_raw_string = ( $this->prefs['show_raw_string'] ? ' checked="checked"' : '' );				$preferences['Global'] = '<table>							<tr>								<th scope="row">Show as Percent</th>								<td><input type="checkbox" id="show_as_percent" name="show_as_percent" value="1"' . $show_as_percent . '" /></td>							</tr>							<tr>								<td>&nbsp;</td>								<td>Show the aggregated language data as <strong>counts</strong> or <strong>percentages</strong>.</td>							</tr>							<tr>								<th scope="row">Show Raw Data</th>								<td><input type="checkbox" id="show_raw_string" name="show_raw_string" value="1"' . $show_raw_string . '" /></td>							</tr>							<tr>								<td>&nbsp;</td>								<td>Show the raw language string (such as <code>en-us,en;q=0.5</code>) in addition to the user-friendly language listing.</td>							</tr>						</table>';				return $preferences;		}	/**************************************************************************	 onSavePreferences()	 	 **************************************************************************/	function onSavePreferences() {		$this->prefs['show_as_percent'] = ( isset($_POST['show_as_percent']) ? $_POST['show_as_percent'] : 0 );		$this->prefs['show_raw_string'] = ( isset($_POST['show_raw_string']) ? $_POST['show_raw_string'] : 0 );	}	/**************************************************************************	 onCustom()	 	 **************************************************************************/	function onCustom() { 		if (isset($_POST['action']) && $_POST['action']=='getranges' && isset($_POST['lang']) && isset($_POST['total'])) {			$lang	= $this->Mint->escapeSQL($_POST['lang']);			$total	= $_POST['total'];			echo $this->getHTML_Ranges($lang, $total);			}		}				/**************************************************************************	 getHTML_LanguagesRecent()	 	 This function expects an array of language abbreviations (key) and language 	 names (value).	 	 **************************************************************************/	function getHTML_LanguagesRecent($since1968_languages) {		$html = '';				$show_raw_string = $this->prefs['show_raw_string'];				$tableData['table'] = array('id'=>'','class'=>'');		$tableData['thead'] = array(			// display name, CSS class(es) for each column			array('value'=>'Language Combinations','class'=>'stacked-rows'),			array('value'=>'When','class'=>'')			);					$query = "SELECT SUBSTRING_INDEX(since1968_language, ',', 1) AS str_language, since1968_language AS str_abbrevs, `dt`					FROM `{$this->Mint->db['tblprefix']}visit` 					WHERE					`since1968_language`!=''					ORDER BY `dt` DESC 					LIMIT 0,{$this->Mint->cfg['preferences']['rows']}";							if ($result = mysql_query($query)) {			while ($r = mysql_fetch_array($result)) {				$dt = $this->Mint->formatDateTimeRelative($r['dt']);				$language_row = $this->Mint->abbr(stripslashes($r['str_language']));				$sub_row = '';								// here are some sample strings:				//	en-us,th;q				//	es-uy				//	de;q=1.0,e				//	en,ja;q=0.								// replace semi-colons with commas				$language_row = str_replace(';', ',', $language_row);				if ($show_raw_string == 1) {					$sub_row = "<br /><span>" . $this->Mint->abbr(stripslashes($r['str_abbrevs'])) . "</span>";					}				// explode list of abbreviations into an array				$language_row_exploded = explode(',', $language_row);								// create a new, empty array to hold readable language names				$language_row_readable = array();				// loop through language abbreviations. For each abbreviation, look up in $a_languages				// If found, add human readable name to array. If not found, add original abbreviation to array				// Implode and print when done.				foreach ($language_row_exploded as $lang) {					if(array_key_exists($lang, $since1968_languages)) {							$language_row_readable[] = $since1968_languages[$lang];						} else {							$language_row_readable[] = $lang;						} 					}								$language_row_final = implode(', ', $language_row_readable) . $sub_row;								$tableData['tbody'][] = array(					$language_row_final,					$dt					);				}			}					$html = $this->Mint->generateTable($tableData);		return $html;			}	/**************************************************************************	 getHTML_LangugesCommon()	 This function expects an array of language abbreviations (key) and language 	 names (value).	 	 	 **************************************************************************/	function getHTML_LanguagesCommon($since1968_languages) {		$html = '';		$thead_label = 'Count';				$show_as_percent = $this->prefs['show_as_percent'];		if ($show_as_percent == 1) {			$thead_label = '% of Total';			}				$tableData['hasFolders'] = true;				$tableData['table'] = array('id'=>'','class'=>'folder');		$tableData['thead'] = array(			// display name, CSS class(es) for each column			array('value'=>'Languages','class'=>'stacked-rows'),			array('value'=>$thead_label,'class'=>'')			);					// get all records with language value		$count_query = "SELECT id FROM `{$this->Mint->db['tblprefix']}visit` 					WHERE						`since1968_language`!=''";		$count_result = mysql_query($count_query);		$total = mysql_num_rows($count_result);		$query = "	SELECT 						LEFT(since1968_language, 2) AS str_language,						COUNT(LEFT(since1968_language, 2)) AS `count`, 						`dt`					FROM `{$this->Mint->db['tblprefix']}visit` 					WHERE						`since1968_language`!=''					GROUP BY `str_language` 					ORDER BY `count` DESC, `dt` DESC 					LIMIT 0,{$this->Mint->cfg['preferences']['rows']}";		$result	= mysql_query($query);		if ($result) {			while ($r = mysql_fetch_array($result)) {				$language_row = $this->Mint->abbr(stripslashes($r['str_language']));								if(array_key_exists($language_row, $since1968_languages)) {						$language_row_readable = $since1968_languages[$language_row];					} else {						$language_row_readable = $language_row;					} 				$num_output = 0;				if ($show_as_percent==1) {					$num_output = 					$this->Mint->formatPercents($r['count']/$total*100);					} else {					$num_output = $r['count'];					}								$tableData['tbody'][] = array(					$language_row_readable,					$num_output,					'folderargs'=>array(							'action'=>'getranges',							'lang'=>$r['str_language'],							'total'=>$total							)					);				}			}					$html = $this->Mint->generateTable($tableData);		return $html;		}			/**************************************************************************	 getHTML_Ranges()	 	 This function takes a language (en) and returns a set of ranges such as English (US), English (UK) along with their counts (value).	 	 **************************************************************************/	function getHTML_Ranges($lang, $total) {		$html = '';				$show_as_percent = $this->prefs['show_as_percent'];		//	load array of languages		/*	Normally we do this only once onDisplay() and then pass the array			around to different functions as needed, but getHTML_Ranges() is			called via POST. Including each time is probably not optimal, so			revisit in future version. I don't want to put the array in global 			scope in a multiple-developer plug-in environment, and serializing			the array to pass via POST makes the page too heavy.		*/						$languages = $this->get_Languages();		$query = "SELECT  SUBSTRING_INDEX(since1968_language, ',', 1) AS str_language,         COUNT(SUBSTRING_INDEX(since1968_language, ',', 1)) AS count,         'dt'					FROM `{$this->Mint->db['tblprefix']}visit` 					WHERE					LEFT(since1968_language, 2) ='$lang'                     GROUP BY str_language					ORDER BY count DESC					LIMIT 0,{$this->Mint->cfg['preferences']['rows']}";							if ($result = mysql_query($query)) {			while ($r = mysql_fetch_array($result)) {				// $language_row = $r['str_language'];				if(array_key_exists($r['str_language'], $languages)) {					$language_row = $languages[$r{'str_language'}];					} else {						$language_row = $r['str_language'];					}														$num_output = 0;				if ($show_as_percent==1) {					$num_output = $this->Mint->formatPercents($r['count']/$total*100);					} else {					$num_output = $r['count'];					}									$tableData['tbody'][] = array(					$language_row,					$num_output					);			} // end while fetch					} // end if result		$html = $this->Mint->generateTableRows($tableData);		return $html;	} // end function		/**************************************************************************	get_Languages()	return array of languages, with abbreviation as key and language as value		**************************************************************************/		function get_Languages() {		$languages =  array(			'af' => 'Afrikaans',			'sq' => 'Albanian',			'ar-dz' => 'Arabic (Algeria)',			'ar-bh' => 'Arabic (Bahrain)',			'ar-eg' => 'Arabic (Egypt)',			'ar-iq' => 'Arabic (Iraq)',			'ar-jo' => 'Arabic (Jordan)',			'ar-kw' => 'Arabic (Kuwait)',			'ar-lb' => 'Arabic (Lebanon)',			'ar-ly' => 'Arabic (libya)',			'ar-ma' => 'Arabic (Morocco)',			'ar-om' => 'Arabic (Oman)',			'ar-qa' => 'Arabic (Qatar)',			'ar-sa' => 'Arabic (Saudi Arabia)',			'ar-sy' => 'Arabic (Syria)',			'ar-tn' => 'Arabic (Tunisia)',			'ar-ae' => 'Arabic (U.A.E.)',			'ar-ye' => 'Arabic (Yemen)',			'ar' => 'Arabic',			'hy' => 'Armenian',			'as' => 'Assamese',			'ast' => 'Asturian',				'az' => 'Azeri',			'eu' => 'Basque',			'be' => 'Belarusian',			'bn' => 'Bengali',			'bg' => 'Bulgarian',			'ca' => 'Catalan',			'zh-cn' => 'Chinese (China)',			'zh-hk' => 'Chinese (Hong Kong SAR)',			'zh-mo' => 'Chinese (Macau SAR)',			'zh-sg' => 'Chinese (Singapore)',			'zh-tw' => 'Chinese (Taiwan)',			'zh' => 'Chinese',			'hr' => 'Croatian',			'hr-hr' => 'Croatian',			'cs' => 'Czech',			'da' => 'Danish',			'div' => 'Divehi',			'nl-be' => 'Dutch (Belgium)',			'nl-nl' => 'Dutch (Netherlands)',			'nl' => 'Dutch',			'en-au' => 'English (Australia)',			'en-bz' => 'English (Belize)',			'en-ca' => 'English (Canada)',			'en-ie' => 'English (Ireland)',			'en-jm' => 'English (Jamaica)',			'en-nz' => 'English (New Zealand)',			'en-ph' => 'English (Philippines)',			'en-za' => 'English (South Africa)',			'en-tt' => 'English (Trinidad)',			'en-gb' => 'English (United Kingdom)',			'en-us' => 'English (United States)',			'en-zw' => 'English (Zimbabwe)',			'en' => 'English',			'us' => 'English (United States)',			'et' => 'Estonian',			'fo' => 'Faeroese',			'fa' => 'Farsi',			'fi' => 'Finnish',			'fr-be' => 'French (Belgium)',			'fr-ca' => 'French (Canada)',			'fr-lu' => 'French (Luxembourg)',			'fr-mc' => 'French (Monaco)',			'fr-ch' => 'French (Switzerland)',			'fr-fr' => 'French (France)',			'fr' => 'French',			'mk' => 'FYRO Macedonian',			'gd' => 'Gaelic',			'ka' => 'Georgian',			'de-at' => 'German (Austria)',			'de-li' => 'German (Liechtenstein)',			'de-lu' => 'German (lexumbourg)',			'de-ch' => 'German (Switzerland)',			'de-de' => 'German (Germany)',			'de' => 'German',			'el' => 'Greek',			'gu' => 'Gujarati',			'he' => 'Hebrew',			'hi' => 'Hindi',			'hu' => 'Hungarian',			'is-is' => 'Icelandic (Iceland)',				'is' => 'Icelandic',			'id' => 'Indonesian',			'it-ch' => 'Italian (Switzerland)',			'it' => 'Italian',			'ja-jp' => 'Japanese (Japan)',			'ja' => 'Japanese',			'kn' => 'Kannada',			'kk' => 'Kazakh',			'kok' => 'Konkani',			'ko' => 'Korean',			'kz' => 'Kyrgyz',			'lv' => 'Latvian',			'lt' => 'Lithuanian',			'ms' => 'Malay',			'ml' => 'Malayalam',			'mt' => 'Maltese',			'mr' => 'Marathi',			'mn' => 'Mongolian (Cyrillic)',			'ne' => 'Nepali (India)',			'nb-no' => 'Norwegian (Bokmal)',			'nb' => 'Norwegian (Bokmal)',				'nn-no' => 'Norwegian (Nynorsk)',			'no' => 'Norwegian: No specified variant',			'nn' => 'Norwegian',			'or' => 'Oriya',			'pl' => 'Polish',			'pt-br' => 'Portuguese (Brazil)',			'pt-pt' => 'Portuguese (Portugal)',			'pt' => 'Portuguese',			'pa' => 'Punjabi',			'rm' => 'Rhaeto-Romanic',			'ro-md' => 'Romanian (Moldova)',			'ro' => 'Romanian',			'ru-md' => 'Russian (Moldova)',			'ru-ru' => 'Russian (Russia)',			'ru' => 'Russian',			'sa' => 'Sanskrit',			'sr' => 'Serbian',			'sk' => 'Slovak',			'sl' => 'Slovenian',			'sb' => 'Sorbian',			'es-ar' => 'Spanish (Argentina)',			'es-bo' => 'Spanish (Bolivia)',			'es-cl' => 'Spanish (Chile)',			'es-co' => 'Spanish (Colombia)',			'es-cr' => 'Spanish (Costa Rica)',			'es-do' => 'Spanish (Dominican Republic)',			'es-ec' => 'Spanish (Ecuador)',			'es-sv' => 'Spanish (El Salvador)',			'es-gt' => 'Spanish (Guatemala)',			'es-hn' => 'Spanish (Honduras)',			'es-mx' => 'Spanish (Mexico)',			'es-ni' => 'Spanish (Nicaragua)',			'es-pa' => 'Spanish (Panama)',			'es-py' => 'Spanish (Paraguay)',			'es-pe' => 'Spanish (Peru)',			'es-pr' => 'Spanish (Puerto Rico)',			'es-us' => 'Spanish (United States)',			'es-uy' => 'Spanish (Uruguay)',			'es-ve' => 'Spanish (Venezuela)',			'es-es' => 'Spanish (Spain)',			'es' => 'Spanish',			'sx' => 'Sutu',			'sw' => 'Swahili',			'sv-fi' => 'Swedish (Finland)',			'sv-se' => 'Swedish (Sweden)',			'sv' => 'Swedish',			'syr' => 'Syriac',			'ta' => 'Tamil',			'tt' => 'Tatar',			'te' => 'Telugu',			'th' => 'Thai',			'ts' => 'Tsonga',			'tn' => 'Tswana',			'tr' => 'Turkish',			'uk' => 'Ukrainian',			'ur' => 'Urdu',			'uz' => 'Uzbek',			'vi' => 'Vietnamese',			'xh' => 'Xhosa',			'yi' => 'Yiddish',			'zu' => 'Zulu' );					return $languages;			} //end get_Languages() function}		?>