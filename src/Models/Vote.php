<?php
namespace Models;

use Database\ConnectionManager;
use Models\Movie;
use RuntimeException;
use Exception;
use InvalidArgumentException;

/**
 *  Vote model
 *
 * Represents a vote and provides methods for interacting
 */
class Vote extends Model
{
    protected const TABLE = 'votes';

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

    protected int $id;
    protected int $movie_id;
    protected int $vote_type;
    protected ?string $ip_address = null;
    protected string $session_id;
    protected ?string $created_at = null;
    protected ?string $updated_at = null;

    protected ?Movie $movie = null;

    /**
     * Sets values
     * @param array $data
     * @return void
     */
    protected function transform(string $key, mixed $value): mixed
    {
        switch ($key) {
                case 'id':
                case 'vote_type':
                case 'movie_id':
                    return (int)$value;
                case 'ip_address':
                    $ip = filter_var($value, FILTER_VALIDATE_IP);
                    return  $ip !== false ? $ip : null;
                default:
                    return (string)$value;
        }
    }

    /**
     * Returns movie of the vote
     * @return void
     */
    protected function getMovie(): ?Movie {
        if (!empty($this->movie_id) && empty($this->movie)) {
            $this->movie =  Movie::find($this->movie_id);
        }
        return $this->movie;
    }

    /**
     * Saves new model to database
     * @return Vote
     */
    public function create(): self
    {
        $id = $this->db->insert(self::table(), [
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


        if (!self::isValidVote($this->vote_type)) {
            throw new InvalidArgumentException('Invalid vote');
        }
        $this->getMovie();

        // If no existing vote create new vote
        if (!$existing) {
            $this->created_at = $now;
            $this->updated_at = null;

            try {
                $this->db->beginTransaction();
                $this->create();

                if ($this->vote_type === 1) {
                    $this->movie->incrementUpvotes();
                } else {
                    $this->movie->incrementDownvotes();
                }
                $this->db->commit();
            } catch (Exception $e) {
                    $this->db->rollBack();
                    throw $e;
            }
            return true;
        }

        // If the vote is the same, we don't have to do anything
        if ((int)$existing['vote_type'] === $this->vote_type) {
            throw new RuntimeException('Vote already set');
        }

        try {
            $this->db->beginTransaction();
            // If the vote changed, adjust
            if ($existing['vote_type'] === 1 && $this->vote_type === -1) {
                $this->movie->decrementUpvotes();
                $this->movie->incrementDownvotes();
            } elseif ($existing['vote_type'] === -1 && $this->vote_type === 1) {
                $this->movie->decrementDownvotes();
                $this->movie->incrementUpvotes();
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
        return $this->db->delete(self::table(), ['id' => $this->id]);
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

        $this->id = $vote['id'];
        $this->created_at = $vote['created_at'];
        $this->updated_at = $vote['updated_at'];

        return true;
    }

    /**
     * @param int $cooldown
     * @return bool
     */
    public function canVote(): bool
    {
        $config = require __DIR__ . '/../../config/config.php';
        $this->refresh();

        if (empty($this->created_at)) {
            return true;
        }

        $lastVoteTime = $this->updated_at ?? $this->created_at;
        $lastVoteTime = strtotime($lastVoteTime);
        return (time() - $lastVoteTime) >= $config['cooldown'];
    }

    public function isMovieSet()
    {
        $this->getMovie();
        return !is_null($this->movie);
    }

    /**
     * Find vote based on movie_id and session_id
     * @param int $movie_id
     * @param string $session_id
     * @return Vote|null
     */
    public static function find(int $movie_id, string $session_id): ?Vote
    {
        $db = ConnectionManager::get('default');
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
     * Checks to see if vote is valid number
     * @param int $vote
     * @return bool
     */
    public static function isValidVote(int $vote): bool
    {
        return in_array($vote, [1, -1], true);
    }

    public function toArray() {
        return [
            'id' => $this->id,
            'vote_type' => $this->vote_type,
            'movie' => $this->getMovie()->toArray(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}