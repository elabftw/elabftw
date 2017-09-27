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
              'node_modules/jquery/dist/jquery.js',
              'node_modules/jquery-ui-dist/jquery-ui.js',
              'node_modules/bootstrap/js/alert.js',
              'node_modules/bootstrap/js/button.js',
              'node_modules/bootstrap/js/collapse.js',
              'node_modules/bootstrap/js/dropdown.js',
              'node_modules/vanderlee-colorpicker/jquery.colorpicker.js',
              'node_modules/keymaster/keymaster.js',
              'app/js/common.js',
              'app/js/cornify.js',
              'app/js/jquery.jeditable.js',
              'app/js/jquery.rating.js',
              'app/js/3Dmol-nojquery.js',
              'app/js/3Dmol-helpers.js',
              'app/js/prism.js'],

          'app/js/chemdoodle/chemdoodle.min.js': [
              'app/js/chemdoodle/chemdoodle-unpacked.js',
              'app/js/chemdoodle/chemdoodle-uis-unpacked.js'],

          'app/js/scheduler.min.js': [
              'node_modules/moment/moment.js',
              'node_modules/fullcalendar/dist/fullcalendar.js',
              'node_modules/fullcalendar/dist/locale-all.js'],
          'app/js/team.min.js': 'app/js/team.js',

          'app/js/tinymce-dropzone.min.js': [
              'node_modules/tinymce/tinymce.js',
              'app/js/tinymce-langs/*',
              'node_modules/dropzone/dist/dropzone.js'],

          'app/js/file-saver.min.js': 'node_modules/file-saver/FileSaver.js',
          'app/js/admin.min.js': 'app/js/admin.js',
          'app/js/view.min.js': [
              'app/js/view.js',
              'node_modules/@fancyapps/fancybox/dist/jquery.fancybox.js'],
          'app/js/tabs.min.js': 'app/js/tabs.js',
          'app/js/sysconfig.min.js': 'app/js/sysconfig.js',
          'app/js/footer.min.js': 'app/js/footer.js',
          'app/js/todolist.min.js': 'app/js/todolist.js',
          'app/js/login.min.js': 'app/js/login.js',
          'app/js/register.min.js': [
              'node_modules/jquery.complexify/jquery.complexify.js',
              'node_modules/jquery.complexify/jquery.complexify.banlist.js',
              'app/js/register.js' ],
          'app/js/change-pass.min.js': 'app/js/change-pass.js',
          'app/js/show.min.js': 'app/js/show.js',
          'app/js/edit.min.js': 'app/js/edit.js',
          'app/js/search.min.js': 'app/js/search.js',
          'app/js/ucp.min.js': 'app/js/ucp.js',
          'app/js/doodle.min.js': 'app/js/doodle.js',
          'app/js/bootstrap-markdown.min.js': [
              'node_modules/markdown/lib/markdown.js',
              'node_modules/bootstrap-markdown/js/bootstrap-markdown.js',
              'node_modules/bootstrap-markdown/locale/*' ]
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
              'app/css/prism.css',
              'node_modules/dropzone/dist/dropzone.css',
              'node_modules/fullcalendar/dist/fullcalendar.css',
              'node_modules/bootstrap/dist/css/bootstrap.css',
              'node_modules/vanderlee-colorpicker/jquery.colorpicker.css',
              'node_modules/@fancyapps/fancybox/dist/jquery.fancybox.css',
              'node_modules/jquery-ui-dist/jquery-ui.css',
              'app/css/main.css'],

          'app/css/pdf.min.css': 'app/css/pdf.css',

          'app/css/bootstrap-markdown.min.css': 'node_modules/bootstrap-markdown/css/bootstrap-markdown.min.css'
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
        command: 'docker run --rm --name selenium -d --net=host selenium/standalone-chrome && php vendor/bin/codecept run --skip functionnal; cp -f config.php.dev config.php; docker stop selenium'
      },
      // xdebug must be ENABLED
      runcoverage: {
        command: 'php vendor/bin/codecept run --skip acceptance --skip functionnal --coverage --coverage-html'
      },
      // run yarn install
      yarninstall: {
        command: 'yarn install'
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-cssmin');
  grunt.loadNpmTasks('grunt-shell');

  // before minifying js it is preferable to do 'yarn install' to update the dependencies
  grunt.registerTask('yarn', 'shell:yarninstall');
  grunt.registerTask('default', ['yarn', 'uglify', 'cssmin']);
  grunt.registerTask('css', 'cssmin');
  grunt.registerTask('api', 'shell:buildapi');
  grunt.registerTask('test', 'shell:runtests');
  grunt.registerTask('unit', 'shell:rununit');
  grunt.registerTask('cov', 'shell:runcoverage');

};
