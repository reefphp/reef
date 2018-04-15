<?php

namespace Reef\Storage;

interface Storage {
	
	public function insert(array $a_data) : int;
	
	public function update(int $i_entryId, array $a_data);
	
	public function delete(int $i_entryId);
	
	public function get(int $i_entryId) : array;
	
}
