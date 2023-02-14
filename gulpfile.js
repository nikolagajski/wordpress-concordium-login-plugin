const zip = require("gulp-zip")
const gulp = require('gulp')
const composer = require("gulp-composer")

async function cleanTask() {
    const del = await import("del")
    return del.deleteAsync('./dist/plugin/**', {force:true});
}

function movePluginFolderTask() {
    return gulp.src([
        './wp-content/plugins/concordium-login/**',
        '!./wp-content/plugins/concordium-login/assets/src/**'
    ]).pipe(gulp.dest('./dist/plugin'))
}

function compressTask() {
    return gulp.src('./dist/plugin/**')
        .pipe(zip('plg_concordium_login.zip'))
        .pipe(gulp.dest('./dist'));
}

function composerTask() {
    return composer({
        "working-dir": "./dist/plugin"
    })
}

async function cleanComposerTask() {
    const del = await import("del")
    return del.deleteAsync('./dist/plugin/composer.*', {force:true});
}

exports.zip = gulp.series(
    cleanTask,
    movePluginFolderTask,
    composerTask,
    cleanComposerTask,
    compressTask,
    cleanTask
);
