
<?php

require_once __DIR__ . '/database.php';

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

    protected array $readonly = [
        'id',
        'created_at',
        'updated_at'
    ];

    public int $id;
    public int $movie_id;
    public int $vote_type;
    public string $ip_address;
    public string $session_id;
    public $created_at;
    public $updated_at;

    public function __construct(array $data = [])
    {
        $this->db = Database::instance();

        if (!empty($data)) {
            $this->fill($data);
        }
    }


    private function fill(array $data): void
    {
        foreach ($data as $key => $value) {
            if (!in_array($key, $this->fillable, true) && !in_array($key, $this->readonly, true)) {
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
     * Find Votes for single movie
     */
    public static function findMovieVotes(int $movie_id): array
    {
        $db = Database::instance();

        $results = $db->select(self::table(), ['movie_id' => $movie_id] );
        $votes = [];
        foreach ($results as $result) {
            $votes[] = new Vote($result);
        }
        return $votes;
    }

    public function create(): string
    {
        return $this->db->insert(self::TABLE, [
            'movie_id' => $this->movie_id,
            'vote_type' => $this->vote_type,
            'ip_address' => $this->ip_address,
            'session_id' => $this->session_id,
        ]);
    }

    public function save(): bool
    {
        $now = date('Y-m-d H:i:s');
        $existing = self::find($this->movie_id, $this->session_id);
        $movie = new Movie(['id' => $this->movie_id]);

        // If no existing vote create new vote
        if (!$existing) {
            $this->created_at = $now;
            $this->updated_at = null;

            $this->create();

            if ($this->vote_type === 1) {
                $movie->incrementUpvotes();
            } else {
                $movie->incrementDownvotes();
            }

            return true;
        }

        // If the vote is the same, we don't have to do anything
        if ($existing->vote_type === $this->vote_type) {
            return false;
        }

        // If the vote changed, adjust
        if ($existing->vote_type === 1 && $this->vote_type === -1) {
            $movie->decrementUpvotes();
            $movie->incrementDownvotes();
        } elseif ($existing->vote_type === -1 && $this->vote_type === 1) {
            $movie->decrementDownvotes();
            $movie->incrementUpvotes();
        }


        // update vote
        $this->updated_at = $now;

        $this->db->update(self::table(), [
            'vote_type' => $this->vote_type,
            'updated_at' => $now
        ], [
            'movie_id' => $this->movie_id,
            'session_id' => $this->session_id
        ]);

        return true;
    }

    public function delete(): bool
    {
        return $this->db->delete(self::TABLE, ['id' => $this->id]);
    }

    public function loadExisting(): bool
    {
        $vote =  self::find($this->movie_id, $this->session_id);
        if (empty($vote)) {
            return false;
        }

        $this->id = $vote->id;
        $this->created_at = $vote->created_at;

        return true;
    }
    public function canVote(int $cooldown = 2): bool
    {
        $this->loadExisting();

        if (empty($this->created_at)) {
            return true;
        }

        $lastVoteTime = strtotime($this->created_at);
        return (time() - $lastVoteTime) >= $cooldown;
    }

    public static function find(int $movie_id, string $session_id): ?Vote
    {
        $db = Database::instance();
        $rows = $db->select(self::table(), [
            'movie_id' => $movie_id,
            'session_id' => $session_id
        ]);

        if (empty($rows)) {
            return null;
        }

        return new Vote($rows[0]);
    }

    public static function table(): string
    {
        return self::TABLE;
    }

    public static function isValidVote($vote): bool
    {
        return in_array((int)$vote, [1, -1], true);
    }
}