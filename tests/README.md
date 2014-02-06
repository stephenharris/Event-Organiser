# Event Organiser Unit Tests

Author: [Stephen Harris](http://www.github.com/stephenharris)


## Setup

### 1. Download phpunit
This repository uses Grunt, and includes a `Gruntfile.js` and `package.json`. First you should ensure you have Grunt CLI installed ( `npm install -g grunt-cli`). Then install development & build tools:

      sudo npm install

Next install PHP unit (via [composer](https://github.com/composer/composer))

       composer install


### 2. Download & Setup WordPress Core Developer Repository
A git mirror of the WordPress core developer repository is available at: `git://develop.git.wordpress.org/`.

You'll need to set up unit tests for that repository by creating a `wp-tests-config.php`. WordPress unit-testing requries database access, so you'll need to create a database for that. Note that the test tables are routinely dropped, so you do not want to use a database that is used for production or otherwise in shared use.


### 3. Setup Event Organiser for unit-tests with WordPress
Either place the Event Organiser in the `wp-content/plug-ins/` directory of the above WordPress install or define the environment variable `WP_DEVELOP_DIR` to point to the development repository you downloaded in step 2.


### 4. Run the tests
All linting and unit test can be run with

       grunt tests

The unit tests can be run with

       grunt phpunit
