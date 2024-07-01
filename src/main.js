/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import { translate, translatePlural } from '@nextcloud/l10n'

// eslint-disable-next-line
__webpack_nonce__ = btoa(OC.requestToken)
// eslint-disable-next-line
__webpack_public_path__ = OC.linkTo('related_resources', 'js/')

Vue.prototype.t = translate
Vue.prototype.n = translatePlural

window.addEventListener('DOMContentLoaded', () => {
	if (!OCA?.Sharing?.ShareTabSections) {
		return
	}

	import('@nextcloud/vue/dist/Components/NcRelatedResourcesPanel.js').then((Module) => {
		OCA.Sharing.ShareTabSections.registerSection((el, fileInfo) => {
			return {
				render(h) {
					return h(Module.default, {
						props: {
							providerId: 'files',
							itemId: fileInfo.id,
						},
					})
				},
			}
		})
	})
})
