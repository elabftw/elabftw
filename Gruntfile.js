module.exports = function(grunt) {

  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    uglify: {
      options: {
        banner: '/*! <%= pkg.name %> <%= grunt.template.today("dd-mm-yyyy") %> */\n'
      },
      dist: {
        files: {
          'js/common.min.js': ['js/common.js'],
          'js/cornify.min.js': ['js/cornify.js'],
          'js/jquery.rating.min.js': ['js/jquery.rating.js'],
          'js/keymaster/keymaster.min.js': ['js/keymaster/keymaster.js'],
          'js/todolist.min.js': ['js/todolist.js'],
          'js/colorpicker/jquery.colorpicker.min.js': ['js/colorpicker/jquery.colorpicker.js']
        }
      }
    },
    watch: {
      files: ['<%= uglify.files %>'],
      tasks: ['uglify']
    }
  });

  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-watch');

  grunt.registerTask('default', ['uglify']);

};
