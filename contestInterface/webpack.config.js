const path = require('path');
const UglifyJsPlugin = require('uglifyjs-webpack-plugin');
require('babel-register');
const webpack = require('webpack');

const isDev = process.env.NODE_ENV !== 'production';
const isDebug = process.env.DEBUG === '1';

// Webpack Configuration
module.exports = {
	// Entry
	entry: ['./app/app.js'],

	// Output
	output: {
		path: path.resolve(__dirname, './build'),
		filename: 'app.js',
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
		new webpack.DefinePlugin({
			__DEBUG__: JSON.stringify(isDebug)
		})
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

	mode: isDev ? 'development' : 'production'
};