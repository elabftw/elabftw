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
          banner: '/*! <%= pkg.name %> <%= pkg.version %> <%= pkg.homepage %> */\n',
        mangle: false
      },
      dist: {
        files: {
          'js/elabftw.min.js': ['js/common.js', 'js/cornify.js', 'js/jquery.rating.js', 'js/keymaster/keymaster.js', 'js/fancybox/source/jquery.fancybox.pack.js', 'js/colorpicker/jquery.colorpicker.js', 'js/jeditable/jquery.jeditable.js', 'js/jquery.complexify.js/jquery.complexify.js', 'js/jquery.complexify.js/jquery.complexify.banlist.js', 'js/3Dmol-nojquery.js', 'js/3dmol_helpers.js', 'js/dropzone/dist/min/dropzone.min.js'],
          'js/chemdoodle/chemdoodle.min.js': ['js/chemdoodle/chemdoodle-unpacked.js', 'js/chemdoodle/chemdoodle-uis-unpacked.js']
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
          'app/css/main.min.css': ['app/css/tagcloud.css', 'app/css/jquery.rating.css', 'app/css/autocomplete.css', 'js/dropzone/dist/min/dropzone.min.css', 'js/fullcalendar/dist/fullcalendar.css', 'app/css/main.css'],
          'app/css/pdf.min.css': ['app/css/pdf.css']
        }
      }
    },
    shell: {
      buildapi: {
        command: 'phpdoc run -d app/classes -d app/models -d app/controllers -d app/views -t _api'
      },
      rununit: {
        command: 'php vendor/bin/codecept run unit'
      },
      // xdebug must be DISABLED
      runtests: {
        command: 'php vendor/bin/codecept run --skip functionnal; cp -f config.php.dev config.php'
      },
      // xdebug must be ENABLED
      runcoverage: {
        command: 'php vendor/bin/codecept run --skip acceptance --skip functionnal --coverage --coverage-html'
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-cssmin');
  grunt.loadNpmTasks('grunt-shell');

  grunt.registerTask('default', ['uglify', 'cssmin']);
  grunt.registerTask('css', 'cssmin');
  grunt.registerTask('api', 'shell:buildapi');
  grunt.registerTask('test', 'shell:runtests');
  grunt.registerTask('unit', 'shell:rununit');
  grunt.registerTask('cov', 'shell:runcoverage');

};
