<?php

// Include the necessary libraries
require __DIR__ . '\..\vendor\autoload.php';
use Firebase\JWT\JWT;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Create a new Slim app instance
$app = AppFactory::create();

// Create a new Monolog logger instance
$log = new Logger('api');
$log->pushHandler(new StreamHandler(__DIR__ . '\..\logs\app.log', Logger::INFO));

$db = new PDO('sqlite:../db/chat.db');

// Define routes for the API endpoints
$app->group('/api', function (RouteCollectorProxy $group) {

    // Users API
    $group->group('/users', function (RouteCollectorProxy $ugroup) {

        
        // Get all users
        $ugroup->get('', function (Request $request, Response $response) {
        
            global $log, $db;

            // Retrieve all users from the database
            $stmt = $db->query('SELECT id, username FROM users');
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);        
            // Set the response status, headers, and body
            $response = $response->withStatus(200)->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode($users));
            return $response;
        });

        // Get user by ID
        $ugroup->get('/{id}', function ($request, $response, $args) {
            
            global $log, $db;

            // Retrieve the user with the specified ID from the database
            $stmt = $db->prepare('SELECT id, username FROM users WHERE id = :id');
            $stmt->bindParam(':id', $args['id']);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                // If no user is found with the specified ID, set the appropriate response status, headers, and body
                $response = $response->withStatus(404)->withHeader('Content-Type', 'application/json');
                $response->getBody()->write(json_encode(['error' => 'User not found']));
                return $response;
            }

            // If the user is found, set the appropriate response status, headers, and body
            $response = $response->withStatus(200)->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode($user));
            return $response;
        });

        // Create user
        $ugroup->post('', function ($request, $response, $args) {

            global $log, $db;

            // Retrieve the request body data as JSON
            $data = json_decode($request->getBody(), true);

            // Check if all required fields are present
            if (!isset($data['username']) || !isset($data['password'])) {
                // If any required fields are missing, set the appropriate response status, headers, and body
                $response = $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                $response->getBody()->write('{"error": "Missing required fields"}');
                return $response;
            }

            // Extract username and password from request data
            $username = trim($data['username']);
            $password = trim($data['password']);

            // Hash the password for storage in the database
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Check if a user with the same username already exists in the database
            $stmt = $db->prepare('SELECT id, username FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $response = $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                $response->getBody()->write('{"error": "User already exists"}');
                return $response;  
            }

            // Insert user data into the database
            $sql = "INSERT INTO users (username, password) VALUES (:username, :password)";
            $stmt = $db->prepare($sql);;
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->execute();

            // Return success message
            $response = $response->withStatus(200)
                ->withHeader('Content-Type', 'application/json');
            $response->getBody()->write('{"message": "User created successfully"}');
            return $response;
        });

    });

    $group->group('/messages', function (RouteCollectorProxy $mgroup) {
        
        // Get messages for a user
        $mgroup->get('/{username}', function (Request $request, Response $response, $args) {
            
            global $log, $db;

            $log->info('Received request', ['method' => $request->getMethod(), 'path' => $request->getUri()->getPath()]);
        
            $stmt = $db->prepare('SELECT m.message, u.username as author FROM messages m JOIN users u ON m.author_id = u.id WHERE recipient_id = (SELECT id FROM users WHERE username = ?) ORDER BY m.created_at DESC');
            $stmt->execute([$args['username']]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response = $response->withStatus(200)->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode($messages));
            return $response;
        });
        
        // Send a message
        $mgroup->post('', function (Request $request, Response $response, $args) {

            global $log, $db;
            $log->info('Received request', ['method' => $request->getMethod(), 'path' => $request->getUri()->getPath()]);
        
            $data = json_decode($request->getBody(), true);
            $author_id = $data['author_id'] ?? 0;
            $recipient_id = $data['recipient_id'] ?? 0;
            $message = $data['message'] ?? '';
            
            $author_id = intval($author_id);
            $recipient_id = intval($recipient_id);
            
            $stmt = $db->prepare('INSERT INTO messages (author_id, recipient_id, message) VALUES (?, ?, ?)');
            $stmt->execute([$author_id, $recipient_id, $message]);
            $response = $response->withStatus(200)->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode(['message' => 'Message sent']));
            return $response;
        });
        
    });

});


$app->run();

?>
