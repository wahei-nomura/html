const path = require("path");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");

const admin = {
	mode: "development",
	entry: "./src/ts/admin.ts",
	output: {
		path: `${__dirname}/dist/`,
		filename: "admin.js",
	},
	module: {
		rules: [
			{
				test: /\.ts$/,
				use: "ts-loader",
			},
			{
				test: /\.(sa|sc|c)ss$/,
				use: [
					MiniCssExtractPlugin.loader,
					{
						loader: "css-loader",
						options: {
							url: false,
						},
					},
					{
						loader: "sass-loader",
					},
				],
			},
		],
	},
	resolve: {
		extensions: [".ts", ".js"],
	},
	plugins: [
		new MiniCssExtractPlugin({
			filename: "admin.css",
		}),
		new CssMinimizerPlugin(),
	],
};

const front = {
	mode: "development",
	entry: "./src/ts/front.ts",
	output: {
		path: `${__dirname}/dist/`,
		filename: "front.js",
	},
	module: {
		rules: [
			{
				test: /\.ts$/,
				use: "ts-loader",
			},
			{
				test: /\.(sa|sc|c)ss$/,
				use: [
					MiniCssExtractPlugin.loader,
					{
						loader: "css-loader",
						options: {
							url: false,
						},
					},
					{
						loader: "sass-loader",
					},
				],
			},
		],
	},
	resolve: {
		extensions: [".ts", ".js"],
	},
	plugins: [
		new MiniCssExtractPlugin({
			filename: "front.css",
		}),
		new CssMinimizerPlugin(),
	],
};

module.exports = [admin, front];
