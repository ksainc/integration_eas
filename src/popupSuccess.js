// import { loadState } from '@nextcloud/initial-state'

// const state = loadState('integration_eas', 'popup-data')

if (window.opener) {
	window.opener.postMessage('Success')
	window.close()
}
