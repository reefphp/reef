<?php

namespace Reef;

/**
 * Return a subset of an array, defined by its keys.
 * E.g. array_subset(['a'=>1, 'b'=>2, 'c'=>3], ['a','c']) = ['a'=>1, 'c'=>3]
 * @param array $a_haystack Associative source array
 * @param array $a_needles The keys from $a_haystack to retrieve
 * @return array The subset
 */
function array_subset(array $a_haystack, array $a_needles) {
	return array_intersect_key($a_haystack, array_flip($a_needles));
}

/**
 * Return a unique id
 * @return string A unique hexadecimal string
 */
function unique_id() {
	return bin2hex(random_bytes(16));
}

/**
 * Return the first key of an array without resetting its pointer
 * @param array The array
 * @return mixed The first key
 */
function array_first_key(array $a_array) {
	reset($a_array);
	return key($a_array);
}

/**
 * Recursively deletes a directory tree
 *
 * @param string $s_dir       The directory path
 * @param bool   $b_rmRoot    Whether to delete the directory itself
 *
 * @return bool TRUE on success, otherwise FALSE
 */
function rmTree($s_dir, $b_rmRoot = false) {
	// Handle trivial arguments
	if (empty($s_dir) || !file_exists($s_dir)) {
		return true;
	}
	elseif (is_file($s_dir) || is_link($s_dir)) {
		return unlink($s_dir);
	}
	
	// Delete all children
	$a_files = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator($s_dir, \RecursiveDirectoryIterator::SKIP_DOTS),
		\RecursiveIteratorIterator::CHILD_FIRST
	);
	
	foreach ($a_files as $fileinfo) {
		$s_action = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
		
		if (!$s_action($fileinfo->getRealPath())) {
			return false;
		}
	}
	
	// Delete the root folder itself?
	return ($b_rmRoot ? rmdir($s_dir) : true);
}

/**
 * Interpret a boolean value. Returns false if the input is either falsey, 'false', 'no' or '0', or true otherwise
 *
 * @param mixed $m_input The input
 *
 * @return bool
 */
function interpretBool($m_input) {
	if(is_string($m_input)) {
		$m_input = strtolower($m_input);
		$m_input = ($m_input != 'false' && $m_input != 'no' && $m_input != '0');
	}
	
	return (bool)$m_input;
}

/**
 * Convert a matcher to a regular expression.
 * `*`, `?` and `_` are mapped to `.*`, `.?` and `.`, respectively
 * This function has an equivalent in javascript that should be kept identical to this implementation
 * @param string $s_matcher The matcher string
 * @return string The regular expression
 */
function matcherToRegExp(string $s_matcher) : string {
	
	$s_regexp = preg_quote($s_matcher, '/');
	
	$s_regexp = preg_replace_callback('/((?:\\\\)*)\\\\\\*/', function($a_matches) {
		$i_slashes = strlen($a_matches[1]);
		return substr($a_matches[1], 0, $i_slashes/2) . (($i_slashes % 4 == 0) ? '.*' : '*');
	}, $s_regexp);
	
	$s_regexp = preg_replace_callback('/((?:\\\\)*)\\\\\\?/', function($a_matches) {
		$i_slashes = strlen($a_matches[1]);
		return substr($a_matches[1], 0, $i_slashes/2) . (($i_slashes % 4 == 0) ? '.?' : '?');
	}, $s_regexp);
	
	$s_regexp = preg_replace_callback('/((?:\\\\)*)_/', function($a_matches) {
		$i_slashes = strlen($a_matches[1]);
		return substr($a_matches[1], 0, floor($i_slashes/4)*2) . (($i_slashes % 4 == 0) ? '.' : '_');
	}, $s_regexp);
	
	return '/^'.$s_regexp.'$/';
}

/**
 * Parse a filesize into bytes. Possible input:
 *  - numeric input, will be interpreted as bytes
 *  - e.g. number KiB, will be interpreted as base-1024 bytes
 *  - e.g. number MB, will be interpreted as base-$i_base bytes
 * @param string $s_size The input size
 * @param ?int $i_base The base, either 1000, 1024 or null to autodetermine
 * @return int Number of bytes
 */
function parseBytes(string $s_size, ?int $i_base) : int {
	if(is_numeric($s_size)) {
		return max((int)$s_size, 0);
	}
	
	// Drop the 'b'
	$s_size = strtolower($s_size);
	if(substr($s_size, -1) == 'b') {
		$s_size = substr($s_size, 0, -1);
	}
	
	if(is_numeric($s_size)) {
		return (int)$s_size;
	}
	
	// Get size and unit
	$s_unit = substr($s_size, -1);
	if($s_unit == 'i') {
		$i_base = 1024;
		$s_unit = substr($s_size, -1);
	}
	$i_size = (int)substr($s_size, 0, -1);
	
	return $i_size * pow($i_base??1000, strpos('bkmgtpezy', $s_unit));
}

/**
 * Format the given number of bytes into human-readable format
 * @param int $i_bytes The number of bytes to format
 * @param ?int $i_base The base, either 1000, 1024 or null for 1024
 * @return string The human-readable representation
 */
function bytes_format(int $i_bytes, ?int $i_base) : string {
	$i_base = $i_base ?? 1024;
	if($i_base !== 1000 && $i_base !== 1024) {
		$i_base = 1024;
	}
	
	if($i_bytes < $i_base) {
		return $i_bytes . ' B';
	}
	
	$s_symbols = '-KMGTPEZY';
	if($i_base == 1000) {
		// In base 1000, the kilo is lower case
		$s_symbols[1] = 'k';
	}
	
	$i_exp = (int)floor(log($i_bytes, $i_base));
	
	$s_bytes = round($i_bytes / $i_base**$i_exp, 1) . ' ';
	$s_bytes .= $s_symbols[$i_exp] . (($i_base == 1024) ? 'i' : '');
	$s_bytes .= 'B';
	
	return $s_bytes;
}
