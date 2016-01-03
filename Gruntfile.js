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
    },
    shell: {
      builddoc: {
        command: 'cd doc; make html'
      },
      buildapi: {
        command: 'phpdoc run -d inc/classes -t doc/api'
      },
      runtests: {
        command: 'php vendor/bin/codecept run'
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-cssmin');
  grunt.loadNpmTasks('grunt-shell');

  grunt.registerTask('default', ['uglify', 'cssmin']);
  grunt.registerTask('doc', 'shell:builddoc');
  grunt.registerTask('api', 'shell:buildapi');
  grunt.registerTask('test', 'shell:runtests');

};
