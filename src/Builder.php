<?php

namespace Reef;

use Symfony\Component\Yaml\Yaml;

class Builder {
	
	private $Reef;
	
	
	/**
	 * Constructor
	 */
	public function __construct(Reef $Reef) {
		$this->Reef = $Reef;
	}
	
	public function generateBuilderHtml(Form $Form) {
		$a_componentMapping = $this->Reef->getComponentMapper()->getMapping();
		$a_categories = [];
		
		foreach($a_componentMapping as $s_name => $Component) {
			$a_definition = $Component->getDefinition();
			
			$s_templateDir = null;
			$s_viewfile = 'view/'.$Form->getFormConfig()['layout']['name'].'/form.mustache';
			
			$a_classes = $Component->getInheritanceList();
			foreach($a_classes as $s_class) {
				if(file_exists($s_class::getDir() . $s_viewfile)) {
					$s_templateDir = $s_class::getDir();
					break;
				}
			}
			
			if($s_templateDir === null) {
				throw new \Exception("Could not find form template file for component '".$a_definition['vendor'].':'.$a_definition['name']."'.");
			}
			
			$s_html = file_get_contents($s_templateDir.$s_viewfile);
			
			if(!isset($a_categories[$a_definition['category']])) {
				$a_categories[$a_definition['category']] = [
					'category' => $a_definition['category'],
					'components' => [],
				];
			}
			
			$a_configDeclaration = [
				'locale' => [
					
				],
				'submissions' => [
					'type' => 'none',
				],
				'main_var' => 'form_data',
				'layout' => [
					'name' => 'bootstrap4',
					'col_left' => 'col-12',
					'col_right' => 'col-12',
				],
				'fields' => $a_definition['declaration']['fields'],
			];
			
			$ComponentForm = $this->Reef->newForm();
			$ComponentForm->importDeclaration($a_configDeclaration);
			$s_form = $ComponentForm->generateFormHtml();
			
			$a_categories[$a_definition['category']]['components'][] = [
				'definition' => base64_encode(json_encode($a_definition)),
				'html' => base64_encode($s_html),
				'image' => $a_definition['image'],
				'name' => $a_definition['name'],
				'componentForm' => $s_form,
			];
			
		}
		$a_categories = array_values($a_categories);
		
		$a_helpers = [];
		$a_helpers['CSSPRFX'] = $this->Reef->getOption('css_prefix');
		
		$Mustache = new \Mustache_Engine([
			'helpers' => $a_helpers,
		]);
		
		$Mustache->setLoader(new \Mustache_Loader_FilesystemLoader(__DIR__));
		$Template = $Mustache->loadTemplate('view/'.$Form->getFormConfig()['layout']['name'].'/builder.mustache');
		
		$s_html = $Template->render([
			'categories' => $a_categories,
		]);
		
		return $s_html;
	}
	
}
