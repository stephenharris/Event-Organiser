module.exports = function(grunt) {

  require('load-grunt-tasks')(grunt);
	
  // Project configuration.
  grunt.initConfig({
	pkg: grunt.file.readJSON('package.json'),
	uglify: {
		options: {
			compress: {
				global_defs: {
					"EO_SCRIPT_DEBUG": false
				},
				dead_code: true
      			},
			banner: '/*! <%= pkg.name %> <%= pkg.version %> <%= grunt.template.today("yyyy-mm-dd HH:MM") %> */\n'
		},
		build: {
			files: [{
				expand: true,     // Enable dynamic expansion.
				src: ['js/*.js', '!js/*.min.js', '!js/qtip2.js', '!js/inline-help.js', '!js/eventorganiser-pointer.js'], // Actual pattern(s) to match.
				ext: '.min.js',   // Dest filepaths will have this extension.
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
			 '-W083': true,//TODO Fix functions within loop
			 '-W082': true, //Todo Function declarations should not be placed in blocks
			 '-W020': true, //Read only - error when assigning EO_SCRIPT_DEBUG a value.
		},
		all: [ 'js/*.js', '!js/*.min.js', '!*/moment.js', '!*/time-picker.js', '!*/jquery-ui-eo-timepicker.js', '!*/fullcalendar.js', '!*/venues.js', '!*/qtip2.js' ]
  	},
    phpcs: {
        application: {
            src: [
                  '**/*.php',
                  '!node_modules/**',
                  '!dist/**',
                  '!apigen/**',
                  '!documentation/**',
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
        	standard: 'codesniff'
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
				processContent: function( src ) {
					return src.replace( /url\((.+?)\.css\)/g, 'url($1-rtl.css)' );
				}
			},
			expand: true,
			ext: '-rtl.css',
			src: [ 'css/*.css', '!**/*.min.css', '!**/*-rtl.css', '!**/fullcalendar.css' ]
		}
	},
  	
	cssmin: {
		minify: {
			expand: true,
			src: [ 'css/*.css', '!**/*.min.css' ],
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
				archive: './dist/event-organiser-<%= pkg.version %>.zip'
			},
			expand: true,
			cwd: 'dist/event-organiser/',
			src: ['**/*'],
			dest: 'event-organiser/'
		}	
	},

	clean: {
		main: ['dist/event-organiser'],//Clean up build folder
		css: [ 'css/*.min.css', 'css/*-rtl.css' ],
		js: [ 'js/*.min.js' ],
		i18n: [ 'languages/*.mo', 'languages/*.pot' ] 
	},

	copy: {
		// Copy the plugin to a versioned release directory
		main: {
			src:  [
				'**',
				'!node_modules/**','!Gruntfile.js','!package.json', //npm/Grunt
				'!assets/**', //wp-org assets
				'!dist/**', //build directory
				'!.git/**', //version control
				'!apigen/**', '!documentation/**', //docs
				'!tests/**','!bin/**','!phpunit.xml', //unit test
				'!features/**', '!behat.yml',//behat test
				'!vendor/**','!composer.lock','!composer.phar','!composer.json', //composer
				'!.*','!**/*~', //hidden files
				'!CONTRIBUTING.md',
				'!readme.md',
				'!codesniff/**','!phpmd.xml', //CodeSniffer & Mess Detector
				'!css/images/**/*.xcf', //source images
			],
			dest: 'dist/event-organiser/'
		}
	},

	wp_readme_to_markdown: {
		convert:{
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
		}
	},
	
    checkrepo: {
    	deploy: {
            tag: {
                eq: '<%= pkg.version %>',    // Check if highest repo tag is equal to pkg.version
            },
            tagged: true, // Check if last repo commit (HEAD) is not tagged
            clean: true,   // Check if the repo working directory is clean
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
    	    tasks: ['newer:jshint','newer:uglify'],
    	    options: {
    	      spawn: false,
    	    },
    	  },
    },
    
    wp_deploy: {
    	deploy:{
            options: {
        		svn_user: 'stephenharris',
        		plugin_slug: 'event-organiser',
        		build_dir: 'dist/event-organiser/',
        		assets_dir: 'assets/',
        		max_buffer: 1024*1024
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
    	options:{
        	text_domain: 'eventorganiser',
        	dest: 'languages/',
        	msgmerge: true,
			keywords: ['__:1',
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
    	files:{
    		src:  [
    		  '**/*.php',
    		  '!node_modules/**',
    		  '!dist/**',
    		  '!apigen/**',
    		  '!documentation/**',
    		  '!tests/**',
    		  '!vendor/**',
    		  '!*~',
    		],
    		expand: true,
    	}
    },

    checktextdomain: {
    	options:{
			text_domain: 'eventorganiser',
			keywords: ['__:1,2d',
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
			src:  [
				'**/*.php',
				'!node_modules/**',
				'!dist/**',
				'!apigen/**',
				'!documentation/**',
				'!tests/**',
				'!vendor/**',
				'!*~',
				'!templates/**',
				'!classes/class-eo-venue-list-table.php'
			],
			expand: true,
		},
    },

});

grunt.registerTask( 'docs', ['shell:makeDocs']);

grunt.registerTask( 'test', [ 'phpunit', 'jshint' ] );

grunt.registerTask( 'test_build', [ 'clean', 'uglify', 'cssjanus', 'cssmin', 'copy' ] );

grunt.registerTask( 'build', [ 'test', 'clean', 'uglify', 'cssjanus', 'cssmin', 'pot', 'po2mo', 'wp_readme_to_markdown', 'copy' ] );

grunt.registerTask( 'deploy', [ 'checkbranch:master', 'checkrepo:deploy', 'build', 'wp_deploy',  'compress' ] );

};
