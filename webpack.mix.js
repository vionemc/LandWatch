const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.setPublicPath('public/');

mix.js('resources/js/app.js', 'js')
    .sass('resources/scss/app.scss', 'css', [
    ], [
        require('autoprefixer'),
    ]);

if (mix.inProduction()) {
    mix.disableNotifications();
    mix.version();
} else {
    mix.browserSync(process.env.APP_URL)
}
