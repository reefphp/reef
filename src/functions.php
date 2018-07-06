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
