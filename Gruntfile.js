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
		all: [ 'js/*.js', '!js/*.min.js', '!*/time-picker.js',  '!*/fullcalendar.js', '!*/venues.js', '!*/qtip2.js' ]
  	},
  	
	shell: {
		makeDocs: {
			options: {
				stdout: true
			},
			command: 'apigen --config /var/www/git/event-organiser/apigen/apigen.conf'
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
		//Clean up build folder
		main: ['dist/event-organiser']
	},

	copy: {
		// Copy the plugin to a versioned release directory
		main: {
			src:  [
				'**',
				'!node_modules/**',
				'!dist/**',
				'!.git/**',
				'!apigen/**',
				'!documentation/**',
				'!tests/**',
				'!vendor/**',
				'!Gruntfile.js',
				'!package.json',
				'!.gitignore',
				'!.gitmodules',
				'!*~',
				'!composer.lock',
				'!composer.phar',
				'!composer.json',
				'!CONTRIBUTING.md'
			],
			dest: 'dist/event-organiser/'
		}		
	},

	wp_readme_to_markdown: {
		convert:{
			files: {
				'readme.md': 'readme.txt'
			},
		},
	},

	phpunit: {
		classes: {
			dir: 'tests'
		},
		options: {
			bin: 'vendor/bin/phpunit',
			bootstrap: 'tests/phpunit.php',
			colors: true
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
        		build_dir: 'dist/event-organiser/'
            },
    	}
    },
    
    po2mo: {
    	files: {
        	src: 'languages/*.po',
          expand: true,
        },
    },
});


grunt.registerTask( 'docs', ['shell:makeDocs']);

grunt.registerTask( 'test', [ 'phpunit', 'jshint' ] );

grunt.registerTask( 'build', [ 'test', 'newer:uglify', 'newer:po2mo', 'wp_readme_to_markdown', 'clean', 'copy' ] );

grunt.registerTask( 'deploy', [ 'checkbranch:master', 'checkrepo:deploy', 'build', 'wp_deploy',  'compress' ] );

};