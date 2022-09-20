module.exports = {
	extends: [
		'@nextcloud',
	],
	rules: {
		'n/no-missing-import': ['error', {
			allowModules: ['@nextcloud/vue'],
		}
		],
	}
}
