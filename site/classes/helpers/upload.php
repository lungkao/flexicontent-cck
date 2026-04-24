<?php
defined( '_JEXEC' ) or die( 'Restricted access' );
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Language\LanguageFactoryInterface;

class flexicontent_upload
{
	static function makeSafe($file, $language = null)
	{
		// Replace [] with () and multiple space with a single hyphen '-', but firest remove any leading / trailing spaces
		$file = trim($file);
		$file = str_replace('[', '(', $file);
		$file = str_replace(']', ')', $file);
		$file = preg_replace('![\s]+!', '-', $file);

		// Replace $*"[]:;|/ with dash after removing any leading / trailing spaces
		$file = preg_replace('![\$\*\"\[\]\:\;\|\/]]+!', '_', $file);

		// Remove any trailing dots, as those aren't ever valid file names
		$file = rtrim($file, '.');

		// Regex for replacing non safe characters
		$regex = array('#(\.){2,}#', '#[^A-Za-z0-9\(\)\.\_\-]#', '#^\.#');

		// Language transliteration should include given language, and also site + admin defaults (most useful is site default)
		$lang_params = \Joomla\CMS\Component\ComponentHelper::getParams('com_languages');
		$lang_site_default  = $lang_params->get('site', '*');
		$lang_admin_default = $lang_params->get('admin', '*');

		$langs[$language]   = $language && $language != '*';
		$langs[$lang_site_default]  = $lang_site_default != '*';
		$langs[$lang_admin_default] = $lang_admin_default != '*';

		// Try to transliterate according to given language and site + admin default languages
		$file_safe = false;
		$transformed = '';

		foreach($langs as $language => $do)
		{
			if ($do)
			{
				$transformed = \Joomla\CMS\Factory::getContainer()->get(LanguageFactoryInterface::class)->createLanguage($language, false)->transliterate($file);
				$file_safe = $transformed ? preg_replace($regex, '', $transformed) : false;

				// Stop trying transliterations if a complete job was done
				if ($transformed && $transformed == $file_safe)
				{
					break;
				}
				$file_safe = false;
			}
		}

		// Finally if none of transliterations did a good enough job (if less than 50% of file remained)
		// (It could be because of wrong language(s) tried)
		// then avoid bad looking filenames by using current time
		if ( !$file_safe )
		{
			if (strlen($transformed) < 0.5 * strlen($file))
			{
				$ext = self::getExt($file);
				$file_safe = date('Y-m-d_H.i.s') .'.'. $ext;
			}
			else
			{
				$file_safe = $transformed;
			}
		}

		// Return filename that is filesystem safe
		return $file_safe;
	}


	static function parseByteLimit($limit)
	{
		if (is_numeric($limit)) return $limit;  // already in bytes
	
		$v = (int)$limit;
		$type = substr($limit, -1);
		
		switch (strtoupper($type)) {
			case 'P': $v *= 1024;
			case 'T':	$v *= 1024;
			case 'G':	$v *= 1024;
			case 'M': $v *= 1024;
			case 'K': $v *= 1024;
			break;
		}
		return $v;
	}


	/**
	 * Gets upload Limits
	 *
	 * @return array with limits
	 * @since 3.0
	 */
	static function getPHPuploadLimit()
	{
		$post_max   = flexicontent_upload::parseByteLimit(ini_get('post_max_size'));
		$upload_max = flexicontent_upload::parseByteLimit(ini_get('upload_max_filesize'));
		if ($upload_max < $post_max) {
			$limit = array('value'=>$upload_max, 'name'=>'upload_max_filesize');
		}
		else {
			$limit = array('value'=>$post_max, 'name'=>'post_max_size');
		}
		// Sucosin limitation
		if (extension_loaded('suhosin')) {
			$post_max = flexicontent_upload::parseByteLimit(ini_get('suhosin.post.max_value_length'));
			if ($post_max < $limit['value']) $limit = array('value'=>$post_max, 'name'=>'suhosin.post.max_value_length');
		}
		return $limit;
	}


	/**
	 * Gets the extension of a file name
	 *
	 * @param string $file The file name
	 * @return string | boolean  The file extension or false if file does not have extension
	 * @since 1.5
	 */
	static function getExt($file)
	{
		$dot = strrpos($file, '.');

		if ($dot === false)
		{
			return '';
		}

		return (string) substr($file, $dot + 1);
		//return pathinfo($file, PATHINFO_EXTENSION);
	}


	/**
	 * Classify an extension into a file-type family.
	 *
	 * @param   string  $ext  file extension (without dot)
	 * @return  string        one of: video, audio, image, pdf, doc, xls, ppt, zip, code, default
	 */
	public static function fileTypeClass($ext)
	{
		$ext = strtolower((string) $ext);
		$map = [
			'video' => ['mp4','mp4v','mpeg','mpg','mov','webm','mkv','avi','m4v','flv','wmv','ogv','3gp'],
			'audio' => ['mp3','m4a','mp4a','ogg','wav','aac','flac','opus','wma','aiff'],
			'image' => ['jpg','jpeg','png','gif','webp','bmp','ico','svg','tif','tiff'],
			'pdf'   => ['pdf'],
			'doc'   => ['doc','docx','odt','rtf','txt','md'],
			'xls'   => ['xls','xlsx','ods','csv','tsv'],
			'ppt'   => ['ppt','pptx','odp','key'],
			'zip'   => ['zip','rar','7z','tar','gz','bz2','xz'],
			'code'  => ['html','htm','css','js','php','xml','json','sql','yml','yaml','ts','jsx','tsx'],
		];
		foreach ($map as $type => $exts) {
			if (in_array($ext, $exts, true)) {
				return $type;
			}
		}
		return 'default';
	}


	/**
	 * Render a styled, colored file-type thumbnail (SVG icon + extension label).
	 * Used when a real image thumbnail is not available.
	 *
	 * @param   string  $ext       file extension (without dot)
	 * @param   int     $size      pixel size (square)
	 * @param   string  $filename  optional filename for alt text
	 * @return  string             HTML
	 */
	public static function fileTypeThumb($ext, $size = 100, $filename = '')
	{
		$ext   = strtolower((string) $ext);
		$type  = self::fileTypeClass($ext);
		$label = strtoupper($ext !== '' ? $ext : 'FILE');

		// Inline SVG icons keep this self-contained (no external font/icon dep)
		$icons = [
			'video'   => '<svg viewBox="0 0 24 24" width="100%" height="100%" fill="currentColor"><path d="M4 6h12v12H4z" opacity=".35"/><path d="M2 5v14a1 1 0 0 0 1.5.87L9 16.5V7.5L3.5 4.13A1 1 0 0 0 2 5zm9 1v12h10a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H11z"/></svg>',
			'audio'   => '<svg viewBox="0 0 24 24" width="100%" height="100%" fill="currentColor"><path d="M12 3v10.55A4 4 0 1 0 14 17V7h4V3z"/></svg>',
			'image'   => '<svg viewBox="0 0 24 24" width="100%" height="100%" fill="currentColor"><path d="M21 5v14H3V5zm-2 2H5v8l4-4 4 4 2-2 4 4z"/><circle cx="8" cy="10" r="1.5"/></svg>',
			'pdf'     => '<svg viewBox="0 0 24 24" width="100%" height="100%" fill="currentColor"><path d="M6 2h9l5 5v15H6z" opacity=".2"/><path d="M14 2H6v20h14V8zM6 20V4h8v5h5v11zm3-7h6v1H9zm0 3h6v1H9z"/></svg>',
			'doc'     => '<svg viewBox="0 0 24 24" width="100%" height="100%" fill="currentColor"><path d="M14 2H6v20h14V8zM6 20V4h8v5h5v11zM8 11h9v1H8zm0 3h9v1H8zm0 3h6v1H8z"/></svg>',
			'xls'     => '<svg viewBox="0 0 24 24" width="100%" height="100%" fill="currentColor"><path d="M14 2H6v20h14V8zM6 20V4h8v5h5v11zM8 11h3v2H8zm5 0h3v2h-3zm-5 3h3v2H8zm5 0h3v2h-3z"/></svg>',
			'ppt'     => '<svg viewBox="0 0 24 24" width="100%" height="100%" fill="currentColor"><path d="M14 2H6v20h14V8zM6 20V4h8v5h5v11zM8 11h8v5H8z"/></svg>',
			'zip'     => '<svg viewBox="0 0 24 24" width="100%" height="100%" fill="currentColor"><path d="M14 2H6v20h14V8zM6 20V4h6v2h2V4h0v5h5v11zm7-14h-2v2h2zm-2 2h-2v2h2zm2 2h-2v2h2zm-2 2h-2v2h2zm1 3h-2l-.5 3h3z"/></svg>',
			'code'    => '<svg viewBox="0 0 24 24" width="100%" height="100%" fill="currentColor"><path d="M9 16l-4-4 4-4 1.4 1.4L7.8 12l2.6 2.6zm6 0l-1.4-1.4L16.2 12l-2.6-2.6L15 8l4 4z"/></svg>',
			'default' => '<svg viewBox="0 0 24 24" width="100%" height="100%" fill="currentColor"><path d="M14 2H6v20h14V8zM6 20V4h8v5h5v11z"/></svg>',
		];
		$icon_svg = $icons[$type] ?? $icons['default'];

		$size = (int) $size;
		$icon_size = max(24, (int) round($size * 0.42));
		$font_size = max(10, (int) round($size * 0.13));
		$alt = htmlspecialchars($filename ?: $label, ENT_COMPAT, 'UTF-8');

		return '<div class="fc-file-thumb ft-' . $type . '" title="' . $alt . '" style="width:' . $size . 'px;height:' . $size . 'px;">'
			. '<span class="fc-ft-icon" style="width:' . $icon_size . 'px;height:' . $icon_size . 'px;">' . $icon_svg . '</span>'
			. '<span class="fc-ft-ext" style="font-size:' . $font_size . 'px;">' . htmlspecialchars($label, ENT_COMPAT, 'UTF-8') . '</span>'
			. '</div>';
	}


	/**
	 * Checks uploaded file
	 *
	 * @param string $file The file name
	 * @param string $err  Set (return) the error string in it
	 * @param string $file view 's parameters
	 * @return string The file extension
	 * @since 1.5
	 */
	static function check(&$file, &$err, &$params, $language=null)
	{
		if (!$params)
		{
			$params = \Joomla\CMS\Component\ComponentHelper::getParams( 'com_flexicontent' );
		}


		// ************************
		// Check non-empty filename
		// ************************
		
		if(empty($file['name']))
		{
			$err = 'FLEXI_PLEASE_INPUT_A_FILE';
			return false;
		}



		// ***************************************************************
		// Make filename safe, transliterating according to given language
		// ***************************************************************
		
		$language = $language ? $language : (!empty($file['language']) ? $file['language'] : '*');   // * would usually be interpretted as frontend site default language
		$file['name'] = flexicontent_upload::makeSafe($file['name'], $language);


		// ***************************************
		// Check if the image file type is allowed
		// ***************************************
		
		$format = strtolower(flexicontent_upload::getExt($file['name'])?? '');

		$allowed_exts = preg_split("/[\s]*,[\s]*/", strtolower($params->get('upload_extensions', 'bmp,wbmp,csv,doc,docx,webp,gif,ico,jpg,jpeg,odg,odp,ods,odt,pdf,png,ppt,pptx,txt,xcf,xls,xlsx,zip,ics') ?? ''));
		$allowed_exts = array_flip($allowed_exts);

		$ignored_exts = preg_split("/[\s]*,[\s]*/", strtolower($params->get('ignore_extensions') ?? ''));
		$ignored_exts = array_flip($ignored_exts);

		if (!isset($allowed_exts[$format]) && !isset($ignored_exts[$format]))
		{
			$err = 'FLEXI_WARNFILETYPE';
			return false;
		}


		// **************
		// Check filesize
		// **************
		
		$maxSize = (int) $params->get( 'upload_maxsize', 0 );
		if ($maxSize > 0 && (int) $file['size'] > $maxSize)
		{
			$err = 'FLEXI_WARNFILETOOLARGE';
			return false;
		}


		// **********************************************
		// Check extension and mime type are both allowed
		// **********************************************
		
		$imginfo = null;
		$images = explode( ',', $params->get( 'image_extensions', 'bmp,wbmp,gif,jpg,jpeg,png,webp,ico' ));
		
		if ($params->get('restrict_uploads', 1) )
		{
			if (in_array($format, $images))  // if its an image run it through getimagesize
			{
				if (($imginfo = getimagesize($file['tmp_name'])) === FALSE)
				{
					$err = 'FLEXI_WARNINVALIDIMG';
					return false;
				}

			}
			
			else if ( !isset($ignored_exts[$format]) )
			{
				// if its not an image...and we're not ignoring it
				$allowed_mime = explode(',', $params->get('upload_mime'));
				$illegal_mime = explode(',', $params->get('upload_mime_illegal'));

				if (function_exists('finfo_open') /*&& $params->get('check_mime',1)*/)
				{
					// We have fileinfo
					$finfo = finfo_open(FILEINFO_MIME);
					$type = finfo_file($finfo, $file['tmp_name']);
					if(strlen($type) && !in_array($type, $allowed_mime) && in_array($type, $illegal_mime)) {
						$err = 'FLEXI_WARNINVALIDMIME';
						return false;
					}
					finfo_close($finfo);

				}
				else if(function_exists('mime_content_type') /*&& $params->get('check_mime',1)*/)
				{
					// we have mime magic
					$type = mime_content_type($file['tmp_name']);

					if(strlen($type) && !in_array($type, $allowed_mime) && in_array($type, $illegal_mime)) {
						$err = 'FLEXI_WARNINVALIDMIME';
						return false;
					}

				}
			}
		}


		// ***************************
		// Check for XSS safe contents
		// ***************************
		
		$xss_check = file_get_contents($file['tmp_name'], false, null, 0, 256);
		$html_tags = array('abbr','acronym','address','applet','area','audioscope','base','basefont',
			'bdo','bgsound','big','blackface','blink','blockquote','body','bq','br','button','caption',
			'center','cite','code','col','colgroup','comment','custom','dd','del','dfn','dir','div','dl','dt',
			'em','embed','fieldset','fn','font','form','frame','frameset','h1','h2','h3','h4','h5','h6','head',
			'hr','html','iframe','ilayer','img','input','ins','isindex','keygen','kbd','label','layer','legend',
			'li','limittext','link','listing','map','marquee','menu','meta','multicol','nobr','noembed','noframes',
			'noscript','nosmartquotes','object','ol','optgroup','option','param','plaintext','pre','rt','ruby','s','samp',
			'script','select','server','shadow','sidebar','small','spacer','span','strike','strong','style','sub','sup','table',
			'tbody','td','textarea','tfoot','th','thead','title','tr','tt','ul','var','wbr','xml','xmp','!DOCTYPE', '!--');
		foreach($html_tags as $tag)
		{
			// A tag is '<tagname ', so we need to add < and a space or '<tagname>'
			if(stristr($xss_check, '<'.$tag.' ') || stristr($xss_check, '<'.$tag.'>'))
			{
				$err = 'FLEXI_WARNIEXSS';
				return false;
			}
		}
		
		return true;
	}


	/**
	* Sanitize the file name allowing only filesystem-safe characters, and return an unique filename for the given folder
	*
	* @since 1.0
	*
	* @param string $base_Dir the target directory
	* @param string $filename the unsanitized imagefile name
	*
	* @return string $filename the sanitized and unique file name
	*/
	static function sanitize($base_Dir, $filename)
	{

		// Check for any trailing dots and remove them (trailing shouldn't be possible cause of the getExt check)
		$filename = rtrim($filename, '.');

		// Replace invalid characters with dash, if makeSafe has been called then this has already been done
		$regex = array('#(\.){2,}#', '#[^A-Za-z0-9\.\_\- ]#', '#^\.#');
		$filename = trim(preg_replace($regex, '-', $filename));

		// Get name part and extension part from the file name
		$lastdotpos = strrpos( $filename, '.' );
		$name = substr( $filename, 0, $lastdotpos );
		$ext  = substr( $filename, $lastdotpos + 1 );

		// Make a unique filename by checking if it is already taken, if already taken keep incrementing counter till finding a new name
		if (file_exists( $base_Dir . $name . '.' . $ext ))
		{
			$unique_num = 1;
			while( file_exists( $base_Dir . $name . '-' . $unique_num . '.' . $ext ) )
			{
				$unique_num++;
			}

			// Create new filename out of the name and ext parts adding the unique number to it
			$filename = $name . '-' . $unique_num . '.' . $ext;
		}

		return $filename;
	}


	/**
	* Sanitize folders and return an unique string
	*
	* @since 1.5
	*
	* @param string $base_Dir the target directory
	* @param string $foler the unsanitized folder name
	*
	* @return string $foldername the sanitized and unique file name
	*/
	static function sanitizedir($base_Dir, $folder)
	{

		// Replace invalid characters with dash, if makeSafe has been called then this has already been done
		$regex = array('#(\.){2,}#', '#[^A-Za-z0-9\.\_\- ]#', '#^\.#');
		$folder = trim(preg_replace($regex, '-', $folder));
		$foldername = $folder;

		//make a unique folder name for the image and check it is not already taken
		if (is_dir( $base_Dir . $folder ))
		{
			$unique_num = 1;
			while( is_dir( $base_Dir . $folder . '-' . $unique_num ))
			{
				$unique_num++;
			}

			// Create new folder name appending the unique number to it
			$foldername = $folder . '-' . $unique_num;
		}

		return $foldername;
	}
}
