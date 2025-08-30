const mix = require("laravel-mix");

require("laravel-mix-tailwind");

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

mix.js("resources/js/app.js", "public/js/app.js")
    .sass("resources/sass/app.scss", "public/css/app.css")
    .tailwind("./tailwind.config.js")
    .copy("node_modules/tinymce/skins", "public/js/skins")
    .copy("node_modules/tinymce/themes", "public/js/themes")
    .copy("node_modules/tinymce/plugins", "public/js/plugins")
    .copy("node_modules/tinymce/models", "public/js/models")
    .copy("node_modules/tinymce/icons", "public/js/icons")
    .copy("node_modules/tinymce/tinymce.min.js", "public/js/tinymce.min.js")
    .sourceMaps();

if (mix.inProduction()) {
    mix.version();
}
