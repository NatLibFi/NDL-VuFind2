var gulp = require('gulp');
var less = require('gulp-less');
var cleanCSS = require('gulp-clean-css');
 
gulp.task('less', function () {
  return gulp.src('./themes/finna2/less/finna.less')
    .pipe(less())
    .pipe(cleanCSS({keepSpecialComments: 0, advanced: true }))
    .pipe(gulp.dest('./themes/finna2/css/'));
});

gulp.task('watch', function() {
    gulp.watch('./themes/finna2/less/finna/**/*.less', [less]);
});
