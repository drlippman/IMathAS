//This file determines the correspondence between the .less file and
//the .css file.
//See http://gruntjs.com/configuring-tasks#building-the-files-object-dynamically
module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        less: {
            build: {
                files: {
                    'imascore.css': 'less/imascore.less'
                }
            },
			dynamic: {
				options: {
						paths: ['less/'],
				},
				files: [
					{
						expand: true,     // Enable dynamic expansion.
						src: ['themes/less/*.less'],
						dest: 'themes/',
						flatten: true,
						ext: '.css',
					}
				]
			}
        }

    });

    //grunt.loadNpmTasks("grunt-contrib-jshint");
    //grunt.loadNpmTasks("grunt-contrib-uglify");
    grunt.loadNpmTasks('grunt-contrib-less');
    //grunt.loadNpmTasks("grunt-contrib-cssmin");
    //grunt.loadNpmTasks("grunt-contrib-watch");
};
