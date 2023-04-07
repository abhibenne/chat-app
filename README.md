# Basic Chat Backend Application

This backend application simulates chat between users using RESTful APIs.

## Install the Application

1. Install `PHP` and `composer` 
2. An app like `Postman` to send API requests. 

## Usage  

To run the application in development, you can run these commands 

```bash
cd [my-app-name]
composer start
```

Or you can use `docker-compose` to run the app with `docker`, so you can run these commands:
```bash
cd [my-app-name]
docker-compose up -d
```

Or you can use `php -S localhost:8000 -t public`

After that, open `http://localhost:8080` in your browser or place requests from Postman. 

## Functionality

1. GET `/api/users` : Gets all the users in the system.
2. POST `/api/messages` : Send a message from an author to recipient.
3. GET `/api/messages/{username}` : Gets all the messages recieved by `{username}`.
4. GET `/api/users/{userId}` : Gets the specific user for that `{userId}`. 
5. POST `/api/users` : Creates a new user with username, password. 

