<?php
require_once(__DIR__ . '/../../config/bootstrap.php' );
use Models\Movie;
use Models\Vote;

session_start();
$session_id = session_id();

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];


switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            try {
                $id = intval($_GET['id']);
                $movie = Movie::find($id);
                if (!$movie) {
                    throw new InvalidArgumentException('Movie Not Found');
                }
                echo json_encode([
                    'success' => true,
                    'data' => $movie
                ]);
                exit();
            } catch (InvalidArgumentException $e) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
                exit();
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Server error'
                ]);
                exit();
            }
        } else {
            $movies = Movie::all();
            echo json_encode([
                'success' => true,
                'data' => array_map(fn($m) => $m->toArray(), $movies)
            ]);
            exit();
        }
        break;
    case 'POST':
        try {
            $movie_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $vote_value = filter_input(INPUT_POST, 'vote_type', FILTER_VALIDATE_INT);

            if (!is_int($movie_id) || !is_int($vote_value)) {
                throw new InvalidArgumentException('Invalid input');
            }

            // Only allow valid data to be set
            if (!Vote::isValidVote( $vote_value)) {
                throw new InvalidArgumentException('Invalid input');
            }

            $data = [
                'movie_id' => $movie_id,
                'vote_type' => $vote_value,
                'session_id' => $session_id,
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ];
            $vote = new Vote($data);

            if (!$vote->isMovieSet()) {
                throw new InvalidArgumentException('Invalid input');
            }

            if (!$vote->canVote()) {
                throw new RuntimeException('You\'ve already voted recently. Please wait before voting again.');
            }

            $vote->save();

            echo json_encode([
                'success' => true,
                'data' => $vote->toArray()
             ]);
            exit();
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            exit();
        } catch (RuntimeException $e) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            exit();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Server error'
            ]);
            exit();
        }
        break;
    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method Not Allowed'
        ]);
        exit();
        break;
}