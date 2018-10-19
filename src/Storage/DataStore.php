<?php

namespace Reef\Storage;

use \Reef\Reef;
use \Reef\Form\StoredForm;
use \Reef\Exception\StorageException;

/**
 * The data store uses the StorageFactory passed in the Reef setup to store
 * data in the database. The data store keeps track of the form and submission
 * storages created, and allows to fetch and modify them.
 */
class DataStore {
	
	/**
	 * The Reef object this DataStore belongs to
	 * @type Reef
	 */
	private $Reef;
	
	/**
	 * The StorageFactory in use by this DataStore
	 * @type StorageFactory
	 */
	private $StorageFactory;
	
	/**
	 * The form storage instance
	 * @type Storage
	 */
	private $FormStorage;
	
	/**
	 * The submission storage instances, indexed by storage name
	 * @type Storage[]
	 */
	private $a_submissionStorages = [];
	
	/**
	 * The database prefix to use
	 * @type string
	 */
	private $s_prefix;
	
	/**
	 * Constructor
	 * @param Reef $Reef The Reef object
	 */
	public function __construct(Reef $Reef) {
		$this->Reef = $Reef;
		$this->StorageFactory = $this->Reef->getSetup()->getStorageFactory();
		$this->s_prefix = $this->Reef->getOption('db_prefix');
	}
	
	/**
	 * Retrieve the Form Storage object. If it does not exist yet, the storage is created
	 * @return Storage The form storage
	 */
	public function getFormStorage() : Storage {
		if(empty($this->FormStorage)) {
			if($this->StorageFactory->hasStorage($this->s_prefix.'_forms')) {
				$this->FormStorage = $this->StorageFactory->getStorage($this->s_prefix.'_forms');
			}
			else {
				$this->FormStorage = $this->StorageFactory->newStorage($this->s_prefix.'_forms');
				
				$this->FormStorage->addColumns([
					'definition' => [
						'type' => Storage::TYPE_TEXT,
						'limit' => 4194303,
					],
				]);
			}
		}
		
		return $this->FormStorage;
	}
	
	/**
	 * Determine whether the given submission storage name exists
	 * @return bool
	 */
	public function hasSubmissionStorage($s_storageName) : bool {
		return $this->StorageFactory->hasStorage($this->s_prefix.$s_storageName);
	}
	
	/**
	 * Create a new Submission Storage
	 * @return Storage The submission storage object
	 */
	public function createSubmissionStorage(StoredForm $Form) : Storage {
		$s_storageName = $Form->getStorageName();
		
		if($s_storageName === null) {
			throw new StorageException('Storage name is not set');
		}
		
		if(!empty($this->a_submissionStorages[$s_storageName]) || $this->StorageFactory->hasStorage($this->s_prefix.$s_storageName)) {
			throw new StorageException('Storage already exists');
		}
		
		$this->StorageFactory->newStorage($this->s_prefix.$s_storageName);
		
		return $this->getSubmissionStorage($Form);
	}
	
	/**
	 * Retrieve a Submission Storage object
	 * @return Storage The submission storage
	 */
	public function getSubmissionStorage(StoredForm $Form) : Storage {
		$s_storageName = $Form->getStorageName();
		
		if($s_storageName === null) {
			throw new StorageException('Storage name is not set');
		}
		
		if(empty($this->a_submissionStorages[$s_storageName])) {
			$this->a_submissionStorages[$s_storageName] = $this->StorageFactory->getStorage($this->s_prefix.$s_storageName);
		}
		
		return $this->a_submissionStorages[$s_storageName];
	}
	
	/**
	 * Delete a Submission Storage
	 * @param StoredForm $Form The form to delete the submission storage for
	 */
	public function deleteSubmissionStorage(StoredForm $Form) {
		$this->getSubmissionStorage($Form)->deleteStorage();
		unset($this->a_submissionStorages[$Form->getStorageName()]);
	}
	
	/**
	 * Delete a Submission Storage if it exists; does not throw a StorageException if the storage does not exist
	 * @param StoredForm $Form The form to delete the submission storage for
	 */
	public function deleteSubmissionStorageIfExists(StoredForm $Form) {
		if($this->hasSubmissionStorage($Form->getStorageName())) {
			$this->deleteSubmissionStorage($Form);
		}
	}
	
	/**
	 * Rename a submission storage
	 * @param StoredForm $Form The form to change the submission storage name for
	 * @param string $s_newStorageName The new storage name
	 */
	public function changeSubmissionStorageName(StoredForm $Form, $s_newStorageName) {
		if(!$this->hasSubmissionStorage($Form->getStorageName())) {
			throw new StorageException("Storage not found for renaming");
		}
		
		$this->getSubmissionStorage($Form)->renameStorage(
			$this->s_prefix.$s_newStorageName
		);
		
		unset($this->a_submissionStorages[$Form->getStorageName()]);
	}
	
	/**
	 * Utility function for applying modifications in the database and filesystem,
	 * allowing them to be rolled back when an error occurs.
	 * @param callable $fn_callback Callback performing the desired modifications
	 * @return mixed The result of $fn_callback
	 */
	public function ensureTransaction($fn_callback) {
		$Filesystem = $this->getFilesystem();
		$b_transaction = !$Filesystem->inTransaction();
		
		if($b_transaction) {
			$Filesystem->startTransaction();
		}
		
		try {
			$m_return = $this->StorageFactory->ensureTransaction($fn_callback);
			
			if($b_transaction) {
				$Filesystem->commitTransaction();
			}
		}
		catch(\Exception $e) {
			$Filesystem->rollbackTransaction();
			throw $e;
		}
		
		return $m_return;
	}
	
	/**
	 * Get the Filesystem object
	 * @return Filesystem
	 */
	public function getFilesystem() {
		return $this->Reef->getSetup()->getFilesystem();
	}
	
}
