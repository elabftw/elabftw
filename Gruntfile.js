/**
 * Gruntfile.js
 *
 * Run 'grunt' in shell to compile javascript and css files
 *
 */
module.exports = function(grunt) {

  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    uglify: {
      options: {
          banner: '/*! <%= pkg.name %> <%= pkg.homepage %> */\n',
        mangle: false
      },
      dist: {
        files: {
          'web/app/js/chemdoodle.min.js': [
              'src/js/vendor/chemdoodle/chemdoodle-unpacked.js',
              'src/js/vendor/chemdoodle/chemdoodle-uis-unpacked.js'],
        }
      }
    },
  });

  grunt.loadNpmTasks('grunt-contrib-uglify-es');

  // before minifying js it is preferable to do 'yarn install' to update the dependencies
  grunt.registerTask('default', ['uglify']);
};
