<?php
namespace Models;

use Database\Database;
use Movie;
use RuntimeException;
use Exception;
use InvalidArgumentException;

/**
 *  Vote model
 */
class Vote
{
    private $db;

    private const TABLE = 'votes';

    protected array $fillable = [
        'vote_type',
        'movie_id',
        'ip_address',
        'session_id'
    ];

    protected array $attributes = [
        'id',
        'created_at',
        'updated_at'
    ];

    public int $id;
    public int $movie_id;
    public int $vote_type;
    public ?string $ip_address = null;
    public string $session_id;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function __construct(array $data = [])
    {
        $this->db = Database::getInstance();

        if (!empty($data)) {
            $this->fill($data);
        }
    }


    /**
     * @param array $data
     * @return void
     */
    private function fill(array $data): void
    {
        foreach ($data as $key => $value) {
            if (!in_array($key, $this->fillable, true) && !in_array($key, $this->attributes, true)) {
                continue;
            }

            switch ($key){
                case 'id':
                case 'vote_type':
                case 'movie_id':
                    $this->$key = (int)$value;
                    break;
                case 'ip_address':
                    $ip = filter_var($value, FILTER_VALIDATE_IP);
                    $this->$key = $ip !== false ? $ip : null;
                    break;
                default:
                    $this->$key = (string)$value;
                    break;
            }
        }
    }

    /**
     * Saves new model to database
     * @return Vote
     */
    public function create(): self
    {
        $id = $this->db->insert(self::TABLE, [
            'movie_id' => $this->movie_id,
            'vote_type' => $this->vote_type,
            'ip_address' => $this->ip_address,
            'session_id' => $this->session_id,
        ]);
        $this->id = (int)$id;
        return $this;
    }

    /**
     * @return array|null
     */
    protected function getExistingData()
    {
        $data = $this->db->select(self::table(), [
            'movie_id' => $this->movie_id,
            'session_id' => $this->session_id
        ]);

        return $data[0] ?? null;
    }

    /**
     * Updates values
     * @return bool
     */
    public function save(): bool
    {

        $now = date('Y-m-d H:i:s');
        $existing = $this->getExistingData();
        $movie = new Movie(['id' => $this->movie_id]);

        if (!self::isValidVote($this->vote_type)) {
            throw new InvalidArgumentException('Invalid vote');
        }

        // If no existing vote create new vote
        if (!$existing) {
            $this->created_at = $now;
            $this->updated_at = null;

            $this->db->beginTransaction();
            try {
                $this->create();

                if ($this->vote_type === 1) {
                    $movie->incrementUpvotes();
                } else {
                    $movie->incrementDownvotes();
                }
                $this->db->commit();
            } catch (Exception $e) {
                    $this->db->rollBack();
                    throw $e;
            }

            return true;
        }

        // If the vote is the same, we don't have to do anything
        if ($existing['vote_type'] === $this->vote_type) {
            throw new RuntimeException('Vote already set');
        }

        $this->db->beginTransaction();
        try {
            // If the vote changed, adjust
            if ($existing['vote_type'] === 1 && $this->vote_type === -1) {
                $movie->decrementUpvotes();
                $movie->incrementDownvotes();
            } elseif ($existing['vote_type'] === -1 && $this->vote_type === 1) {
                $movie->decrementDownvotes();
                $movie->incrementUpvotes();
            }

            // update vote
            $this->updated_at = $now;

            $updated =$this->db->update(self::table(), [
                'vote_type' => $this->vote_type,
                'updated_at' => $now
            ], [
                'movie_id' => $this->movie_id,
                'session_id' => $this->session_id
            ]);
            if (!$updated) {
                throw new RuntimeException('Failed to update vote');
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        return true;
    }

    public function delete(): bool
    {
        return $this->db->delete(self::TABLE, ['id' => $this->id]);
    }

    /**
     * @return bool
     */
    public function refresh(): bool
    {
        $vote =  $this->getExistingData();
        if ($vote === null) {
            return false;
        }

        $this->id = $vote->id;
        $this->created_at = $vote->created_at;
        $this->updated_at = $vote->updated_at;

        return true;
    }

    /**
     * @param int $cooldown
     * @return bool
     */
    public function canVote(): bool
    {
        $config = require __DIR__ . '/../../config.php';
        $this->refresh();

        if (empty($this->created_at)) {
            return true;
        }

        $lastVoteTime = $this->updated_at ?? $this->created_at;
        $lastVoteTime = strtotime($lastVoteTime);
        return (time() - $lastVoteTime) >= $config['cooldown'];
    }

    /**
     * Find vote based on movie_id and session_id
     * @param int $movie_id
     * @param string $session_id
     * @return Vote|null
     */
    public static function find(int $movie_id, string $session_id): ?Vote
    {
        $db = Database::getInstance();
        $rows = $db->select(self::table(), [
            'movie_id' => $movie_id,
            'session_id' => $session_id
        ]);

        if (empty($rows)) {
            return null;
        }

        return new Vote($rows[0]);
    }

    /**
     * Returns table name
     * @return string
     */
    public static function table(): string
    {
        return self::TABLE;
    }

    /**
     * Checks to see if vote is valid number
     * @param int $vote
     * @return bool
     */
    public static function isValidVote(int $vote): bool
    {
        return in_array($vote, [1, -1], true);
    }
}