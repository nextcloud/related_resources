const webpackConfig = require('@nextcloud/webpack-vue-config')
const path = require('path')

webpackConfig.entry = {
	...webpackConfig.entry,
	main: path.join(__dirname, 'src', 'main.js'),
}

webpackConfig.stats = {
	context: path.resolve(__dirname, 'src'),
	assets: true,
	entrypoints: true,
	chunks: true,
	modules: true,
}

webpackConfig.output = {
	path: path.resolve('./js'),
	filename: 'related_resources.js',
}

module.exports = webpackConfig
