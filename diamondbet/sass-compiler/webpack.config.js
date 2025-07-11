const path = require('path');
const fs = require('fs');

const HookShellScriptPlugin = require('hook-shell-script-webpack-plugin');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
const StylelintPlugin = require('stylelint-webpack-plugin');

/**
 *
 * @param dir: path of the directory you want to search the files for
 * @param fileTypes: array of file types you are search files, example: ['.scss']
 * @param subdirectories: subdirectories which should be included in the returned files list, example: ['mrvegas']
 *
 * @returns a list of files of the specified fileTypes in the provided dir and specified subdirectories
 *
 */

/**
 * Names of selected SCSS partials which should not be compiled to CSS
 * must be listed below as excluded files
 */
const excludedFiles = ['_font-faces.scss', '_game-play-mobile.scss']

const getFilesFromDir = function (dir, fileTypes, subdirectories) {
	fileTypes = (fileTypes == null) ? ['.scss'] : fileTypes;
	subdirectories = (subdirectories == null) ? [] : subdirectories;

	let filesToReturn = [];

	function walkDir(currentPath) {
		const files = fs.readdirSync(currentPath);

		files.forEach(function (fileName) {
			const curFileFullPath = path.join(currentPath, fileName);

			if (fs.statSync(curFileFullPath).isFile() && fileTypes.indexOf(path.extname(curFileFullPath)) != -1 && excludedFiles.indexOf(fileName) < 0) {
				filesToReturn.push(curFileFullPath);
			} else if (fs.statSync(curFileFullPath).isDirectory() && subdirectories.indexOf(path.basename(curFileFullPath)) != -1) {
				walkDir(curFileFullPath);
			}
		});
	};

	walkDir(dir);

	return filesToReturn;
};

const isProduction = (process.env.NODE_ENV === 'production');

const useHookShellScriptPlugin = true;
const useStylelint = false;

// is compiler running from a subdirectory or on the root directory?
const inSubdirectory = true;

const SCSS_SOURCE_PATH = inSubdirectory ? '../scss/' : './scss/';

// We don't resolve SCSS_INCLUDED_FILE_PATH as it is used for the SCSS @import
// Resolving leads to issue under Windows (backslashes versus slashes)
const SCSS_INCLUDED_FILE_PATH = SCSS_SOURCE_PATH + 'included/_included.scss';

const CSS_DEV_OUTPUT_PATH = inSubdirectory ? '../css' : './css';
const CSS_DEV_OUTPUT_PATH_FULL = path.resolve(__dirname, CSS_DEV_OUTPUT_PATH);

const CSS_DIST_OUTPUT_PATH = inSubdirectory ? '../dist/css' : './dist/css';
const CSS_DIST_OUTPUT_PATH_FULL = path.resolve(__dirname, CSS_DIST_OUTPUT_PATH);

// https://webpack.js.org/loaders/sass-loader/#additionaldata
const getSassAdditionalData =  (content, loaderContext) => {
	const { resourcePath} = loaderContext;
	const parentDirectory = path.basename(path.dirname(resourcePath));
	const scssRootBaseName = path.basename(path.resolve(__dirname, SCSS_SOURCE_PATH));
	const includedFileSubfolder = (parentDirectory === scssRootBaseName) ? '' : parentDirectory + '/';
	const scssCommonIncludedFilePath = `${SCSS_SOURCE_PATH}included/common/_included.scss`;
	const scssFolderIncludedFilePath = `${SCSS_SOURCE_PATH}included/${includedFileSubfolder}_included.scss`;
	const actualFileName = path.basename(resourcePath);
	const scssIncludedVariablesFilePath = `${SCSS_SOURCE_PATH}included/${includedFileSubfolder}variables/_${actualFileName}`;

	// imports included file for all processed files
	const importScssCommonIncludedFile = fs.existsSync(scssCommonIncludedFilePath) ? `@import '${scssCommonIncludedFilePath}';` : '';

	// imports included file specific for given folder
	// Example: in every file from scss/ folder imports scss/included/_included.scss
	const importScssFolderIncludedFile = fs.existsSync(scssFolderIncludedFilePath) ? `@import '${scssFolderIncludedFilePath}';` : '';

	// imports variable file belonging to given file
	// Example: in all.scss imports included/variables/_all.scss
	// This allows a kind of namespacing for variables
	const importScssIncludedVariablesFile = fs.existsSync(scssIncludedVariablesFilePath) ? `@import '${scssIncludedVariablesFilePath}';` : '';

	return importScssCommonIncludedFile + importScssFolderIncludedFile + importScssIncludedVariablesFile + content;
}

const brandFolders = ['mrvegas', 'kungaslottet', 'megariches', 'dbet'];
const filesForCompilation = getFilesFromDir(SCSS_SOURCE_PATH, ['.scss'], brandFolders);

module.exports = (env, argv) => {
	return {
		watchOptions: {
			ignored: /node_modules/,
		},
		resolve: {
			preferRelative: true
		},
		mode: isProduction ? 'production' : 'development',
		entry: filesForCompilation,
		output: {
			path: isProduction ? CSS_DIST_OUTPUT_PATH_FULL : CSS_DEV_OUTPUT_PATH_FULL
		},
		// partially inspired by
		// https://florianbrinkmann.com/en/sass-webpack-4240/
		// https://github.com/webpack-contrib/extract-text-webpack-plugin/issues/159#issuecomment-292568527
		module: {
			rules: [
				{
					test: /\.scss$/,
					// not working, needs to be resolved
					// https://stackoverflow.com/questions/68590117/how-to-ignore-ds-store-file-in-webpack-scss-compilation
					// TBD: @richardkrejci
					exclude: [
						/.*\.DS_Store/
					],
					use: [
						{
							// emits the extract-loader's result as separate files
							loader: 'file-loader',
							options: {
								//name: '[name].css',
								name: (file) => {
									const parentDirectory = path.basename(path.dirname(file));

									if (parentDirectory !== 'scss') {
										return `${parentDirectory}/[name].css`
									}

									return '[name].css';
								}
							}
						},
						{
							// evaluates the given source code on the fly and returns the result as string
							loader: 'extract-loader'
						},
						{
							loader: 'css-loader',
							options: {
								// needs to be `false`, otherwise it tries resolve resources from `url()/image-set()`
								url: false
							}
						},
						{
							loader: 'postcss-loader',
							options: {
								postcssOptions: {
									config: path.resolve(__dirname, "postcss.config.js"),
								}
							},
						},
						{
							loader: 'sass-loader',
							options: {
								sassOptions: {
									outputStyle: isProduction ? `compressed` : 'expanded',
								},
								// this data can be used in every SCSS file
								additionalData: getSassAdditionalData
							}
						}
					]
				}
			]
		},

		plugins: [
			useHookShellScriptPlugin && new HookShellScriptPlugin({
				afterCompile: ['npm run check-vars'],
			}),

			// removes reduntant JS files from the output
			// https://github.com/webdiscus/webpack-remove-empty-scripts
			new RemoveEmptyScriptsPlugin({ verbose: true }),

			// https://gist.github.com/mattwatsoncodes/5a44b9e12b3e8bd05416512e05655974
			useStylelint && new StylelintPlugin({
				configFile: '.stylelintrc.json',
				context: path.resolve(__dirname, CSS_DEV_OUTPUT_PATH),
				//files: '*.css',
				failOnError: false,
				quiet: false,
				emitErrors: true // by default this is to true to check the CSS lint errors
			})
		].filter(Boolean)
	}
};
