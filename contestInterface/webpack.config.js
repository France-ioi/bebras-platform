// Imports
const path = require('path');
require("@babel/register");

// Webpack Configuration
const config = {
	// Entry
	entry: ['./components/index.js'],

	// Output
	output: {
		path: path.resolve(__dirname, './build'),
		filename: 'bundle.js',
		libraryTarget: "var",
		library: "ui"
	},
	externals: {
		"jquery": "$"
	},
	// Loaders
	module: {
		rules: [
			// JavaScript/JSX Files
			{
				test: /\.jsx$/,
				exclude: /node_modules/,
				use: ['babel-loader']
			}
		]
	},
	// Plugins
	plugins: [
	],
	// OPTIONAL
	// Reload On File Change
	watch: true,
	// Development Tools (Map Errors To Source File)
	devtool: 'source-map',
};
// Exports
module.exports = config;