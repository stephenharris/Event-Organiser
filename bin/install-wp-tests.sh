#!/usr/bin/env bash

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version]"
	exit 1
fi

parent=$(dirname $PWD)
grandparent=$(dirname $parent)

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}

WP_TESTS_DIR=${WP_TESTS_DIR-/tmp/wordpress-tests-lib}
WP_CORE_DIR=/tmp/wordpress/

if [[ $WP_VERSION =~ [0-9]+\.[0-9]+(\.[0-9]+)? ]]; then
	WP_TESTS_TAG="tags/$WP_VERSION"
elif [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
	WP_TESTS_TAG="trunk"
else
	# http serves a single offer, whereas https serves multiple. we only want one
	wget -nv -O /tmp/wp-latest.json http://api.wordpress.org/core/version-check/1.7/
	LATEST_VERSION=$(grep -o '"version":"[^"]*' /tmp/wp-latest.json | sed 's/"version":"//')
	if [[ -z "$LATEST_VERSION" ]]; then
		echo "Latest WordPress version could not be found"
		exit 1
	fi
	WP_TESTS_TAG="tags/$LATEST_VERSION"
fi

# Exit if anything fails AND echo each command before executing
# http://www.peterbe.com/plog/set-ex
set -ex

install_wp() {
	mkdir -p $WP_CORE_DIR

	if [ $WP_VERSION == 'latest' ]; then 
		wget -nv -O /tmp/wordpress.tar.gz https://wordpress.org/latest.tar.gz
		tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C $WP_CORE_DIR
	elif [ $WP_VERSION == 'nightly' ]; then
		wget -nv -O /tmp/wordpress.zip https://wordpress.org/nightly-builds/wordpress-latest.zip
		unzip -d $WP_CORE_DIR /tmp/wordpress.zip && f=("$WP_CORE_DIR"/*) && mv "$WP_CORE_DIR"/*/* "$WP_CORE_DIR" && rmdir "${f[@]}"
	else
		wget -nv -O /tmp/wordpress.tar.gz "https://wordpress.org/wordpress-$WP_VERSION.tar.gz"
		tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C $WP_CORE_DIR
	fi

	wget -nv -O $WP_CORE_DIR/wp-content/db.php https://raw.github.com/markoheijnen/wp-mysqli/master/db.php	
}

install_db() {

	# parse DB_HOST for port or socket references
	local PARTS=(${DB_HOST//\:/ })
	local DB_HOSTNAME=${PARTS[0]};
	local DB_SOCK_OR_PORT=${PARTS[1]};
	local EXTRA=""

	if ! [ -z $DB_HOSTNAME ] ; then
		if [[ "$DB_SOCK_OR_PORT" =~ ^[0-9]+$ ]] ; then
			EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
		elif ! [ -z $DB_SOCK_OR_PORT ] ; then
			EXTRA=" --socket=$DB_SOCK_OR_PORT"
		elif ! [ -z $DB_HOSTNAME ] ; then
			EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
		fi
	fi

	# reset database
	mysql --user="$DB_USER" --password="$DB_PASS" $EXTRA -e "DROP DATABASE IF EXISTS $DB_NAME";
	mysqladmin --no-defaults create $DB_NAME --user="$DB_USER" --password="$DB_PASS"$EXTRA;

}

install_config() {
	# portable in-place argument for both GNU sed and Mac OSX sed
	if [[ $(uname -s) == 'Darwin' ]]; then
		local ioption='-i .bak'
	else
		local ioption='-i'
	fi

	cp "$WP_CORE_DIR/wp-config-sample.php" "$WP_CORE_DIR/wp-config.php"
	
	sed $ioption "s:dirname(__FILE__) . '/':'$WP_CORE_DIR':" "$WP_CORE_DIR/wp-config.php"
	sed $ioption "s/database_name_here/$DB_NAME/" "$WP_CORE_DIR/wp-config.php"
	sed $ioption "s/username_here/$DB_USER/" "$WP_CORE_DIR/wp-config.php"
	sed $ioption "s/password_here/$DB_PASS/" "$WP_CORE_DIR/wp-config.php"
	sed $ioption "s|localhost|${DB_HOST}|" "$WP_CORE_DIR/wp-config.php"
}

install_test_suite() {
	# portable in-place argument for both GNU sed and Mac OSX sed
	if [[ $(uname -s) == 'Darwin' ]]; then
		local ioption='-i .bak'
	else
		local ioption='-i'
	fi

	# set up testing suite
	mkdir -p $WP_TESTS_DIR
	cd $WP_TESTS_DIR

	svn co --quiet https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/

	wget -nv -O wp-tests-config.php https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php
	sed $ioption "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR':" wp-tests-config.php
	sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" wp-tests-config.php
	sed $ioption "s/yourusernamehere/$DB_USER/" wp-tests-config.php
	sed $ioption "s/yourpasswordhere/$DB_PASS/" wp-tests-config.php
	sed $ioption "s|localhost|${DB_HOST}|" wp-tests-config.php
}

install_wp
install_config
install_test_suite
install_db

rm -rf ${WP_CORE_DIR}wp-content/plugins/*

# Used when waiting for stuff
NAP_LENGTH=1
SELENIUM_PORT=4444

# Wait for a specific port to respond to connections.
wait_for_port() {
    local PORT=$1
    while echo | telnet localhost $PORT 2>&1 | grep -qe 'Connection refused'; do
        echo "Connection refused on port $PORT. Waiting $NAP_LENGTH seconds..."
        sleep $NAP_LENGTH
    done
}

rm -f /tmp/.X0-lock

Xvfb & export DISPLAY=localhost:0.0

echo 'start php';
php -S localhost:8000 -t /tmp/wordpress -d disable_functions=mail > /dev/null 2>&1 &

# Start Selenium
wget http://selenium-release.storage.googleapis.com/2.53/selenium-server-standalone-2.53.0.jar
java -jar selenium-server-standalone-2.53.0.jar -p $SELENIUM_PORT > /dev/null 2>&1 &

# Wait for Selenium, if necessary
wait_for_port $SELENIUM_PORT

echo 'waiting to start tests...';
sleep 5
