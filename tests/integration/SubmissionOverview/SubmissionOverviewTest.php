<?php

namespace tests\SubmissionOverview;

use PHPUnit\Framework\TestCase;
use Reef\Storage\PDO_SQLite_Storage;
use \Reef\Storage\Storage;
use \Reef\Exception\InvalidArgumentException;

final class SubmissionOverviewTest extends TestCase {
	
	const ROWS = [
		['_entry_id' => 1, 'input_1' => 'asdf', 'input_2' => 1],
		['_entry_id' => 2, 'input_1' => '', 'input_2' => 0],
		['_entry_id' => 3, 'input_1' => 'test test', 'input_2' => 1],
	];
	
	private static $Reef;
	private static $Form;
	private static $i_submissionId;
	
	public static function setUpBeforeClass() {
		global $_reef_reef;
		static::$Reef = $_reef_reef;
	}
	
	/**
	 */
	public function testCanCreateForm(): void {
		static::$Form = static::$Reef->newStoredForm();
		
		$Creator = static::$Form->newCreator();
		$Creator
			->getForm()
				->setStorageName('submission_overview_test')
			->addField('reef:heading')
				->set('size', 4)
				->setLocale(['title' => 'Test form'])
			->addField('reef:text_line')
				->setName('input_1')
				->setLocale(['title' => 'Input 1'])
			->addField('reef:checkbox')
				->setName('input_2')
				->setLocale(['title_left' => 'Input 2'])
			->apply();
		
		$a_fields = static::$Form->getFields();
		$this->assertSame(3, count($a_fields));
		
		// Test raw head when having no rows
		$this->assertSame(array_keys(static::ROWS[0]), static::$Form->newSubmissionOverview()->set('raw', true)->getHead());
		
		foreach(static::ROWS as $a_row) {
			$Submission = static::$Form->newSubmission();
			$Submission->fromStructured($a_row);
			$Submission->save();
		}
		
		// Test raw head when having rows
		$this->assertSame(array_keys(static::ROWS[0]), static::$Form->newSubmissionOverview()->set('raw', true)->getHead());
		
		// Test value head when having rows
		$this->assertSame(count(static::ROWS[0]), count(static::$Form->newSubmissionOverview()->getHead()));
	}
	
	/**
	 * @depends testCanCreateForm
	 */
	public function testTable(): void {
		$a_table = static::$Form->newSubmissionOverview()->set('raw', true)->getTable();
		
		$this->assertEquals(static::ROWS, $a_table);
		
	}
	
	/**
	 * @depends testCanCreateForm
	 */
	public function testGenerator(): void {
		$i = 0;
		foreach(static::$Form->newSubmissionOverview()->set('raw', true)->getGenerator() as $a_row) {
			$this->assertEquals(static::ROWS[$i++], $a_row);
		}
	}
	
	/**
	 * @depends testCanCreateForm
	 */
	public function testCallback(): void {
		// First with raw
		$Overview = static::$Form->newSubmissionOverview()
			->set('raw', true)
			->set('callback_head', function(&$a_head) {
				array_unshift($a_head, 'column');
			})
			->set('callback_row', function(&$a_row) {
				array_unshift($a_row, $a_row['_entry_id']+1);
			});
		$a_head = $Overview->getHead();
		$a_table = $Overview->getTable();
		
		// Validate head
		$a_refHead = array_keys(static::ROWS[0]);
		array_unshift($a_refHead, 'column');
		$this->assertEquals($a_refHead, $a_head);
		
		// Validate rows
		$a_refTable = static::ROWS;
		foreach($a_refTable as &$a_row) {
			array_unshift($a_row, $a_row['_entry_id']+1);
		}
		$this->assertEquals($a_refTable, $a_table);
		
		// Now with values
		$Overview
			->set('raw', false)
			->set('callback_row', function(&$a_row) {
				array_unshift($a_row, 1);
			});
		$a_head = $Overview->getHead();
		$a_table = $Overview->getTable();
		$this->assertEquals(count($a_refHead), count($a_head));
		$this->assertSame([4], array_unique(array_map(function($a_row) { return count($a_row); }, $a_table)));
	}
	
	/**
	 * @depends testCanCreateForm
	 * @doesNotPerformAssertions
	 */
	public function testCSV_set(): void {
		$this->expectException(InvalidArgumentException::class);
		
		static::$Form->newSubmissionOverview()->set('some_invalid_key', 'value');
	}
	
	/**
	 * @depends testCanCreateForm
	 */
	public function testCSV_raw(): void {
		$a_table = array_map('str_getcsv', explode(PHP_EOL, static::$Form->newSubmissionOverview()->set('raw', true)->CSV()));
		unset($a_table[4]); // Empty line..
		
		array_walk($a_table, function(&$a_row) use ($a_table) {
			$a_row = array_combine($a_table[0], $a_row);
		});
		array_shift($a_table);
		$a_table = array_filter($a_table);
		
		$this->assertEquals(static::ROWS, $a_table);
	}
	
	/**
	 * @depends testCanCreateForm
	 */
	public function testCSV_value(): void {
		$a_table = array_map('str_getcsv', explode(PHP_EOL, static::$Form->newSubmissionOverview()->CSV()));
		
		$this->assertSame(5, count($a_table));
		
		unset($a_table[4]); // Empty line..
		
		$this->assertSame([3], array_unique(array_map(function($a_row) { return count($a_row); }, $a_table)));
	}
	
	public static function tearDownAfterClass() {
		if(!empty(static::$Form)) {
			static::$Form->delete();
		}
	}
}
