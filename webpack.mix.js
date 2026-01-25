const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
    .vue()
    .sass('resources/sass/app.scss', 'public/css')
    .browserSync({
        proxy: 'localhost',
        host: 'localhost',
        open: false,
        files: [
            'resources/js/**/*.vue',
            'resources/js/**/*.js',
            'resources/sass/**/*.scss'
        ],
        watchOptions: {
            usePolling: true,
            interval: 500
        }
    })
    .version();
    