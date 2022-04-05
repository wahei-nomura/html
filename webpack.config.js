const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
	mode: 'development',
	entry: './src/ts/index.ts',
	output: {
		path: `${__dirname}/dist/`,
		filename: 'index.js'
	},
	module: {
		rules: [
			{
				test: /\.ts$/,
				use: 'ts-loader',
			},
			{
				test: /\.(sa|sc|c)ss$/,
				use: [
					MiniCssExtractPlugin.loader,
					{
						loader: 'css-loader',
						options: {
							url: false,
						}
					},
					{
						loader: 'sass-loader'
					}
				]
			}
		]
	},
	resolve: {
		extensions: [
			'.ts', '.js',
		]
	},
	plugins: [
		new MiniCssExtractPlugin({
			filename: 'style.css',
		})
	]
};