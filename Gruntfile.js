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
          'app/js/elabftw.min.js': [
              'bower_components/jquery/dist/jquery.js',
              'bower_components/jquery-ui/jquery-ui.js',
              'bower_components/bootstrap/js/alert.js',
              'bower_components/bootstrap/js/button.js',
              'bower_components/bootstrap/js/dropdown.js',
              'bower_components/colorpicker/jquery.colorpicker.js',
              'bower_components/fancybox/dist/jquery.fancybox.js',
              'bower_components/jeditable/jquery.jeditable.js',
              'bower_components/jquery.complexify/jquery.complexify.js',
              'bower_components/jquery.complexify/jquery.complexify.banlist.js',
              'bower_components/keymaster/keymaster.js',
              'app/js/common.js',
              'app/js/cornify.js',
              'app/js/jquery.rating.js',
              'app/js/3Dmol-nojquery.js',
              'app/js/3dmol_helpers.js',
              'app/js/prism.js'],

          'app/js/chemdoodle/chemdoodle.min.js': [
              'app/js/chemdoodle/chemdoodle-unpacked.js',
              'app/js/chemdoodle/chemdoodle-uis-unpacked.js'],

          'app/js/scheduler.min.js': [
              'bower_components/moment/moment.js',
              'bower_components/fullcalendar/dist/fullcalendar.js',
              'bower_components/fullcalendar/dist/locale-all.js'],

          'app/js/edit.mode.min.js': [
              'bower_components/tinymce/tinymce.js',
              'app/js/tinymce-langs/*',
              'bower_components/dropzone/dist/dropzone.js'],

          'app/js/file-saver.min.js': 'bower_components/file-saver.js/FileSaver.js'
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
          'app/css/elabftw.min.css': [
              'app/css/tagcloud.css',
              'app/css/jquery.rating.css',
              'app/css/autocomplete.css',
              'app/css/prism.css',
              'bower_components/dropzone/dist/dropzone.css',
              'bower_components/fullcalendar/dist/fullcalendar.css',
              'bower_components/bootstrap/dist/css/bootstrap.css',
              'bower_components/colorpicker/jquery.colorpicker.css',
              'bower_components/fancybox/dist/jquery.fancybox.css',
              'bower_components/jquery-ui/themes/smoothness/jquery-ui.css',
              'app/css/main.css'],

          'app/css/pdf.min.css': 'app/css/pdf.css'
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
