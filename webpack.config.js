const fs = require("node:fs");
const path = require("path");
const glob = require("glob");
const RemoveEmptyScriptsPlugin = require("webpack-remove-empty-scripts"); // scssのコンパイルのみの際のゴミjs削除
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");

// dist ディレクトリごと初期化
const rmDir = (dirPath) => {
	if (!fs.existsSync(dirPath)) {
		return;
	}
	const items = fs.readdirSync(dirPath);
	for (const item of items) {
		const deleteTarget = path.join(dirPath, item);
		if (fs.lstatSync(deleteTarget).isDirectory()) {
			rmDir(deleteTarget);
		} else {
			fs.unlinkSync(deleteTarget);
		}
	}
	fs.rmdirSync(dirPath);
};
rmDir(path.resolve(__dirname, "dist"));
console.log("distを削除");
// entry作成
const base_path = path.join(__dirname, "src");
/**
 *
 * @param {string} src 対象ディレクトリ
 * @param {string} reg 拡張子正規表現
 * @returns
 */
const entry_arr = (src, reg) => {
	const entry = {};
	glob.sync(`${path.join(__dirname, src)}/*.+(${reg})`).forEach((value) => {
		entry[path.basename(value, path.extname(value))] = value;
	});
	return entry;
};

// jsのコンパイル
const js = {
	mode: "development",
	entry: entry_arr("src/ts", "ts|js"),
	output: {
		path: path.resolve(__dirname, "dist/js"),
		filename: "[name].js",
	},
	module: {
		rules: [
			{
				test: /\.ts$/,
				use: "ts-loader",
			},
		],
	},
	resolve: {
		extensions: [".ts", ".js"],
		alias: {
			// import Vue from 'vue'; と記述したときの 'vue' が表すファイルパスを設定
			vue$: "vue/dist/vue.esm.js",
		},
	},
	// devtool: 'source-map',
};

// css
const css = {
	mode: "development",
	entry: entry_arr("src/scss", "scss|sass|css"),
	output: {
		path: path.resolve(__dirname, "dist/css"),
	},
	module: {
		rules: [
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
		extensions: [".scss", ".sass", ".css"],
	},
	plugins: [
		new MiniCssExtractPlugin({
			filename: "[name].css",
		}),
		new CssMinimizerPlugin(),
		new RemoveEmptyScriptsPlugin(),
	],
	// devtool: 'source-map',
};

module.exports = [js, css];
