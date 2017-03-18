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
      $latestVersionApplied = $this->getLatestVersionApplied();
      $migrationsAppliedThisRun = 0;

      $migrationFilenames = glob(__DIR__ . '/migrations/*.php');
      natsort($migrationFilenames);

      foreach ($migrationFilenames as $migrationFilename) {
        $migrationVersion = $this->getMigrationFileVersion($migrationFilename);

        if ($migrationVersion <= $latestVersionApplied) {
          continue;
        }

        $result = $this->migrateSingle($migrationFilename);

        if (!$result) {
          printf("<p style='color: #ff0000;'>Migration FAILED: %s</p>\n", basename($migrationFilename));
          return null;
        } else {
          $this->storeLatestVersionApplied($migrationVersion);
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

      $result = require($migrationFilename);

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
      * Store the latest successfully applied migration version to the DB.
      */
    private function storeLatestVersionApplied($version)
    {
        $stm = $this->DBH->prepare("UPDATE imas_dbschema SET ver=:ver WHERE id=1");
        $stm->execute(array(':ver'=>$version));

        if (false === $stm) {
            printf("<p style='color: #ff0000'>Unable to store migration version: %s</p>\n", $version);
        }
    }

    /**
     * Get the last successfully applied migration version from the DB.
     *
     * @return float The last successfully applied migration version.
     */
    public function getLatestVersionApplied()
    {
        $stm = $this->DBH->query("SELECT ver FROM imas_dbschema WHERE id=1");
        $lastVersion = $stm->fetchColumn(0);

        return $lastVersion;
    }
    
    /**
     * Get the last available migration version in the migration directory
     *
     * @return float The last available migration version.
     */
    public function getLatestVersionAvailable()
    {
        $migrationFilenames = glob(__DIR__ . '/migrations/*.php');
        if (count($migrationFilenames)==0) {
        	return 0;
        }
        natsort($migrationFilenames);

        $lastVersion = $this->getMigrationFileVersion($migrationFilenames[count($migrationFilenames)-1]);
        
        return $lastVersion;
    }
}
