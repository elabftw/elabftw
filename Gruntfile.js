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
          'web/assets/vendor.min.js': [
              'src/js/vendor/keymaster.js',
              'src/js/vendor/cornify.js',
              'src/js/vendor/jquery.rating.js',
          ],

          'web/app/js/chemdoodle.min.js': [
              'src/js/vendor/chemdoodle/chemdoodle-unpacked.js',
              'src/js/vendor/chemdoodle/chemdoodle-uis-unpacked.js'],

          'web/app/js/chemdoodle-canvas.min.js': 'web/app/js/src/chemdoodle-canvas.js',

          'web/assets/bootstrap-markdown.min.js': [
              'node_modules/marked/lib/marked.js',
              'node_modules/bootstrap-markdown-fa5/js/bootstrap-markdown.js',
              'node_modules/bootstrap-markdown-fa5/locale/*' ],
        }
      }
    },
    cssmin: {
      target: {
        files: {
          'web/app/css/pdf.min.css': 'web/app/css/pdf.css',
          'web/app/css/bootstrap-markdown.min.css': 'node_modules/bootstrap-markdown/css/bootstrap-markdown.min.css',
          'web/app/css/tinymce/skin.min.css': 'node_modules/tinymce/skins/ui/oxide/skin.css',
          'web/app/css/tinymce/content.min.css': 'node_modules/tinymce/skins/ui/oxide/content.css',
          'web/app/css/tinymce/content.mobile.min.css': 'node_modules/tinymce/skins/ui/oxide/content.mobile.css',
          'web/app/css/tinymce/skin.mobile.min.css': 'node_modules/tinymce/skins/ui/oxide/skin.mobile.css',
        }
      }
    },
  });

  grunt.loadNpmTasks('grunt-contrib-uglify-es');
  grunt.loadNpmTasks('grunt-contrib-cssmin');

  // before minifying js it is preferable to do 'yarn install' to update the dependencies
  grunt.registerTask('default', ['uglify', 'cssmin']);
  grunt.registerTask('css', 'cssmin');
};
