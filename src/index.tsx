import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import { SettingsPage } from './admin-components';
import './index.scss';

domReady( () => {
	const settingsElement = document.getElementById( 'farcaster-wp-settings' );

	if ( ! settingsElement ) {
		return;
	}

	const root = createRoot( settingsElement );

	root.render( <SettingsPage /> );
} );
