<?php

/**
 * Execute all PHP files found in the migrations/ directory in natural order.
 */
class Migrator
{
    private $DBH;
    private $isInitialDBsetup;

    public function __construct($DBH, $isDBsetup)
    {
        $this->DBH = $DBH;
        $this->isInitialDBsetup = $isDBsetup;
    }

    /**
     * Execute all migration files found.
     *
     * @return string Version of last migration applied. Null on migration errors.
     */
    public function migrateAll()
    {

      $latestOldStyleVersionApplied = $this->getLatestVersionAppliedOldStyle();
      $appliedMigrations = $this->getAppliedMigrations();
      $migrationsAppliedThisRun = 0;

      $migrationFilenames = glob(__DIR__ . '/migrations/*.php');
      natsort($migrationFilenames);

      foreach ($migrationFilenames as $migrationFilename) {
        $migrationVersion = $this->getMigrationFileVersion($migrationFilename);

        if ($migrationVersion <= $latestOldStyleVersionApplied) {
        	continue;
        } else if ($migrationVersion>156 && in_array($migrationVersion, $appliedMigrations)) {
        	continue;
        }

        $result = $this->migrateSingle($migrationFilename);

        if (!$result) {
          printf("<p style='color: #ff0000;'>Migration FAILED: %s</p>\n", basename($migrationFilename));
          return null;
        } else {
        	if ($migrationVersion <= 156) { //156 is the last old-style migration
        		$this->storeLatestVersionAppliedOldStyle($migrationVersion);
        	} else {
        		$this->storeMigrationApplied($migrationVersion, $migrationFilename);
        	}
        	++$migrationsAppliedThisRun;
        }
      }

      if ($migrationsAppliedThisRun > 0) {
        printf("<p>Successfully applied %d migrations.</p>\n", $migrationsAppliedThisRun);
      }

      return $this->getLatestVersionApplied();
    }

    /**
     * Execute a single migration file.
     *
     * @param $migrationFilename string migration file to execute.
     * @return mixed True if the migration succeeded. False if not.
     */
    private function migrateSingle($migrationFilename)
    {
      // Allow migration scripts to access $this->DBH.
      $DBH = $this->DBH;
      $isDBsetup = $this->isInitialDBsetup;

      printf("<p>Applying migration: %s</p>\n", basename($migrationFilename));

      if (!is_readable($migrationFilename)) {
          printf("<p style='color: #ff0000'>Unable to read migration file: %s</p>\n", $migrationFilename);
          return false;
      }

      $result = require_once $migrationFilename;

      if ($result !== true && $result !== false) {
          printf("<p style='color: #ff0000'>Invalid result returned from migration: %s</p>\n", $migrationFilename);
          return false;
      }

      return $result;
    }

    /**
     * Extract and return the version number from a migration file name.
     *
     * @param $migrationFilename string The migration filename.
     * @return string the version of the migration file.
     */
    private function getMigrationFileVersion($migrationFilename)
    {
        $justFilename = basename($migrationFilename);
        $migrationVersion = explode("_", $justFilename)[0];

        return $migrationVersion;
    }
    
    /**
      * Get all applied migrations (new style)
      */
    private function getAppliedMigrations() 
    {
    	$stm = $this->DBH->query("SELECT id FROM imas_dbschema WHERE id>156");
        return $stm->fetchAll(PDO::FETCH_COLUMN);
    }
    /**
      * Store that the migration was applied.
      */
    private function storeMigrationApplied($version, $filename)
    {
        $stm = $this->DBH->prepare("INSERT INTO imas_dbschema (id,ver,details) VALUES (:id,:ver,:details)");
        $stm->execute(array(':id'=>$version, ':ver'=>time(), ':details'=>basename($filename)));

        if (false === $stm) {
            printf("<p style='color: #ff0000'>Unable to store migration version: %s</p>\n", $version);
        }
    }


    /**
      * Store the latest successfully applied migration version to the DB.
      */
    private function storeLatestVersionAppliedOldStyle($version)
    {
        $stm = $this->DBH->prepare("UPDATE imas_dbschema SET ver=:ver WHERE id=1");
        $stm->execute(array(':ver'=>$version));

        if (false === $stm) {
            printf("<p style='color: #ff0000'>Unable to store migration version: %s</p>\n", $version);
        }
    }

    /**
     * Get the last successfully applied migration version from the DB,
     *  of the older format migrations (sequential), the last of which was 156.
     *
     * @return float The last successfully applied migration version.
     */
    public function getLatestVersionAppliedOldStyle()
    {
        $stm = $this->DBH->query("SELECT ver FROM imas_dbschema WHERE id=1");
        $lastVersion = $stm->fetchColumn(0);

        return $lastVersion;
    }
    /**
     * Get the last successfully applied migration version from the DB,
     * new format, assuming in numerical order
     *
     * @return float The last successfully applied migration version.
     */
    public function getLatestVersionApplied()
    {
    	$lastOldVersion = $this->getLatestVersionAppliedOldStyle();
    	if ($lastOldVersion<156) {
    		return $lastOldVersion;
    	} else {
    		$stm = $this->DBH->query("SELECT id FROM imas_dbschema WHERE ver>0 AND id>156 ORDER BY id DESC LIMIT 1");
    		$lastVersion = $stm->fetchColumn(0);
    		if ($lastVersion === false) {
    			return $lastOldVersion;
    		} else {
    			return $lastVersion;
    		}
    	}
    }
    
   
}
