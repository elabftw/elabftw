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
          'web/app/js/vendor.min.js': [
              'src/js/vendor/keymaster.js',
              'src/js/vendor/cornify.js',
              'src/js/vendor/jquery.rating.js',
          ],

          'web/app/js/chemdoodle.min.js': [
              'src/js/vendor/chemdoodle/chemdoodle-unpacked.js',
              'src/js/vendor/chemdoodle/chemdoodle-uis-unpacked.js'],

          'web/app/js/chemdoodle-canvas.min.js': 'web/app/js/src/chemdoodle-canvas.js',

          'web/app/js/bootstrap-markdown.min.js': [
              'node_modules/marked/lib/marked.js',
              'node_modules/bootstrap-markdown-fa5/js/bootstrap-markdown.js',
              'node_modules/bootstrap-markdown-fa5/locale/*' ],
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
          'web/app/css/pdf.min.css': 'web/app/css/pdf.css',
          'web/app/css/vendor.min.css': [
            'node_modules/prismjs/themes/prism.css',
            'node_modules/dropzone/dist/dropzone.css',
            'node_modules/@fullcalendar/core/main.css',
            'node_modules/@fullcalendar/bootstrap/main.css',
            'node_modules/@fullcalendar/list/main.css',
            'node_modules/@fullcalendar/timegrid/main.css',
            'node_modules/@fancyapps/fancybox/dist/jquery.fancybox.css',
            'node_modules/jquery-ui/themes/base/all.css',
          ],

          'web/app/css/bootstrap-markdown.min.css': 'node_modules/bootstrap-markdown/css/bootstrap-markdown.min.css',
          'web/app/css/tinymce/skin.min.css': 'node_modules/tinymce/skins/ui/oxide/skin.css',
          'web/app/css/tinymce/content.min.css': 'node_modules/tinymce/skins/ui/oxide/content.css',
          'web/app/css/tinymce/content.mobile.min.css': 'node_modules/tinymce/skins/ui/oxide/content.mobile.css',
          'web/app/css/tinymce/skin.mobile.min.css': 'node_modules/tinymce/skins/ui/oxide/skin.mobile.css',
          'web/app/css/jsoneditor/jsoneditor.min.css': 'node_modules/jsoneditor/dist/jsoneditor.css'
        }
      }
    },
    shell: {
      tsc: {
        command: 'tsc --build src/ts/tsconfig.json'
      },
      // run yarn install
      yarninstall: {
        command: 'yarn install --pure-lockfile'
      },
      tinymce: {
        // copy the mobile font file
        command: 'cp node_modules/tinymce/skins/ui/oxide/fonts/tinymce-mobile.woff web/app/css/tinymce/fonts/tinymce-mobile.woff'
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-uglify-es');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-cssmin');
  grunt.loadNpmTasks('grunt-shell');

  // before minifying js it is preferable to do 'yarn install' to update the dependencies
  grunt.registerTask('yarn', 'shell:yarninstall');
  grunt.registerTask('default', ['yarn', 'shell:tsc', 'uglify', 'cssmin', 'tinymce']);
  grunt.registerTask('css', 'cssmin');
  grunt.registerTask('tinymce', 'shell:tinymce');
  grunt.registerTask('ts', ['shell:tsc']);
};
