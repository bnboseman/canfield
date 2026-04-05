<?php

require __DIR__ . '/database.php';
include __DIR__ . '/votes.php';

/**
 * Movie Model
 *
 * Represents a movie record and provides methods for interacting
 * with the movies table, including CRUD operations and vote management.
 *
 * The model maintains counts of downvotes and upvotes for performance
 * but the votes table remains the source of truth.
 */
class Movie
{
    /**
     * @var Database
     */
    private $db;

    /**
     * Table Name
     */
    private const TABLE = 'movies'; // Table Name

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
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->db = Database::instance();

        if (!empty($data)) {
            $this->fill($data);
        }
    }

    /**
     * Function that accepts a data array and populates class with values
     * @param array{
     *      title?: string,
     *      description?: string,
     *      image_link?: string,
     *      created_at?: string
     * } $data
     * * @return void
     */
    private function fill(array $data): void
    {
        foreach ($data as $key => $value) {
            if (!in_array($key, $this->fillable, true) && !in_array($key, $this->attributes, true)) {
                continue;
            }

            if (in_array($key, ['id', 'upvotes', 'downvotes'])) {
                $this->$key = (int)$value;
            } elseif ($value !== null) {
                $this->$key = (string)$value;
            }
        }
    }

    /**
     * Get all movies as Movie objects
     *
     * @returns array
     */
    public static function all(): array
    {
        $db = Database::instance();

        $sql = "
            SELECT
                m.id,
                m.title,
                m.description,
                m.image_link,
                m.upvotes,
                m.downvotes,
                m.created_at
            FROM " . self::table() . " m
            ORDER BY upvotes DESC
        ";



        $rows = $db->query($sql);

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
        $db = Database::instance();

        $sql = "
            SELECT
                m.id,
                m.title,
                m.description,
                m.image_link,
                m.created_at,
                m.upvotes,
                m.downvotes
            FROM " . self::table() . " m
            WHERE m.id = :id
        ";

        $row = $db->query($sql, ['id' => $id]);

        return $row ? new Movie($row[0]) : null;
    }

    /**
     * Creates a new movie
     * @return string
     */
    public function create(): string
    {
        return $this->db->insert(self::TABLE, [
                'title' => $this->title,
                'description' => $this->description,
                'image_link' => $this->image_link,
                'upvotes' => 0,
                'downvotes' => 0
        ]);
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
     * @return void
     */
    public function incrementUpvotes(): void
    {
        $this->db->execute(
            "UPDATE " . self::table() . " SET upvotes = upvotes + 1 WHERE id = :id",
            ['id' => $this->id]
        );
        $this->upvotes++;
    }

    /**
     * Decrement count of upvotes in database
     * @return void
     */
    public function decrementUpvotes(): void
    {
        $this->db->execute(
            "UPDATE " . self::table() . " SET upvotes = upvotes - 1 WHERE id = :id",
            ['id' => $this->id]
        );
        $this->upvotes++;
    }

    /**
     * Increwments count of downvotes in database
     * @return void
     */
    public function incrementDownvotes(): void
    {
        $this->db->execute(
            "UPDATE " . self::table() . " SET downvotes = downvotes + 1 WHERE id = :id",
            ['id' => $this->id]
        );

        $this->downvotes++;
    }

    /**
     * Decrement count of downvotes in database
     * @return void
     */
    public function decrementDownvotes(): void
    {
        $this->db->execute(
            "UPDATE " . self::table() . " SET downvotes = downvotes - 1 WHERE id = :id",
            ['id' => $this->id]
        );
        $this->downvotes--;
    }


    /**
     * Returns table name
     *
     * @return string
     */
    public static function table(): string
    {
        return self::TABLE;
    }

    /**
     * Helper function to update upvotes and downvotes in movies database
     * based on saved values in votes database.
     *
     * @return void
     */
    public static function rebuildVoteCounts(): void
    {
        $db = Database::instance();

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
}