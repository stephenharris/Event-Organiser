#!/usr/bin/env bash

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

# set up testing suite
mkdir -p $WP_TESTS_DIR
cd $WP_TESTS_DIR
svn co --quiet https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/
wget -nv -O wp-tests-config.php https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php
sed -i "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR':" wp-tests-config.php
sed -i "s/youremptytestdbnamehere/$WORDPRESS_DB_NAME/" wp-tests-config.php
sed -i "s/yourusernamehere/$WORDPRESS_DB_USER/" wp-tests-config.php
sed -i "s/yourpasswordhere/$WORDPRESS_DB_PASSWORD/" wp-tests-config.php
sed -i "s|localhost|${WORDPRESS_DB_HOST}|" wp-tests-config.php