<?php

namespace ReefTests\integration\Components\Upload;

use \ReefTests\integration\Components\ComponentTestCase;
use \Reef\Exception\InvalidArgumentException;
use \Reef\Exception\BadMethodCallException;

final class UploadComponentTest extends ComponentTestCase {
	
	use CommonUploadTrait;
	
	const FILES_DIR = TEST_TMP_DIR . '/reef_upload_component';
	
	public function testCanBeCreated() {
		parent::testCanBeCreated();
	}
	
	public function testSetDefaultTypes() {
		$Component = $this->createComponent();
		
		$Component->setDefaultTypes(['txt']);
		$this->assertSame(['txt'], $Component->getDefaultTypes());
	}
	
	/**
	 * @depends testSetDefaultTypes
	 */
	public function testAddDefaultTypes() {
		$Component = $this->createComponent();
		
		$Component->setDefaultTypes(['txt']);
		
		$Component->addDefaultTypes(['txt']);
		$this->assertSame(['txt'], $Component->getDefaultTypes());
		
		$Component->addDefaultTypes(['png']);
		$this->assertSame(['txt', 'png'], $Component->getDefaultTypes());
	}
	
	/**
	 * @depends testSetDefaultTypes
	 */
	public function testRemoveDefaultTypes() {
		$Component = $this->createComponent();
		
		$Component->setDefaultTypes(['txt', 'jpeg', 'asdf']);
		
		$Component->removeDefaultTypes(['txt']);
		$this->assertSame(['jpeg', 'asdf'], array_values($Component->getDefaultTypes()));
	}
	
	/**
	 * @depends testSetDefaultTypes
	 */
	public function testCheckSetupFailsWithInvalidDefaultTypes() {
		global $_reef_PDO;
		
		// Specify which components we want to use
		$Setup = new \Reef\ReefSetup(
			\Reef\Storage\PDOStorageFactory::createFactory($_reef_PDO),
			new \Reef\Layout\bootstrap4\bootstrap4(),
			new \Reef\Session\TmpSession()
		);
		
		$Component = $this->createComponent();
		$Setup->addComponent($Component);
		
		$Component->addDefaultTypes(['bogus-ext']);
		
		$this->expectException(InvalidArgumentException::class);
		$Reef = new \Reef\Reef(
			$Setup,
			$this->getReefOptions()
		);
	}
	
	/**
	 * @depends testSetDefaultTypes
	 * @depends testCanBeCreated
	 */
	public function testCannotChangeDefaultsAfterInitialization() {
		$Component = static::$Reef->getSetup()->getComponent('reef:upload');
		
		$i_nonerrors = 0;
		try {
			$Component->setDefaultTypes(['txt']);
			$i_nonerrors++;
		} catch(BadMethodCallException $e) {}
		
		try {
			$Component->addDefaultTypes(['txt']);
			$i_nonerrors++;
		} catch(BadMethodCallException $e) {}
		
		try {
			$Component->removeDefaultTypes(['txt']);
			$i_nonerrors++;
		} catch(BadMethodCallException $e) {}
		
		$this->assertSame(0, $i_nonerrors, "Some default type setters (".$i_nonerrors.") did not throw an error when run after initialization");
	}
}
