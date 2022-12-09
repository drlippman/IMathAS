<?php

class SessionDBHandler implements SessionHandlerInterface
{
	private $db;
	private $readLastAccess = 0;
	private $readHash = '';

	/**
	 * SessionHandler constructor.
	 *
	 * Re-use the global database connection.
	 */
	public function __construct()
	{
		global $DBH;

		$this->db = $DBH;
	}

	/**
	 * @inheritdoc
	 */
	public function close(): bool
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function destroy($sessionId): bool
	{
		$stm = $this->db->prepare('DELETE FROM php_sessions WHERE id = :sessionId');
		$stm->bindParam(':sessionId', $sessionId);

		if ($stm->execute()) {
			return true;
		}

		return false;
	}

	/**
	 * @inheritdoc
	 */
    #[\ReturnTypeWillChange]
	public function gc($maxLifetime) 
	{
		$oldTimestamp = time() - $maxLifetime;

		$stm = $this->db->prepare('DELETE FROM php_sessions WHERE access < :oldTimestamp');
		$stm->bindParam(':oldTimestamp', $oldTimestamp);

		if ($stm->execute()) {
			return true;
		}

		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function open($savePath, $sessionName): bool
	{
		if ($this->db) {
			return true;
		}
		error_log("No database connection is available for PHP session storage.");
		return false;
	}

	/**
	 * @inheritdoc
	 */
    #[\ReturnTypeWillChange]
	public function read($sessionId)
	{
		$stm = $this->db->prepare('SELECT `data`,`access` FROM php_sessions WHERE id = :id');
		$stm->bindParam(':id', $sessionId);

		if ($stm->execute() && ($row = $stm->fetch())) {
			$this->readLastAccess = $row['access'];
			$this->readHash = md5($row['data']);
			$GLOBALS['sessionLastAccess'] = $row['access'];
			return $row['data'];
		} else {
			return '';
		}
	}

	/**
	 * @inheritdoc
	 */
	public function write($sessionId, $sessionData): bool
	{
		if ($sessionData == '') {
			return true; // skip write if no data
		}
		if (md5($sessionData) === $this->readHash &&
			time() - $this->readLastAccess < 300
		) {
			// no change to data, and little time has elapsed, so skip update
			return true;
		}
		//$stm = $this->db->prepare('REPLACE INTO php_sessions VALUES (:sessionId, :lastAccessTime, :sessionData)');
		$query = 'INSERT INTO php_sessions (id,access,data) VALUES (:sessionId, :lastAccessTime, :sessionData) ';
		$query .= 'ON DUPLICATE KEY UPDATE access=:lastAccessTime2,data=:sessionData2';
		$stm = $this->db->prepare($query);

		$stm->bindParam(':sessionId', $sessionId);
		$stm->bindValue(':lastAccessTime', time());
		$stm->bindParam(':sessionData', $sessionData);
		$stm->bindValue(':lastAccessTime2', time());
		$stm->bindParam(':sessionData2', $sessionData);

		if ($stm->execute()) {
			return true;
		}

		return false;
	}

}
