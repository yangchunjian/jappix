<?php

/*

Jappix - An open social platform
These are the PHP functions for Jappix Get API

~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~

License: AGPL
Authors: Valérian Saliou, Mathieui, Olivier M.
Contact: http://project.jappix.com/contact
Last revision: 05/11/10

*/

// The function to get the cached content
function readCache($hash) {
	return file_get_contents(PHP_BASE.'/store/cache/'.$hash.'.cache');
}

// The function to generate a cached file
function genCache($string, $mode, $cache) {
	if(!$mode) {
		$cache_dir = PHP_BASE.'/store/cache';
		$file_put = $cache_dir.'/'.$cache.'.cache';
		
		// Cache not yet wrote
		if(is_dir($cache_dir) && !file_exists($file_put))
			file_put_contents($file_put, $string);
	}
}

// The function to compress the CSS
function compressCSS($buffer) {
	// We remove the comments
	$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
	
	// We remove the useless spaces
	$buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '	 ', '	 '), '', $buffer);
	
	// We remove the last useless spaces
	$buffer = str_replace(array(' { ',' {','{ '), '{', $buffer);
	$buffer = str_replace(array(' } ',' }','} '), '}', $buffer);
	$buffer = str_replace(array(' : ',' :',': '), ':', $buffer);
 	
	return $buffer;
}

// The function to replace classical path to get.php paths
function setPath($string, $hash, $host, $type, $locale) {
	// Initialize the static server path
	$static = '.';
	
	// Replace the JS strings
	if($type == 'js') {
		// Static host defined
		if($host != '.')
			$static = $host;
		
		// Links to JS (must have a lang parameter)
		$string = preg_replace('/((\")|(\'))(\.\/)(js)(\/)(\S+)(js)((\")|(\'))/', '$1'.$static.'/php/get.php?h='.$hash.'&l='.$locale.'&t=$5&f=$7$8$9', $string);
		
		// Other "normal" links (no lang parameter)
		$string = preg_replace('/((\")|(\'))(\.\/)(css|img|store|snd)(\/)(\S+)(css|png|jpg|jpeg|gif|bmp|ogg|oga)((\")|(\'))/', '$1'.$static.'/php/get.php?h='.$hash.'&t=$5&f=$7$8$9', $string);
	}
	
	// Replace the CSS strings
	else if($type == 'css') {
		// Static host defined
		if($host != '.')
			$static = $host.'/php';
		
		$string = preg_replace('/(\(\.\.\/)(css|js|img|store|snd)(\/)(\S+)(css|js|png|jpg|jpeg|gif|bmp|ogg|oga)(\))/', '('.$static.'/get.php?h='.$hash.'&t=$2&f=$4$5)', $string);
	}
	
	return $string;
}

// The function to set the good translation to a JS file
function setTranslation($string) {
	return preg_replace('/_e\("([^\"\"]+)"\)/e', "'_e(\"'.T_gettext('$1').'\")'", $string);
}

// The function to set the good configuration to a JS file
function setConfiguration($string, $locale, $version) {
	// Configuration array
	$array = array();
	
	// xml:lang
	$array['XML_LANG'] = $locale;
	
	// Jappix parameters
	$array['JAPPIX_LOCATION'] = jappixLocation();
	$array['JAPPIX_VERSION'] = $version;
	
	// Main configuration
	$array['SERVICE_NAME'] = SERVICE_NAME;
	$array['SERVICE_DESC'] = SERVICE_DESC;
	$array['JAPPIX_RESOURCE'] = JAPPIX_RESOURCE;
	$array['LOCK_HOST'] = LOCK_HOST;
	$array['ANONYMOUS'] = ANONYMOUS;
	$array['HTTPS_STORAGE'] = HTTPS_STORAGE;
	$array['ENCRYPTION'] = ENCRYPTION;
	$array['COMPRESSION'] = COMPRESSION;
	$array['DEVELOPER'] = DEVELOPER;
	
	// Hosts configuration
	$array['HOST_MAIN'] = HOST_MAIN;
	$array['HOST_MUC'] = HOST_MUC;
	$array['HOST_VJUD'] = HOST_VJUD;
	$array['HOST_ANONYMOUS'] = HOST_ANONYMOUS;
	$array['HOST_BOSH'] = HOST_BOSH;
	$array['HOST_STATIC'] = HOST_STATIC;
	
	// Apply it!
	foreach($array as $array_key => $array_value)
		$string = preg_replace('/var '.$array_key.'(( )?=( )?)null;/', 'var '.$array_key.'$1\''.addslashes($array_value).'\';', $string);
	
	return $string;
}

// The function to set the background
function setBackground($string) {
	// Get the default values
	$array = defaultBackground();
	
	// Read the background configuration
	$xml = readXML('conf', 'background');
	
	if($xml) {
		$read = new SimpleXMLElement($xml);
		
		foreach($read->children() as $child) {
			// Any value?
			if($child)
				$array[$child->getName()] = $child;
		}
	}
	
	$css = '';
	
	// Generate the CSS code
	switch($array['type']) {
		// Image
		case 'image':
			$css .= 
	"\n".'	background-image: url(../store/backgrounds/'.urlencode($array['image_file']).');
	background-repeat: '.$array['image_repeat'].';
	background-position: '.$array['image_horizontal'].' '.$array['image_vertical'].';
	background-color: '.$array['image_color'].';'
			;
			
			// Add CSS code to adapt the image?
			if($array['image_adapt'] == 'on')
				$css .= 
	'	background-attachment: fixed;
	background-size: cover;
	background-size: cover;
	-moz-background-size: cover;
	-webkit-background-size: cover;';
			
			$css .= "\n";
			
			break;
		
		// Color
		case 'color':
			$css .= "\n".'	background-color: '.$array['color_color'].';'."\n";
			
			break;
		
		// Default: use the filtering regex
		default:
			$css .= '$3';
			
			break;
	}
	
	// Apply the replacement!
	return preg_replace('/(\.body-images( )?\{)([^\{\}]+)(\})/i', '$1'.$css.'$4', $string);
}

?>
