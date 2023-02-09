const fs                   = require( "node:fs" );
const path                 = require( "path" );
const glob                 = require( 'glob' );
const MiniCssExtractPlugin = require( "mini-css-extract-plugin" );
const CssMinimizerPlugin   = require( "css-minimizer-webpack-plugin" );
const base_path    = path.resolve(__dirname, 'src/ts');
const entry        = {};
glob.sync(`${base_path}/**/*.+(ts|js)`).forEach(value => {
	if ( ! value.split("/").pop().match(/^_/) ) {
		entry[ path.basename( value, path.extname(value) ) ] = value;
	}
});
// ディレクトリごと初期化
const rmDir = (dirPath) => {
	if ( !fs.existsSync(dirPath) ) { return }
	const items = fs.readdirSync(dirPath);
	for ( const item of items ) {
		const deleteTarget = path.join( dirPath, item );
		if ( fs.lstatSync(deleteTarget).isDirectory() ) {
			rmDir(deleteTarget);
		} else {
			fs.unlinkSync(deleteTarget);
		}
	}
	fs.rmdirSync( dirPath );
}
rmDir( path.resolve(__dirname, 'dist/js') );
console.log('distを削除');
module.exports = {
	mode: "development",
	entry,
	output: {
		path: path.resolve(__dirname, 'dist/js'),
		filename: '[name].js',
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
		extensions: [".ts", ".js", ".scss", ".sass", "css"],
	},
	plugins: [
		new MiniCssExtractPlugin(
			{
				filename: '../css/[name].css',
			}
		),
		new CssMinimizerPlugin(),
	],
};
