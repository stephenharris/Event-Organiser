# Event Organiser Tests

## Setup

### 1. Install docker

See <https://docs.docker.com/install/>

### 2. Download phpunit
Install PHPUnit and other development dependencies via [composer](https://github.com/composer/composer):

    composer install


### 3. Run the container

    docker-compose build --up


### 4. Run the unit tests

    docker exec -it -w /var/www/html/wp-content/plugins/event-organiser eventorg_php ./vendor/bin/phpunit    

### 5. Run the behat tests

     docker exec -it -w /var/www/html/wp-content/plugins/event-organiser eventorg_php mkdir failed-scenerios

     docker exec -it -w /var/www/html/wp-content/plugins/event-organiser eventorg_php ./vendor/bin/behat
    