<?php

namespace Reef;

use Symfony\Component\Yaml\Yaml;
use Reef\Components\Component;

class Updater {
	
	public function __construct() {
	}
	
	private function computeUpdatePlan(Form $Form1, Form $Form2, $a_fieldRenames) {
		$a_create = $a_update = $a_delete = [];
		
		$a_fields1 = $Form1->getValueFieldsByName();
		$a_fields2 = $Form2->getValueFieldsByName();
		
		foreach($a_fields1 as $s_fieldName1 => $Field1) {
			$s_fieldName2 = null;
			
			if(isset($a_fieldRenames[$s_fieldName1])) {
				$s_fieldName2 = $a_fieldRenames[$s_fieldName1];
				
				if($s_fieldName2 !== null && (!isset($a_fields2[$s_fieldName2]) || get_class($a_fields1[$s_fieldName1]) != get_class($a_fields2[$s_fieldName2]))) {
					throw new \Exception("Invalid rename from '".$s_fieldName1."' to '".$s_fieldName2."'.");
				}
			}
			else {
				if(isset($a_fields2[$s_fieldName1])) {
					$s_fieldName2 = $s_fieldName1;
				}
			}
			
			if($s_fieldName2 === null) {
				$a_delete[$s_fieldName1] = null;
			}
			else {
				$a_update[$s_fieldName1] = $s_fieldName2;
			}
		}
		
		foreach($a_fields2 as $s_fieldName2 => $Field2) {
			if(!in_array($s_fieldName2, $a_update)) {
				$a_create[] = $s_fieldName2;
			}
		}
		
		return [$a_create, $a_update, $a_delete];
	}
	
	public function computeCompatibility(Form $Form1, Form $Form2, $a_fieldRenames = []) {
		
		[$a_create, $a_update, $a_delete] = $this->computeUpdatePlan($Form1, $Form2, $a_fieldRenames);
		
		$a_fields1 = $Form1->getValueFieldsByName();
		$a_fields2 = $Form2->getValueFieldsByName();
		
		$b_needsValueUpdate = false;
		$a_dataLoss = [];
		
		foreach($a_update as $s_fieldName1 => $s_fieldName2) {
			
			$Field1 = $a_fields1[$s_fieldName1];
			$Field2 = $a_fields2[$s_fieldName2];
			
			if($Field2->needsValueUpdate($Field1, $b_dataLoss)) {
				$b_needsValueUpdate = true;
			}
			
			if($b_dataLoss !== false) {
				$a_dataLoss[] = [
					'field' => $s_fieldName2,
					'unknown' => ($b_dataLoss === null),
				];
			}
		}
		
		return [$b_needsValueUpdate, $a_dataLoss];
	}
	
	public function update(Form $Form1, Form $Form2, $a_fieldRenames = []) : Form {
		
		[$a_create, $a_update, $a_delete] = $this->computeUpdatePlan($Form1, $Form2, $a_fieldRenames);
		
		$a_fields1 = $Form1->getValueFieldsByName();
		$a_fields2 = $Form2->getValueFieldsByName();
		
		$a_valueUpdate = [];
		$b_needsValueUpdate = false;
		foreach($a_update as $s_fieldName1 => $s_fieldName2) {
			
			$Field1 = $a_fields1[$s_fieldName1];
			$Field2 = $a_fields2[$s_fieldName2];
			
			$a_valueUpdate[$s_fieldName2] = $Field2->needsValueUpdate($Field1);
			$b_needsValueUpdate = $a_valueUpdate[$s_fieldName2] || $b_needsValueUpdate;
		}
		
		if($b_needsValueUpdate || true) {
			
			$s_storageName = $Form1->getStorageName();
			$Form2->setStorageName('__tmp__'.$s_storageName);
			
			foreach($Form1->getSubmissionIds() as $i_submissionId) {
				$Submission1 = $Form1->getSubmission($i_submissionId);
				
				$Submission2 = $Form2->newSubmission();
				$Submission2->emptySubmission();
				
				foreach($a_update as $s_fieldName1 => $s_fieldName2) {
					$Field1 = $a_fields1[$s_fieldName1];
					$Field2 = $a_fields2[$s_fieldName2];
					
					$Value1 = $Submission1->getFieldValue($s_fieldName1);
					$Value2 = $Submission2->getFieldValue($s_fieldName2);
					
					if($a_valueUpdate[$s_fieldName2]) {
						$Value2->fromUpdate($Value1);
					}
					else {
						$Value2->fromFlat($Value1->toFlat());
					}
				}
				
				$Submission2->saveAs($Submission1->getSubmissionId());
			}
			
			$i_formId = $Form1->getFormId();
			$Form1->delete();
			$Form2->setStorageName($s_storageName);
			if($i_formId !== null) {
				$Form2->saveAs($i_formId);
			}
			else {
				$i_formId = $Form2->save();
			}
			
			$Form = $Form2;
		}
		else {
			$Form = $Form1;
		}
		
		return $Form;
	}
}
