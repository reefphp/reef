<?php

namespace Reef\Exception;

class ValidationException extends RuntimeException {
	private $a_errors;
	
	public function __construct(array $a_errors, int $i_code = 0, \Throwable $previous = null) {
		
		$this->a_errors = $a_errors;
		$s_message = '';
		
		$b_first = true;
		foreach($a_errors as $s_name => $m_subErrors) {
			if(is_array($m_subErrors)) {
				$m_subErrors = array_map(function($m_error) {
					return is_array($m_error) ? '[Multiple errors]' : $m_error;
				}, $m_subErrors);
				
				if(!$b_first) {
					$s_message .= '; ';
				}
				
				$s_message .= '('.$s_name.') '.implode(', ', $m_subErrors);
			}
			else {
				$s_message .= '('.$s_name.') '.$m_subErrors;
			}
		}
		
		parent::__construct($s_message, $i_code, $previous);
	}
	
	public function getErrors() {
		return $this->a_errors;
	}
}
