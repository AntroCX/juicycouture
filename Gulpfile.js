/**
 * Created by mbakirov on 15.05.15.
 */
//load plugins
var gulp             = require('gulp'),
    compass          = require('gulp-compass'),
    uglify           = require('gulp-uglify'),
    livereload       = require('gulp-livereload'),
    rename           = require('gulp-rename'),
    imagemin         = require('gulp-imagemin'),
    pngquant         = require('imagemin-pngquant'),
    path             = require('path'),
    base64           = require('gulp-base64');

//styles
gulp.task('styles', function() {
    return gulp.src(['/blocks/**/*.scss', '/blocks/**/**/*.scss', '/blocks/**/*.sass', '/blocks/**/**/*.sass'])
        .pipe(compass({
            sass: 'local/blocks',
            css: 'local/blocks',
            javascript: 'local/blocks',
            image: 'local/img',
            font: 'local/fonts',
            style: 'compressed', //nested - если не минифицировать, compressed
            sourcemap: false,
            relative: false,
            comments: true,
            environment: 'development'
        }))
        /*.pipe(base64({
            baseDir: '',
            extensions: ['svg', 'png', /\.jpg#datauri$/i],
            exclude:    [/\.server\.(com|net)\/dynamic\//, '--live.jpg'],
            maxImageSize: 8*1024, // bytes
            debug: true
        }))*/
});

gulp.task('styles_production', ['images'], function() {
    return gulp.src(['/blocks/**/*.scss', '/blocks/**/**/*.scss', '/blocks/**/*.sass', '/blocks/**/**/*.sass'])
        .pipe(compass({
            sass: 'local/blocks',
            css: 'local/blocks',
            javascript: 'local/blocks',
            image: 'local/img/production',
            font: 'local/fonts',
            style: 'compressed',
            relative: false,
            force: true,
            environment: 'production'
        }))
});


//scripts
gulp.task('scripts', function() {
    return gulp.src(['local/blocks/**/*.js', '!local/blocks/**/*.deps.js', '!local/blocks/**/*.min.js'])
        .pipe(uglify())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest('local/blocks'))
});
gulp.task('scripts_production', function() {
    return gulp.src(['local/blocks/**/*.js', '!local/blocks/**/*.deps.js', '!local/blocks/**/*.min.js'])
        .pipe(uglify())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest('local/blocks'))
});

//images
gulp.task('images', function () {
    return gulp.src(['local/img/*', '!local/img/production*'])
        .pipe(imagemin({
            progressive: true,
            svgoPlugins: [{removeViewBox: false}],
            use: [pngquant()]
        }))
        .pipe(gulp.dest('local/img/production'));
});

//watch
gulp.task('live_dev', function() {
    livereload.listen();

    //watch .scss files
    gulp.watch(['local/blocks/**/*.scss','local/blocks/**/**/*.scss', 'local/blocks/**/*.sass','local/blocks/**/**/*.sass'], ['styles']);

    //watch .js files
    gulp.watch(['local/blocks/**/*.js', '!local/blocks/**/*.deps.js', '!local/blocks/**/*.min.js'], ['scripts_production']);
});


gulp.task('production', ['images', 'styles_production', 'scripts_production']);