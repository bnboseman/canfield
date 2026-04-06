<?php

require_once(__DIR__ . "/Models/movies.php");

require_once(__DIR__ . "/Models/votes.php");
function randomPublicIp(): string
{
    do {
        $ip = long2ip(random_int(0, 4294967295));
    } while (
        filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false
    );

    return $ip;
}

function generateVotes(int $count = 50)
{
    $movies = Movie::all();
    foreach ($movies as $movie) {

        for ($i = 0; $i <= $count; $i++) {
            $vote = (new Vote([
                'movie_id' => $movie->id,
                'vote_type' => (random_int(1, 100) <= 70) ? 1 : -1,
                'ip_address' => randomPublicIp(),
                'session_id' => bin2hex(random_bytes(16)),
            ]))->create();
        }
    }
}