<?php

namespace Models;

use Database\ConnectionManager;
use RuntimeException;


/**
 * Movie Model
 *
 * Represents a movie record and provides methods for interacting
 * with the movies table, including CRUD operations and vote management.
 *
 * The model maintains counts of downvotes and upvotes for performance
 * but the votes table remains the source of truth.
 */
class Movie extends Model
{
    /**
     * Table Name
     */
    protected const TABLE = 'movies';

    /**
     * @var array|string[]
     */
    protected array $fillable = [
        'title',
        'description',
        'image_link',
    ];

    /**
     * @var array|string[]
     */
    protected array $attributes = [
        'id',
        'created_at',
        'upvotes',
        'downvotes',
    ];

    /**
     * @var int
     */
    public int $id;

    /**
     * @var string
     */
    public string $title;

    /**
     * @var string
     */
    public string $description;
    public string $image_link;
    public string $created_at;
    public int $upvotes = 0;
    public int $downvotes = 0;

    /**
     * Sets values
     *
     * @param $key
     * @param $value
     * @return void
     */
    protected function transform(string $key, mixed $value): mixed
    {
        if (in_array($key, ['id', 'upvotes', 'downvotes'])) {
            return  (int)$value;
        } elseif ($value !== null) {
            return (string)$value;
        }
    }

    /**
     * Get all movies as Movie objects
     *
     * @returns array
     */
    public static function all(): array
    {
        $db = ConnectionManager::get('default');
        $rows = $db->select(self::table(), [], 'upvotes', 'desc' );
        return array_map(fn($row) => new Movie($row), $rows);
    }

    /**
     * Simple find function to find a Movie by Model
     *
     * @param int $id Id of movie to fetch
     * @return Movie
     */
    public static function find(int $id): ?Movie
    {
        $db = ConnectionManager::get('default');
        $row = $db->select(self::table(), ['id' => $id]);

        return $row ? new Movie($row[0]) : null;
    }

    /**
     * Creates a new movie
     * @return int Last insert ID of model
     */
    public function create(): int
    {
        $id = $this->db->insert(self::table(), [
                'title' => $this->title,
                'description' => $this->description,
                'image_link' => $this->image_link,
                'upvotes' => 0,
                'downvotes' => 0
        ]);

        $this->id = (int) $id;
        return $this->id;
    }

    /**
     * Update current model
     * @return bool
     */
    public function update(): bool
    {
        return $this->db->update(self::table(), [
            'title' => $this->title,
            'description' => $this->description,
            'image_link' => $this->image_link,
        ], ['id' => $this->id]);
    }

    /**
     * Deletes movie in database
     *
     * @returns bool
     */
    public function delete(): bool
    {
        return $this->db->delete(self::table(), ['id' => $this->id]);
    }

    /**
     * Increments count of upvotes in database
     * @return bool
     */
    public function incrementUpvotes(): bool
    {
        $success = $this->db->execute(
            "UPDATE " . self::table() . " SET upvotes = upvotes + 1 WHERE id = :id",
            ['id' => $this->id]
        );

        if (!$success) {
            throw new RuntimeException('Failed to update vote count');
        }

        $this->upvotes++;

        return $success;
    }

    /**
     * Decrement count of upvotes in database
     * @return bool
     */
    public function decrementUpvotes(): bool
    {
        $success = $this->db->execute(
            "UPDATE " . self::table() . " SET upvotes = GREATEST(upvotes - 1, 0) WHERE id = :id",
            ['id' => $this->id]
        );

        if (!$success) {
            throw new RuntimeException('Failed to update vote count');
        }
        $this->upvotes--;

        return $success;
    }

    /**
     * Increwments count of downvotes in database
     * @return bool
     */
    public function incrementDownvotes(): bool
    {
        $success = $this->db->execute(
            "UPDATE " . self::table() . " SET downvotes = downvotes + 1 WHERE id = :id",
            ['id' => $this->id]
        );

        if (!$success) {
            throw new RuntimeException('Failed to update vote count');
        }

        $this->downvotes++;

        return  $success;
    }

    /**
     * Decrement count of downvotes in database
     * @return bool
     */
    public function decrementDownvotes(): bool
    {
        $success = $this->db->execute(
            "UPDATE " . self::table() . " SET downvotes = GREATEST(downvotes - 1, 0) WHERE id = :id",
            ['id' => $this->id]
        );

        if (!$success) {
            throw new RuntimeException('Failed to update vote count');
        }
        $this->downvotes--;
        return $success;
    }

    /**
     * Helper function to update upvotes and downvotes in movies database
     * based on saved values in votes database.
     *
     * @return void
     */
    public static function rebuildVoteCounts(): void
    {
        $db = ConnectionManager::get('default');

        $sql = "
        UPDATE movies m
        LEFT JOIN (
            SELECT 
                movie_id,
                SUM(CASE WHEN vote_type = 1 THEN 1 ELSE 0 END) AS upvotes,
                SUM(CASE WHEN vote_type = -1 THEN 1 ELSE 0 END) AS downvotes
            FROM votes
            GROUP BY movie_id
        ) v ON v.movie_id = m.id
        SET 
            m.upvotes = COALESCE(v.upvotes, 0),
            m.downvotes = COALESCE(v.downvotes, 0)
    ";

        $db->execute($sql);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'image_link' => $this->image_link,
            'upvotes' => $this->upvotes,
            'downvotes' => $this->downvotes,
            'created_at' => $this->created_at,
        ];
    }
}