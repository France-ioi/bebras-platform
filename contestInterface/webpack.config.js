// Imports
const path = require('path');
const UglifyJsPlugin = require('uglifyjs-webpack-plugin');
require("babel-register");

const isDev = process.env.NODE_ENV !== 'production';
// Webpack Configuration
const config = {
	// Entry
	entry: ['./components/index.js'],

	// Output
	output: {
		path: path.resolve(__dirname, './build'),
		filename: 'bundle.js',
		libraryTarget: "var",
		library: "UI"
	},
	externals: {
		"jquery": "$"
	},
	// Loaders
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: /(node_modules|bower_components)/,
				use: {
					loader: "babel-loader"
				  }
			}
		]
	},
	// Plugins
	plugins: [
	],
	optimization: {
		minimize: !isDev,
		minimizer: [new UglifyJsPlugin()],
	},

	// OPTIONAL
	// Reload On File Change
	watch: isDev,
	// Development Tools (Map Errors To Source File)
	devtool: isDev ? 'source-map': false,
};
// Exports
module.exports = config;