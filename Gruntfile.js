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
		main: {
			options: {
				archive: 'dist/<%= pkg.name %>.zip'
			},
		files: [{
			src: [
				'**',
				'!*~', 
				'!**/dist/**', '!**/.git/**', '!**/node_modules/**','!**/apigen/**', '!**/documentation/**',  
				'!package.json', 
				'!Gruntfile.js'] 
			}]
  		},
		version: {
			options: {
				archive: 'dist/<%= pkg.name %>-<%= pkg.version %>.zip'
			},
			files: [{
				src: [
					'**',
					'!*~', 
					'!**/dist/**', '!**/.git/**', '!**/node_modules/**','!**/apigen/**', '!**/documentation/**',  
					'!package.json', 
					'!Gruntfile.js'] 
			}]
		}	
  	},
	wp_readme_to_markdown: {
		convert:{
			files: {
				'readme.md': 'readme.txt'
			},
		},
	},
});



grunt.loadNpmTasks('grunt-shell');

grunt.loadNpmTasks('grunt-contrib-uglify');

grunt.loadNpmTasks('grunt-contrib-compress');

grunt.loadNpmTasks('grunt-contrib-jshint');

grunt.loadNpmTasks('grunt-wp-readme-to-markdown');

 // Default task(s).
grunt.registerTask('default', ['uglify']);

grunt.registerTask('docs', ['shell:makeDocs']);

grunt.registerTask('readme', ['wp_readme_to_markdown']);
};
