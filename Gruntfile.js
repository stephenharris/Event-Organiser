module.exports = function(grunt) {

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
      			files: {
        			'js/frontend.min.js': ['js/frontend.js'],
        			'js/fullcalendar.min.js': ['js/fullcalendar.js'],
        			'js/event.min.js': ['js/event.js'],
        			'js/venues.min.js': ['js/venues.js'],
        			'js/admin-calendar.min.js': ['js/admin-calendar.js'],
        			'js/time-picker.min.js': ['js/time-picker.js'],
        			'js/edit-event-controller.min.js': ['js/edit-event-controller.js'],
      			}
		}
	},
	jshint: {
		options: {
			globals: {
				"EO_SCRIPT_DEBUG": false,
			},
			 '-W014': true,
			 '-W015': true,
			 '-W099': true,
			 '-W033': true,
			'-W083': true,//functions within loop
			'-W020': true, //Read only - error when assigning EO_SCRIPT_DEBUG a value.
		},
		all: [ 'js/*.js', '!js/*.min.js', '!*/time-picker.js',  '!*/fullcalendar.js', '!*/venues.js', '!*/qtip2.js' ]
  	},
	shell: {                                // Task
        	makeDocs: {                      // Target
			options: {                      // Options
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
				'!.gitmodules'
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
	}
});

grunt.loadNpmTasks('grunt-shell');

grunt.loadNpmTasks('grunt-contrib-uglify');

grunt.loadNpmTasks('grunt-contrib-compress');

grunt.loadNpmTasks( 'grunt-contrib-clean' );

grunt.loadNpmTasks( 'grunt-contrib-copy' );

grunt.loadNpmTasks('grunt-contrib-jshint');

grunt.loadNpmTasks('grunt-wp-readme-to-markdown');

grunt.loadNpmTasks('grunt-phpunit');

 // Default task(s).
grunt.registerTask('default', ['uglify']);

grunt.registerTask('docs', ['shell:makeDocs']);

grunt.registerTask('readme', ['wp_readme_to_markdown']);

grunt.registerTask( 'build', [ 'clean', 'copy', 'compress'] );
};
