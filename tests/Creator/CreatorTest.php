<?php

namespace tests\Form;

use PHPUnit\Framework\TestCase;
use \Reef\Exception\CreatorException;

final class CreatorTest extends TestCase {
	
	private static $Reef;
	private static $Form;
	private static $Creator;
	
	public function testCanCreateReef(): void {
		
		// Specify which components we want to use
		$Setup = new \Reef\ReefSetup(
			new \Reef\Storage\NoStorageFactory(),
			new \Reef\Layout\bootstrap4()
		);
		
		$this->assertInstanceOf(\Reef\ReefSetup::class, $Setup);
		
		static::$Reef = new \Reef\Reef(
			$Setup,
			[
			]
		);
		
		$this->assertInstanceOf(\Reef\Reef::class, static::$Reef);
	}
	
	/**
	 * @depends testCanCreateReef
	 */
	public function testCanCreateForm(): void {
		static::$Form = static::$Reef->newTempForm();
		static::$Creator = static::$Form->newCreator();
		
		static::$Creator
			->getForm()
				->setStorageName('my_storage_name')
			->addField('reef:text_line')
				->setName('input_1')
				->set('required', true)
				->setLocale('en_US', [
					'title' => 'Input 1',
				])
			->apply();
		
		$this->assertSame(1, count(static::$Form->getFields()));
		
		/*
		static::$Form->newDefinition([
			'storage_name' => 'test',
			'fields' => [
				[
					'component' => 'reef:heading',
					'size' => 4,
					'locale' => [
						'title' => 'Test form',
					],
				],
				[
					'component' => 'reef:text_line',
					'name' => 'input_1',
					'required' => true,
					'locale' => [
						'title' => 'Input 1',
					],
				],
				[
					'component' => 'reef:checkbox',
					'name' => 'input_2',
					'locale' => [
						'title_left' => 'Input 2',
					],
				],
			],
		]);*/
	}
	
	/**
	 * @depends testCanCreateForm
	 */
	public function testGetByValidName(): void {
		$Creator = static::$Form->newCreator();
		
		$Creator
			->getFieldByName('input_1')
			->get('component', $s_componentName);
		
		$this->assertSame('reef:text_line', $s_componentName);
	}
	
	/**
	 * @depends testGetByValidName
	 */
	public function testGetByInvalidName(): void {
		$Creator = static::$Form->newCreator();
		
		$this->expectException(CreatorException::class);
		
		$Creator
			->getFieldByName('some_invalid_name');
	}
	
	/**
	 * @depends testGetByInvalidName
	 */
	public function testGetByValidPosition(): void {
		$Creator = static::$Form->newCreator();
		
		$Creator
			->getFieldByPosition(1)
			->get('name', $s_fieldName);
		
		$this->assertSame('input_1', $s_fieldName);
	}
	
	/**
	 * @depends testGetByValidPosition
	 */
	public function testGetByInvalidPosition(): void {
		$Creator = static::$Form->newCreator();
		
		$this->expectException(CreatorException::class);
		
		$Creator
			->getFieldByPosition(2);
	}
	
	/**
	 * @depends testGetByInvalidPosition
	 */
	public function testGetByValidIndex(): void {
		$Creator = static::$Form->newCreator();
		
		$Creator
			->getFieldByIndex(0)
			->get('name', $s_fieldName);
		
		$this->assertSame('input_1', $s_fieldName);
	}
	
	/**
	 * @depends testGetByValidIndex
	 */
	public function testGetByInvalidIndex(): void {
		$Creator = static::$Form->newCreator();
		
		$this->expectException(CreatorException::class);
		
		$Creator
			->getFieldByIndex(1);
	}
	
	/**
	 * @depends testGetByInvalidIndex
	 */
	public function testAddInvalidComponent(): void {
		$Creator = static::$Form->newCreator();
		
		$this->expectException(CreatorException::class);
		
		$Creator
			->addField('some:inexistent_component_name');
	}
	
	/**
	 * @depends testAddInvalidComponent
	 */
	public function testUnsupportedBranching(): void {
		
		// For coverage, call getForm() twice to cover Form::getForm()
		$ComponentContext = static::$Creator
			->getForm()
			->getForm()
			->getFirstField()
				->getPosition($i_position);
		
		$FormContext = $ComponentContext->getForm();
		
		$this->expectException(CreatorException::class);
		
		$ComponentContext->getPosition($i_position);
	}
	
	/**
	 * @depends testUnsupportedBranching
	 */
	public function testDoubleNames(): void {
		
		$this->expectException(CreatorException::class);
		
		static::$Creator
			->addField('reef:text_number')
				->setName('input_1');
	}
	
	/**
	 * @depends testDoubleNames
	 */
	public function testRenaming(): void {
		
		static::$Creator
			->getFirstField()
				->setName('input_2');
		
		$this->assertSame(
			['input_1' => 'input_2'],
			static::$Creator->_getComponent()->_getFieldRenames()
		);
		
		static::$Creator
			->getFirstField()
				->set('name', 'input_3');
		
		$this->assertSame(
			['input_1' => 'input_3'],
			static::$Creator->_getComponent()->_getFieldRenames()
		);
	}
	
	/**
	 * @depends testRenaming
	 */
	public function testLocale(): void {
		
		// Test en_US set()
		$a_localeSet = [
			'name' => 'Field name',
		];
		
		static::$Creator
			->getFirstField()
				->setLocale('en_US', $a_localeSet)
				->get('locales', $a_localeGet);
		
		$this->assertSame($a_localeSet, $a_localeGet['en_US']);
		
		// Test en_US add()
		$a_localeAdd = [
			'placeholder' => 'Placeholder',
		];
		
		static::$Creator
			->getFirstField()
				->addLocale('en_US', $a_localeAdd)
				->get('locales', $a_localeGet);
		
		$this->assertSame(array_merge($a_localeSet, $a_localeAdd), $a_localeGet['en_US']);
		
		
		// Test general set()
		$a_localeSet = [
			'name' => 'Field name 2',
		];
		
		static::$Creator
			->getFirstField()
				->setLocale($a_localeSet)
				->get('locale', $a_localeGet);
		
		$this->assertSame($a_localeSet, $a_localeGet);
		
		// Test general add()
		$a_localeAdd = [
			'placeholder' => 'Placeholder 2',
		];
		
		static::$Creator
			->getFirstField()
				->addLocale($a_localeAdd)
				->get('locale', $a_localeGet);
		
		$this->assertSame(array_merge($a_localeSet, $a_localeAdd), $a_localeGet);
		
	}
	
	/**
	 * 
	 */
	public function testFieldOrder(): void {
		$Form = static::$Reef->newTempForm();
		$Creator = $Form->newCreator();
		
		$fn_getFieldNames = function() use($Form) {
			return array_keys($Form->getValueFieldsByName());
		};
		
		$Creator
			->addField('reef:text_line')->setName('a')
			->addField('reef:text_line')->setName('b')
			->addField('reef:text_line')->setName('c')
			->addField('reef:text_line')->setName('d')
			->addField('reef:text_line')->setName('e')
			->addField('reef:text_line')->setName('f')
			->apply();
		
		$this->assertSame(6, count($Form->getFields()));
		
		$Context = $Creator->getFirstField();
		
		$this->assertSame('a', $Context->getFirstField()->return('name'));
		$this->assertSame('b', $Context->getNextField()->return('name'));
		$this->assertSame('c', $Context->getNextField()->getNextField()->getPrevField()->return('name'));
		$this->assertSame('f', $Context->getLastField()->return('name'));
		
		$this->assertSame(['a', 'b', 'c', 'd', 'e', 'f'], $fn_getFieldNames());
		
		// Set position down
		$Context
			->getFieldByName('b')
				->setPosition(5)
			->apply();
		
		$this->assertSame(5, $Context->returnPosition());
		$this->assertSame(['a', 'c', 'd', 'e', 'b', 'f'], $fn_getFieldNames());
		
		// Set position up
		$Context
			->getFieldByName('e')
				->setPosition(3)
			->apply();
		
		$this->assertSame(3, $Context->returnPosition());
		$this->assertSame(['a', 'c', 'e', 'd', 'b', 'f'], $fn_getFieldNames());
		
		// Move up
		$Context
			->getFieldByName('d')
				->moveUp()
			->apply();
		
		$this->assertSame(['a', 'c', 'd', 'e', 'b', 'f'], $fn_getFieldNames());
		
		// Move down
		$Context
			->getFieldByName('c')
				->moveDown()
			->apply();
		
		$this->assertSame(['a', 'd', 'c', 'e', 'b', 'f'], $fn_getFieldNames());
		
		// Move to begin
		$Context
			->getFieldByName('b')
				->moveToBegin()
			->apply();
		
		$this->assertSame(['b', 'a', 'd', 'c', 'e', 'f'], $fn_getFieldNames());
		
		// Move to end
		$Context
			->getFieldByName('d')
				->moveToEnd()
			->apply();
		
		$this->assertSame(['b', 'a', 'c', 'e', 'f', 'd'], $fn_getFieldNames());
		
		// Move before: internal
		$Context
			->getFieldByName('e')
				->moveBefore('a')
			->apply();
		
		$this->assertSame(['b', 'e', 'a', 'c', 'f', 'd'], $fn_getFieldNames());
		
		// Move before: no-op
		$Context
			->getFieldByName('c')
				->moveBefore('f')
			->apply();
		
		$this->assertSame(['b', 'e', 'a', 'c', 'f', 'd'], $fn_getFieldNames());
		
		// Move before: to begin
		$Context
			->getFieldByName('f')
				->moveBefore('b')
			->apply();
		
		$this->assertSame(['f', 'b', 'e', 'a', 'c', 'd'], $fn_getFieldNames());
		
		// Move after: internal
		$Context
			->getFieldByName('e')
				->moveAfter('c')
			->apply();
		
		$this->assertSame(['f', 'b', 'a', 'c', 'e', 'd'], $fn_getFieldNames());
		
		// Move after: no-op
		$Context
			->getFieldByName('c')
				->moveAfter('a')
			->apply();
		
		$this->assertSame(['f', 'b', 'a', 'c', 'e', 'd'], $fn_getFieldNames());
		
		// Move after: to end
		$Context
			->getFieldByName('b')
				->moveAfter('d')
			->apply();
		
		$this->assertSame(['f', 'a', 'c', 'e', 'd', 'b'], $fn_getFieldNames());
		
		try {
			$Context
				->getFieldByName('a')
					->setPosition(7);
			$this->fail('Was able to set invalid position');
		}
		catch(CreatorException $e) {}
		
		// Verify end result
		$Context->getFieldByName('a')->getPosition($i_position);
		$this->assertSame(2, $i_position);
		
		$i_position = $Context->getFieldByName('b')->returnPosition();
		$this->assertSame(6, $i_position);
		
		$Context->getFieldByName('c')->getIndex($i_index);
		$this->assertSame(2, $i_index);
		
		$i_index = $Context->getFieldByName('d')->returnIndex();
		$this->assertSame(4, $i_index);
		
		$Context->getFieldByName('e')->getIndex($i_index);
		$this->assertSame(3, $i_index);
		
		$i_index = $Context->getFieldByName('f')->returnIndex();
		$this->assertSame(0, $i_index);
		
		$this->assertSame('f', $Creator->getFirstField()->return('name'));
		$this->assertSame('b', $Creator->getLastField()->return('name'));
		
	}
	
}
