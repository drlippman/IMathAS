<?php

class SessionDBHandler implements SessionHandlerInterface
{
	private $db;

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
	public function close()
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function destroy($sessionId)
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
	public function open($savePath, $sessionName)
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
	public function read($sessionId)
	{
		$stm = $this->db->prepare('SELECT `data` FROM php_sessions WHERE id = :id');
		$stm->bindParam(':id', $sessionId);

		if ($stm->execute() && ($row = $stm->fetch())) {
			return $row['data'];
		} else {
			return '';
		}
	}

	/**
	 * @inheritdoc
	 */
	public function write($sessionId, $sessionData)
	{
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
