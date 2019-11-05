module.exports = function(grunt) {

    require('load-grunt-tasks')(grunt);

    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        gitinfo: {
            commands: {
                'local.tag.current.name': ['name-rev', '--tags', '--name-only', 'HEAD'],
                'local.tag.current.nameLong': ['describe', '--tags', '--long']
            }
        },

        uglify: {
            options: {
                compress: {
                    global_defs: {
                        "EO_SCRIPT_DEBUG": false
                    },
                    dead_code: true
                },
                banner: '/*! <%= pkg.name %> <%= gitinfo.local.tag.current.nameLong %> <%= grunt.template.today("yyyy-mm-dd HH:MM") %> */\n'
            },
            build: {
                files: [{
                    expand: true, // Enable dynamic expansion.
                    src: ['js/*.js', '!js/*.min.js', '!js/qtip2.js', '!js/inline-help.js', '!js/eventorganiser-pointer.js'], // Actual pattern(s) to match.
                    ext: '.min.js', // Dest filepaths will have this extension.
                }]
            }
        },

        jshint: {
            options: {
                reporter: require('jshint-stylish'),
                globals: {
                    "EO_SCRIPT_DEBUG": false,
                },
                '-W099': true, //Mixed spaces and tabs
                '-W083': true, //TODO Fix functions within loop
                '-W082': true, //Todo Function declarations should not be placed in blocks
                '-W020': true, //Read only - error when assigning EO_SCRIPT_DEBUG a value.
            },
            build: ['Gruntfile.js'],
            code: ['js/*.js', '!js/*.min.js', '!*/moment.js', '!*/time-picker.js', '!*/jquery-ui-eo-timepicker.js', '!*/fullcalendar.js', '!*/venues.js', '!*/qtip2.js']
        },

        phpcs: {
            application: {
                src: [
                    '**/*.php',
                    '!node_modules/**',
                    '!dist/**',
                    '!docker/**',
                    '!tests/**',
                    '!features/**',
                    '!vendor/**',
                    '!*~',
                ]
            },
            options: {
                report: 'summary',
                bin: './vendor/bin/phpcs',
                showSniffCodes: true,
            }
        },

        phpmd: {
            application: {
                dir: 'includes,classes'
            },
            options: {
                reportFormat: 'text',
                bin: './vendor/bin/phpmd',
                rulesets: 'phpmd.xml'
            }
        },

        cssjanus: {
            core: {
                options: {
                    swapLtrRtlInUrl: false,
                    processContent: function(src) {
                        return src.replace(/url\((.+?)\.css\)/g, 'url($1-rtl.css)');
                    }
                },
                expand: true,
                ext: '-rtl.css',
                src: ['css/*.css', '!**/*.min.css', '!**/*-rtl.css', '!**/fullcalendar.css']
            }
        },

        cssmin: {
            minify: {
                expand: true,
                src: ['css/*.css', '!**/*.min.css'],
                ext: '.min.css',
                extDot: 'last'
            }
        },

        shell: {
            makeDocs: {
                options: {
                    stdout: true
                },
                command: 'apigen --config apigen/apigen.conf'
            },
        },

        compress: {
            //Compress build/event-organiser
            main: {
                options: {
                    mode: 'zip',
                    archive: './dist/event-organiser.zip'
                },
                expand: true,
                cwd: 'dist/event-organiser/',
                src: ['**/*'],
                dest: 'event-organiser/'
            },
            version: {
                options: {
                    mode: 'zip',
                    archive: './dist/event-organiser-<%= gitinfo.local.tag.current.name %>.zip'
                },
                expand: true,
                cwd: 'dist/event-organiser/',
                src: ['**/*'],
                dest: 'event-organiser/'
            }
        },

        clean: {
            main: ['dist/event-organiser'], //Clean up build folder
            css: ['css/*.min.css', 'css/*-rtl.css'],
            js: ['js/*.min.js'],
            i18n: ['languages/*.mo', 'languages/*.pot']
        },

        copy: {
            // Copy the plugin to a versioned release directory
            main: {
                src: [
                    '**',
                    '!*.xml', '!*.log', //any config/log files
                    '!node_modules/**', '!Gruntfile.js', '!package.json', '!package-lock.json', //npm/Grunt
                    '!assets/**', //wp-org assets
                    '!dist/**', //build directory
                    '!.git/**', //version control
                    '!docker/**', '!docker-compose.yml', //docker
                    '!tests/**', '!bin/**', '!phpunit.xml', //unit test
                    '!features/**', '!behat.yml', //behat test
                    '!vendor/**', '!composer.lock', '!composer.phar', '!composer.json', //composer
                    '!.*', '!**/*~', //hidden files
                    '!CONTRIBUTING.md',
                    '!readme.md',
                    '!phpcs.xml', '!phpmd.xml', //CodeSniffer & Mess Detector
                    '!css/images/**/*.xcf', //source images
                ],
                dest: 'dist/event-organiser/',
                options: {
                    processContentExclude: ['**/*.{png,gif,jpg,ico,mo}'],
                    processContent: function(content, srcpath) {
                        if (srcpath == 'readme.txt' || srcpath == 'event-organiser.php') {
                            if (grunt.config.get('gitinfo').local.tag.current.name !== 'undefined') {
                                content = content.replace('{{version}}', grunt.config.get('gitinfo').local.tag.current.name);
                            } else {
                                content = content.replace('{{version}}', grunt.config.get('gitinfo').local.tag.current.nameLong);
                            }
                        }
                        return content;
                    },
                },
            }
        },

        wp_readme_to_markdown: {
            convert: {
                files: {
                    'readme.md': 'readme.txt'
                },
                options: {
                    'screenshot_url': 'assets/{screenshot}.png'
                }
            }
        },

        phpunit: {
            classes: {
                dir: 'tests/unit-tests'
            },
            options: {
                bin: 'vendor/bin/phpunit',
            }
        },

        watch: {
            readme: {
                files: ['readme.txt'],
                tasks: ['wp_readme_to_markdown'],
                options: {
                    spawn: false,
                },
            },
            scripts: {
                files: ['js/*.js'],
                tasks: ['newer:jshint', 'newer:uglify'],
                options: {
                    spawn: false,
                },
            },
        },

        wp_deploy: {
            deploy: {
                options: {
                    svn_user: 'jenkinspress',
                    plugin_slug: 'event-organiser',
                    build_dir: 'dist/event-organiser/',
                    assets_dir: 'assets/',
                    max_buffer: 1024 * 1024,
                    skip_confirmation: true
                },
            }
        },

        po2mo: {
            files: {
                src: 'languages/*.po',
                expand: true,
            },
        },

        pot: {
            options: {
                text_domain: 'eventorganiser',
                dest: 'languages/',
                msgmerge: true,
                keywords: [
										'__:1',
                    '_e:1',
                    '_x:1,2c',
                    'esc_html__:1',
                    'esc_html_e:1',
                    'esc_html_x:1,2c',
                    'esc_attr__:1',
                    'esc_attr_e:1',
                    'esc_attr_x:1,2c',
                    '_ex:1,2c',
                    '_n:1,2,4d',
                    '_nx:1,2,4c',
                    '_n_noop:1,2',
                    '_nx_noop:1,2,3c',
                    'ngettext:1,2'
                ],
            },
            files: {
                src: [
                    '**/*.php',
                    '!node_modules/**',
                    '!dist/**',
                    '!docker/**',
                    '!tests/**',
                    '!vendor/**',
                    '!*~'
                ],
                expand: true,
            }
        },

        upload_pot: {
            options: {
                pot: 'languages/eventorganiser.pot',
                catalogueID: 10,
                consumer: {
                    key: process.env.POPRESS_CONSUMER_KEY,
                    secret: process.env.POPRESS_CONSUMER_SECRET
                },
                access: {
                    key: process.env.POPRESS_ACCESS_KEY,
                    secret: process.env.POPRESS_ACCESS_SECRET
                }
            }
        },

        checkrepo: {
            deploy: {
                tagged: true, // Check that the last commit (HEAD) is tagged
                clean: true // Check that working directory is clean
            }
        },

        checktextdomain: {
            options: {
                text_domain: 'eventorganiser',
                keywords: [
										'__:1,2d',
                    '_e:1,2d',
                    '_x:1,2c,3d',
                    'esc_html__:1,2d',
                    'esc_html_e:1,2d',
                    'esc_html_x:1,2c,3d',
                    'esc_attr__:1,2d',
                    'esc_attr_e:1,2d',
                    'esc_attr_x:1,2c,3d',
                    '_ex:1,2c,3d',
                    '_x:1,2c,3d',
                    '_n:1,2,4d',
                    '_nx:1,2,4c,5d',
                    '_n_noop:1,2,3d',
                    '_nx_noop:1,2,3c,4d'
                ],
            },
            files: {
                src: [
                    '**/*.php',
                    '!node_modules/**',
                    '!dist/**',
                    '!tests/**',
                    '!docker/**',
                    '!features/**',
                    '!vendor/**',
                    '!*~',
                    '!templates/**',
                    '!classes/class-eo-venue-list-table.php'
                ],
                expand: true,
            },
        },

    });

    /**
     * Uploading a POT file i18n.wp-event-organiser.com
     */
    grunt.registerTask('upload_pot', function() {
        var request = require('request');
        var OAuth = require('oauth-1.0a');
        var crypto = require('crypto');
        var fs = require("fs");

        var options = this.options();

        if (!grunt.file.exists(options.pot)) {
            grunt.fatal('POT file "' + options.pot + '" does not exist.');
        }

        var oauth = OAuth({
            consumer: options.consumer,
            signature_method: 'HMAC-SHA1',
            hash_function: function(base_string, key) {
                return crypto.createHmac('sha1', key).update(base_string).digest('base64');
            }
        });

        var request_data = {
            method: 'POST',
            url: 'http://i18n.wp-event-organiser.com/wp-json/popress/v1/catalogue/' + options.catalogueID + '/pot',
            formData: {
                'popress-file-upload': {
                    value: fs.createReadStream(options.pot),
                    options: {
                        filename: 'event-organiser.pot',
                        contentType: null
                    }
                }
            }
        };

        var headers = oauth.toHeader(oauth.authorize(request_data, options.access));
        headers['content-type'] = 'multipart/form-data; boundary=---011000010111000001101001';
        headers['cache-control'] = 'no-cache';
        request_data.headers = headers;
        var done = this.async();
        request(request_data, function(error, response, body) {
            if (200 !== response.statusCode) {
                grunt.fail.warn('POT file not uploaded. (' + response.statusCode + ') ' + body);
            } else {
                response = JSON.parse(body);
                grunt.log.ok('There are ' + response.messages + ' translatable strings.');
            }
            done();
        });

        return;
    });

    grunt.registerTask('docs', ['shell:makeDocs']);

    grunt.registerTask('test', ['jshint']);

    grunt.registerTask('test_build', ['gitinfo', 'clean', 'uglify', 'cssjanus', 'cssmin', 'copy']);

    grunt.registerTask('build', ['gitinfo', 'test', 'clean', 'uglify', 'cssjanus', 'cssmin', 'pot', 'po2mo', 'wp_readme_to_markdown', 'copy']);

    grunt.registerTask('deploy', ['checkbranch:master', 'build', 'wp_deploy']);

};
