<?php
namespace Idealstack\DynamoDbSessionsDependencyFree;
require_once __DIR__.'/DynamoDbSessionConnection.php';
require_once __DIR__.'/DynamoDbClient.php';

class DynamoDbSessionHandler implements \SessionHandlerInterface
{
    protected $service = 'dynamodb';
    /** @var string Session save path. */
    private $savePath;

    /** @var string Session name. */
    private $sessionName;

    /** @var string The last known session ID */
    private $openSessionId = '';

    /** @var string Stores serialized data for tracking changes. */
    private $dataRead = '';

    /** @var bool Keeps track of whether the session has been written. */
    private $sessionWritten = false;

    /** @var int Keeps track of when the session was last written */
    private $lastWritten = 0;

    private $connection;

    private $config;


    /**
     * @param DynamoDbClient $client DynamoDB client
     * @param array $config Session handler config
     */
    public function __construct(array $config = [])
    {
        $this->connection = new DynamoDbSessionConnection(new DynamoDbClient($config), $config);
        $this->config = $config + ['base64' => true];

    }
    /**
     * Register the DynamoDB session handler.
     *
     * @return bool Whether or not the handler was registered.
     * @codeCoverageIgnore
     */
    public function register()
    {
        return session_set_save_handler($this, true);
    }

    /**
     * Open a session for writing. Triggered by session_start().
     *
     * @param string $savePath    Session save path.
     * @param string $sessionName Session name.
     *
     * @return bool Whether or not the operation succeeded.
     */
    public function open($savePath, $sessionName)
    {
        $this->savePath = $savePath;
        $this->sessionName = $sessionName;

        return true;
    }

    /**
     * Close a session from writing.
     *
     * @return bool Success
     */
    public function close()
    {
        $id = session_id();
        // Make sure the session is unlocked and the expiration time is updated,
        // even if the write did not occur
        if ($this->openSessionId !== $id || !$this->sessionWritten) {
            $result = $this->connection->write($this->formatId($id), '', false);
            $this->sessionWritten = (bool) $result;
        }

        return $this->sessionWritten;
    }

    /**
     * Read a session stored in DynamoDB.
     *
     * @param string $id Session ID.
     *
     * @return string Session data.
     */
    public function read($id)
    {
        $this->openSessionId = $id;
        // PHP expects an empty string to be returned from this method if no
        // data is retrieved
        $this->dataRead = '';

        // Get session data using the selected locking strategy
        $item = $this->connection->read($this->formatId($id));

        // Return the data if it is not expired. If it is expired, remove it
        if (isset($item['expires']) && isset($item['data'])) {
            $this->dataRead = $item['data'];
            if ($item['expires'] <= time()) {
                $this->dataRead = '';
                $this->destroy($id);
            } else {
            	$this->lastWritten = $item['expires'] - (int) ini_get('session.gc_maxlifetime');
            	$GLOBALS['sessionLastAccess'] = $this->lastWritten;
            }
        }

        return  $this->config['base64'] ? base64_decode($this->dataRead) : $this->dataRead;
    }

    /**
     * Write a session to DynamoDB.
     *
     * @param string $id   Session ID.
     * @param string $data Serialized session data to write.
     *
     * @return bool Whether or not the operation succeeded.
     */
    public function write($id, $data)
    {

        if ($this->config['base64'] ) {
            $data = base64_encode($data);
        }
        $changed = $id !== $this->openSessionId
            || $data !== $this->dataRead;

        if (!$changed && time() - $this->lastWritten < 300) {
        	return true; // skip writing
        }
        $this->openSessionId = $id;

        // Write the session data using the selected locking strategy
        $this->sessionWritten = $this->connection
            ->write($this->formatId($id), $data, $changed);

        return $this->sessionWritten;
    }

    /**
     * Delete a session stored in DynamoDB.
     *
     * @param string $id Session ID.
     *
     * @return bool Whether or not the operation succeeded.
     */
    public function destroy($id)
    {
        $this->openSessionId = $id;
        // Delete the session data using the selected locking strategy
        $this->sessionWritten
            = $this->connection->delete($this->formatId($id));

        return $this->sessionWritten;
    }

    /**
     * Satisfies the session handler interface, but does nothing. To do garbage
     * collection, you must manually call the garbageCollect() method.
     *
     * @param int $maxLifetime Ignored.
     *
     * @return bool Whether or not the operation succeeded.
     * @codeCoverageIgnore
     */
    public function gc($maxLifetime)
    {
        // Garbage collection for a DynamoDB table must be triggered manually.
        return true;
    }


    /**
     * Prepend the session ID with the session name.
     *
     * @param string $id The session ID.
     *
     * @return string Prepared session ID.
     */
    private function formatId($id)
    {
        global $installname;
        return trim($this->sessionName . '_' . preg_replace('/\W/','',$installname) . '_' . $id, '_');
    }

}
