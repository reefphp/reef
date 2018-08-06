<?php

namespace Reef;

/**
 * Return a subset of an array, defined by its keys.
 * E.g. array_subset(['a'=>1, 'b'=>2, 'c'=>3], ['a','c']) = ['a'=>1, 'c'=>3]
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
