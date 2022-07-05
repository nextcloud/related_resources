<!--
  - @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
  <div class="related-entry__resources">
    <!-- Main collapsible entry -->
    <RelatedResourceItem :title="mainTitle" :subtitle="subTitle">
      <template #avatar>
        <div class="avatar-subfolder avatar-subfolder--primary icon-folder-shared-white" />
      </template>
    </RelatedResourceItem>

    <RelatedResourceItem v-for="resource in resources"
                         :key="resource.itemId"
                         class="related-entry__resource"
                         :title="resource.title"
                         :subtitle="resource.subtitle"
						 :tooltip="resource.tooltip">
      <ActionLink icon="icon-confirm" :href="resource.link" target="_blank">
        {{ resource.link }}
      </ActionLink>
    </RelatedResourceItem>
  </div>
</template>

<script>
import axios from '@nextcloud/axios'
import {generateOcsUrl, generateUrl} from '@nextcloud/router'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionLink from '@nextcloud/vue/dist/Components/ActionLink'
import RelatedResourceItem from '../components/RelatedResourceItem'

export default {
  name: 'RelatedResources',

  components: {
    ActionButton,
    ActionLink,
    RelatedResourceItem,
  },

  props: {
    fileInfo: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      loaded: false,
      loading: false,
      error: false,
      resources: [],
    }
  },

  computed: {
    mainTitle() {
      return t('related_resources', 'Related resources')
    },
    subTitle() {
      if (this.loading) {
        return t('related_resources', 'Loading')
      }
      return (this.resources.length === 0)
          ? t('related_resources', 'No related resources found')
          : ''
    },
    fullPath() {
      const path = `${this.fileInfo.path}/${this.fileInfo.name}`
      return path.replace('//', '/')
    },
  },

  watch: {
    fileInfo() {
      this.fetchRelatedResources()
    },
  },

  beforeMount() {
    this.fetchRelatedResources()
  },

  methods: {
    async fetchRelatedResources() {
      this.loading = true
      this.loaded = false
	  this.resources = []
      try {
        const url = generateOcsUrl(`apps/related_resources/resources/files/${this.fileInfo.id}?format=json`, 2)
        const resources = await axios.get(url.replace(/\/$/, ''))
        this.resources = resources.data.ocs.data

        console.log('--- ' + JSON.stringify(this.resources))

        this.loaded = true
      } catch (error) {
		console.error(error)
        OC.Notification.showTemporary(t('related_resources', 'Unable to fetch the related resources'),
            {type: 'error'})
        this.error = true
      } finally {
        this.error = false
        this.loading = false
      }
    },

    /**
     * Generate a file app url to a provided path
     *
     * @param {string} dir the absolute url to the folder
     * @param {number} fileid the node id
     * @return {string}
     */
    generateFileUrl(dir, fileid) {
      return generateUrl('/apps/files?dir={dir}&fileid={fileid}', {
        dir,
        fileid
      })
    },
  },
}
</script>

<style lang="scss" scoped>
.related-entry__resources {
  .avatar-subfolder {
    width: 32px;
    height: 32px;
    line-height: 32px;
    font-size: 18px;
    border-radius: 50%;
    flex-shrink: 0;

    &--primary {
      background-color: var(--color-primary-element);
    }
  }

  .related-entry__resource {
    padding-left: 36px;
  }
}
</style>
