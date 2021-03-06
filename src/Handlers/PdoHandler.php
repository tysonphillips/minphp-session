<?php
namespace Minphp\Session\Handlers;

use SessionHandlerInterface;
use PDO;

/**
 * PDO Database Session Handler
 */
class PdoHandler implements SessionHandlerInterface
{
    protected $db;
    protected $options;

    public function __construct(PDO $db, array $options = [])
    {
        $this->options = array_merge(
            [
                'tbl' => 'sessions',
                'tbl_id' => 'id',
                'tbl_exp' => 'expire',
                'tbl_val' => 'value'
            ],
            $options
        );
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        $query = "DELETE FROM {$this->options['tbl']} WHERE {$this->options['tbl_id']} = :id";
        $this->db->prepare($query)
            ->execute([':id' => $sessionId]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime)
    {
        $query = "DELETE FROM {$this->options['tbl']} WHERE {$this->options['tbl_exp']} < :expire";
        $this->db->prepare($query)
            ->execute([':expire' => date('Y-m-d H:i:s', time() - $maxlifetime)]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $name)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        $query = "SELECT {$this->options['tbl_val']} FROM {$this->options['tbl']} "
            . "WHERE {$this->options['tbl_id']} = :id AND {$this->options['tbl_exp']} >= :expire";
        $row = $this->db->prepare($query, [PDO::FETCH_OBJ])
            ->execute([':id' => $sessionId, ':expire' => date('Y-m-d H:i:s')]);

        if ($row) {
            return $row->{$this->options['tbl_val']};
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        $ttl = ini_get('session.gc_maxlifetime');
        $session = [
            ':value' => $data,
            ':id' => $sessionId,
            ':expire' => date('Y-m-d H:i:s', time() + $ttl)
        ];

        $updateQuery = "UPDATE {$this->options['tbl']} SET {$this->options['tbl_val']} = :value, "
            . "{$this->options['tbl_exp']} = :expire "
            . "WHERE {$this->options['tbl_id']} = :id";
        $updateStmt = $this->db->prepare($updateQuery);
        $updateStmt->execute($session);

        if (!$updateStmt->rowCount()) {
            // Session does not exist, so create it
            $insertQuery = "INSERT INTO {$this->options['tbl']} "
                . "({$this->options['tbl_id']}, {$this->options['tbl_val']}, {$this->options['tbl_exp']}) "
                . "VALUES (:id, :value, :expire)";
            $this->db->prepare($insertQuery)->execute($session);
        }
        return true;
    }
}
