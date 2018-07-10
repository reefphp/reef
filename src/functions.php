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
 * @param bool   $b_rmHidden  Whether to delete hidden files
 *
 * @return bool TRUE on success, otherwise FALSE
 */
function rmTree($s_dir, $b_rmRoot = false, $b_rmHidden = false) {
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
		if(!$b_rmHidden) {
			// Keep hidden files
			if(substr($fileinfo->getFilename(), 0, 1) == '.') {
				continue;
			}
			// Keep a directory if it is not empty; this is (presumably?) if there are still hidden files present
			if($fileinfo->isDir()) {
				$a_files = glob($fileinfo->getRealPath().'/*');
				if($a_files !== false && count($a_files) > 0) {
					continue;
				}
			}
		}
		if (!$s_action($fileinfo->getRealPath())) {
			return false;
		}
	}
	
	// Delete the root folder itself?
	return ($b_rmRoot ? rmdir($s_dir) : true);
}
