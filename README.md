# Laravel Word analyzer Setup

1. Clone from the git repository
2. Install project dependencies: composer install
3. I have used predis 2.0.
4. setup the redis connection

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379


5. in database.php file in Redis Databases change client to phpredis

database.php  :  'client' => env('REDIS_CLIENT', 'phpredis'),


6. in cache.php file in driver to redis

'default' => env('CACHE_DRIVER', 'redis'),


7. I used the redis insight for testing.


8. for the API i have included the postman collection.

OR just paste this in the postman configuration :

curl --location 'http://localhost:80/api/word-frequency' \
--header 'Accept: application/json' \
--form 'text="hello ankit how are you fine"' \
--form 'text-file=@"/path/to/file"' \
--form 'top="10"'


9. to start server "php -S localhost:80 -t public"




