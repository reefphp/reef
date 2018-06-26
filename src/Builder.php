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
		
		$a_locales = ['en_US'];
		
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
				'locale' => $a_definition['declaration']['locale']??[],
				'submissions' => [
					'type' => 'none',
				],
				'main_var' => 'form_data[config]',
				'layout' => [
					'name' => 'bootstrap4',
					'col_left' => 'col-12',
					'col_right' => 'col-12',
				],
				'fields' => $a_definition['declaration']['fields']??[],
			];
			
			$ComponentForm = $this->Reef->newForm();
			$ComponentForm->importDeclaration($a_configDeclaration);
			$s_form = $ComponentForm->generateFormHtml();
			
			$a_localeForms = [];
			foreach($a_locales as $s_locale) {
				if(empty($s_locale)) {
					$s_locale = '-';
				}
				
				$a_localeTitles = $a_definition['locale'];
				$a_locale = $Component->getLocale($s_locale);
				
				$a_fields = [];
				foreach($a_locale as $s_name => $s_val) {
					$a_fields[] = [
						'component' => 'reef:single_line_text',
						'name' => $s_name,
						'locale' => [
							'title' => $a_localeTitles[$s_name]??$s_name
						],
						'default' => $s_val,
					];
				}
				
				$a_localeDeclaration = [
					'locale' => [],
					'submissions' => [
						'type' => 'none',
					],
					'main_var' => 'form_data[locale]['.$s_locale.']',
					'layout' => [
						'name' => 'bootstrap4',
						'col_left' => 'col-12',
						'col_right' => 'col-12',
					],
					'fields' => $a_fields,
				];
				
				$LocaleForm = $this->Reef->newForm();
				$LocaleForm->importDeclaration($a_localeDeclaration);
				$a_localeForms[] = [
					'locale' => $s_locale,
					'form' => $LocaleForm->generateFormHtml(),
				];
			}
			
			$a_categories[$a_definition['category']]['components'][] = [
				'definition' => base64_encode(json_encode($a_definition)),
				'html' => base64_encode($s_html),
				'image' => $a_definition['image'],
				'name' => $a_definition['name'],
				'componentForm' => $s_form,
				'localeForms' => $a_localeForms,
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
			'formConfig' => base64_encode(json_encode(array_subset($Form->getFormConfig(), ['locale', 'layout']))),
		]);
		
		return $s_html;
	}
	
}
