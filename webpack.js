/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const webpackConfig = require('@nextcloud/webpack-vue-config')
const TerserPlugin = require('terser-webpack-plugin')
const WebpackSPDXPlugin = require('./build-js/WebpackSPDXPlugin.js')
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

webpackConfig.optimization.minimizer = [new TerserPlugin({
	extractComments: false,
	terserOptions: {
		format: {
			comments: false,
		},
	},
})]

webpackConfig.plugins = [
	...webpackConfig.plugins,
	// Generate reuse license files
	new WebpackSPDXPlugin({
		override: {
			// TODO: Remove if they fixed the license in the package.json
			'@nextcloud/axios': 'GPL-3.0-or-later',
			'@nextcloud/vue': 'AGPL-3.0-or-later',
			'nextcloud-vue-collections': 'AGPL-3.0-or-later',
		}
	}),
]

module.exports = webpackConfig
