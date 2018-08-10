<?php

namespace tests\SubmissionOverview;

use PHPUnit\Framework\TestCase;
use Reef\Storage\PDO_SQLite_Storage;
use \Reef\Storage\Storage;
use \Reef\Exception\InvalidArgumentException;

final class SubmissionOverviewTest extends TestCase {
	
	const ROWS = [
		['_entry_id' => 1, 'input_1' => 'asdf', 'input_2' => true],
		['_entry_id' => 2, 'input_1' => '', 'input_2' => false],
		['_entry_id' => 3, 'input_1' => 'test test', 'input_2' => true],
	];
	
	private static $Reef;
	private static $Form;
	private static $i_submissionId;
	
	public static function setUpBeforeClass() {
		
	}
	
	public function testCanCreateReef(): void {
		$PDO = new \PDO("sqlite::memory:");
		
		// Specify which components we want to use
		$Setup = new \Reef\ReefSetup(
			new \Reef\Storage\PDOStorageFactory($PDO),
			new \Reef\Layout\bootstrap4\bootstrap4()
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
		static::$Form = static::$Reef->newStoredForm();
		
		$Creator = static::$Form->newCreator();
		$Creator
			->getForm()
				->setStorageName('test')
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
		
		foreach(static::ROWS as $a_row) {
			$Submission = static::$Form->newSubmission();
			$Submission->fromStructured($a_row);
			$Submission->save();
		}
	}
	
	/**
	 * @depends testCanCreateForm
	 */
	public function testTable(): void {
		$a_table = static::$Form->newSubmissionOverview()->getTable();
		
		$this->assertEquals(static::ROWS, $a_table);
		
	}
	
	/**
	 * @depends testCanCreateForm
	 */
	public function testGenerator(): void {
		$i = 0;
		foreach(static::$Form->newSubmissionOverview()->getGenerator() as $a_row) {
			$this->assertEquals(static::ROWS[$i++], $a_row);
		}
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
}
