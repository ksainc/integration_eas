<!--
*
* @copyright Copyright (c) 2023 Sebastian Krupinski <krupinski01@gmail.com>
*
* @author Sebastian Krupinski <krupinski01@gmail.com>
*
* @license AGPL-3.0-or-later
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as
* published by the Free Software Foundation, either version 3 of the
* License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
-->

<template>
	<div id="eas_settings" class="section">
		<div class="eas-section-heading">
			<EwsIcon :size="32" /><h2> {{ t('integration_eas', 'EWS Connector') }}</h2>
		</div>
		<p class="settings-hint">
			{{ t('integration_eas', 'Select the system settings for Exchange Integration') }}
		</p>
		<div class="fields">
			<div>
				<div class="line">
					<label>
						{{ t('integration_eas', 'Synchronization Mode') }}
					</label>
					<NcSelect v-model="state.harmonization_mode"
						:reduce="item => item.id"
						:options="[{label: 'Passive', id: 'P'}, {label: 'Active', id: 'A'}]" />
				</div>
				<div v-if="state.harmonization_mode === 'A'" class="line">
					<label>
						{{ t('integration_eas', 'Synchronization Thread Duration') }}
					</label>
					<input id="eas-thread-duration"
						v-model="state.harmonization_thread_duration"
						type="text"
						:autocomplete="'off'"
						:autocorrect="'off'"
						:autocapitalize="'none'">
					<label>
						{{ t('integration_eas', 'Seconds') }}
					</label>
				</div>
				<div v-if="state.harmonization_mode === 'A'" class="line">
					<label>
						{{ t('integration_eas', 'Synchronization Thread Pause') }}
					</label>
					<input id="eas-thread-pause"
						v-model="state.harmonization_thread_pause"
						type="text"
						:autocomplete="off"
						:autocorrect="off"
						:autocapitalize="none">
					<label>
						{{ t('integration_eas', 'Seconds') }}
					</label>
				</div>
			</div>
			<br>
			<div>
				<p class="settings-hint">
					{{ t('integration_eas', 'Microsoft 365 Authentication Settings') }}
				</p>
				<div class="line">
					<label for="eas-microsoft-tenant-id">
						<EwsIcon />
						{{ t('integration_eas', 'Tenant ID') }}
					</label>
					<input id="eas-microsoft-tenant-id"
						v-model="state.ms365_tenant_id"
						type="text"
						:placeholder="t('integration_eas', '')"
						autocomplete="off"
						autocorrect="off"
						autocapitalize="none">
				</div>
				<div class="line">
					<label for="eas-microsoft-application-id">
						<EwsIcon />
						{{ t('integration_eas', 'Application ID') }}
					</label>
					<input id="eas-microsoft-application-id"
						v-model="state.ms365_application_id"
						type="text"
						:placeholder="t('integration_eas', '')"
						autocomplete="off"
						autocorrect="off"
						autocapitalize="none">
				</div>
				<div class="line">
					<label for="eas-microsoft-application-secret">
						<EwsIcon />
						{{ t('integration_eas', 'Application Secret') }}
					</label>
					<input id="eas-microsoft-application-secret"
						v-model="state.ms365_application_secret"
						type="password"
						:placeholder="t('integration_eas', '')"
						autocomplete="off"
						autocorrect="off"
						autocapitalize="none">
				</div>
			</div>
			<br>
			<div class="eas-actions">
				<NcButton @click="onSaveClick()">
					<template #icon>
						<CheckIcon />
					</template>
					{{ t('integration_eas', 'Save') }}
				</NcButton>
			</div>
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import { showSuccess, showError } from '@nextcloud/dialogs'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'

import EwsIcon from './icons/EwsIcon.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'

export default {
	name: 'AdminSettings',

	components: {
		NcButton,
		NcSelect,
		EwsIcon,
		CheckIcon,
	},

	props: [],

	data() {
		return {
			readonly: true,
			state: loadState('integration_eas', 'admin-configuration'),
		}
	},

	computed: {
	},

	methods: {
		onSaveClick() {
			const req = {
				values: {
					harmonization_mode: this.state.harmonization_mode,
					harmonization_thread_duration: this.state.harmonization_thread_duration,
					harmonization_thread_pause: this.state.harmonization_thread_pause,
					ms365_tenant_id: this.state.ms365_tenant_id,
					ms365_application_id: this.state.ms365_application_id,
					ms365_application_secret: this.state.ms365_application_secret,
				},
			}
			const url = generateUrl('/apps/integration_eas/admin-configuration')
			axios.put(url, req)
				.then((response) => {
					showSuccess(t('integration_eas', 'EWS admin configuration saved'))
				})
				.catch((error) => {
					showError(
						t('integration_eas', 'Failed to save EWS admin configuration')
						+ ': ' + error.response.request.responseText
					)
				})
				.then(() => {
				})
		},
	},
}
</script>

<style scoped lang="scss">
#eas_settings {
	.eas-section-heading {
		display:inline-block;
		vertical-align:middle;
	}

	.eas-connected {
		display: flex;
		align-items: center;

		label {
			padding-left: 1em;
			padding-right: 1em;
		}
	}

	.eas-collectionlist-item {
		display: flex;
		align-items: center;

		label {
			padding-left: 1em;
			padding-right: 1em;
		}
	}

	.eas-actions {
		display: flex;
		align-items: center;
	}

	.external-label {
		display: flex;
		//width: 100%;
		margin-top: 1rem;
	}

	.external-label label {
		padding-top: 7px;
		padding-right: 14px;
		white-space: nowrap;
	}
}
</style>
