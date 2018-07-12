<?php

namespace Reef;

class TempForm extends Form {
	
	public function newSubmission() {
		return new TempSubmission($this);
	}
	
}
