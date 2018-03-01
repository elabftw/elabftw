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
          'web/app/js/elabftw.min.js': [
              'node_modules/vanderlee-colorpicker/jquery.colorpicker.js',
              'node_modules/jquery-jeditable/src/jquery.jeditable.js',
              'web/app/js/common.js',
              'web/app/js/cornify.js',
              'web/app/js/jquery.rating.js',
              'web/app/js/3Dmol-nojquery.js',
              'web/app/js/3Dmol-helpers.js',
              'web/app/js/prism.js'],

          'web/app/js/chemdoodle/chemdoodle.min.js': [
              'web/app/js/chemdoodle/chemdoodle-unpacked.js',
              'web/app/js/chemdoodle/chemdoodle-uis-unpacked.js'],

          'web/app/js/scheduler.min.js': [
              'node_modules/moment/moment.js',
              'node_modules/fullcalendar/dist/fullcalendar.js',
              'node_modules/fullcalendar/dist/locale-all.js'],
          'web/app/js/team.min.js': 'web/app/js/team.js',

          'web/app/js/tinymce-dropzone.min.js': [
              'node_modules/tinymce/tinymce.js',
              'web/app/js/tinymce-langs/*',
              'node_modules/dropzone/dist/dropzone.js'],

          'web/app/js/file-saver.min.js': 'node_modules/file-saver/FileSaver.js',
          'web/app/js/admin.min.js': 'web/app/js/admin.js',
          'web/app/js/view.min.js': [
              'node_modules/@fancyapps/fancybox/dist/jquery.fancybox.js',
              'web/app/js/view.js'],
          'web/app/js/tabs.min.js': 'web/app/js/tabs.js',
          'web/app/js/sysconfig.min.js': 'web/app/js/sysconfig.js',
          'web/app/js/footer.min.js': 'web/app/js/footer.js',
          'web/app/js/todolist.min.js': 'web/app/js/todolist.js',
          'web/app/js/login.min.js': 'web/app/js/login.js',
          'web/app/js/register.min.js': [
              'node_modules/jquery.complexify/jquery.complexify.js',
              'node_modules/jquery.complexify/jquery.complexify.banlist.js',
              'web/app/js/register.js' ],
          'web/app/js/change-pass.min.js': 'web/app/js/change-pass.js',
          'web/app/js/show.min.js': 'web/app/js/show.js',
          'web/app/js/edit.min.js': 'web/app/js/edit.js',
          'web/app/js/search.min.js': 'web/app/js/search.js',
          'web/app/js/ucp.min.js': 'web/app/js/ucp.js',
          'web/app/js/profile.min.js': 'web/app/js/profile.js',
          'web/app/js/uploads.min.js': 'web/app/js/uploads.js',
          'web/app/js/doodle.min.js': 'web/app/js/doodle.js',
          'web/app/js/bootstrap-markdown.min.js': [
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
          'web/app/css/elabftw.min.css': [
              'web/app/css/tagcloud.css',
              'web/app/css/jquery.rating.css',
              'web/app/css/prism.css',
              'node_modules/dropzone/dist/dropzone.css',
              'node_modules/fullcalendar/dist/fullcalendar.css',
              'node_modules/bootstrap/dist/css/bootstrap.css',
              'node_modules/vanderlee-colorpicker/jquery.colorpicker.css',
              'node_modules/@fancyapps/fancybox/dist/jquery.fancybox.css',
              'node_modules/jquery-ui-dist/jquery-ui.css',
              'web/app/css/main.css'],

          'web/app/css/pdf.min.css': 'web/app/css/pdf.css',

          'web/app/css/bootstrap-markdown.min.css': 'node_modules/bootstrap-markdown/css/bootstrap-markdown.min.css'
        }
      }
    },
    shell: {
      buildapi: {
        command: 'phpdoc run -d src/classes -d src/models -d web/app/controllers -d src/views -t _api'
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
