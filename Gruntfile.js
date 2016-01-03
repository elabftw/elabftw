module.exports = function(grunt) {

  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    uglify: {
      options: {
        banner: '/*! <%= pkg.name %> <%= grunt.template.today("dd-mm-yyyy") %> */\n',
        mangle: false
      },
      dist: {
        files: {
          'js/elabftw.min.js': ['js/common.js', 'js/cornify.js', 'js/jquery.rating.js', 'js/keymaster/keymaster.js', 'js/todolist.js', 'js/colorpicker/jquery.colorpicker.js', 'js/jeditable/jquery.jeditable.js', 'js/jquery.complexify.js/jquery.complexify.js', 'js/jquery.complexify.js/jquery.complexify.banlist.js'],
        }
      }
    },
    watch: {
      files: ['<%= uglify.files %>'],
      tasks: ['uglify']
    },
    cssmin: {
      target: {
        files: {
          'css/main.min.css': ['css/main.css', 'css/tagcloud.css', 'css/jquery.rating.css']
        }
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-cssmin');

  grunt.registerTask('default', ['uglify', 'cssmin']);

};
