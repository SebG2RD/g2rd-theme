import { createRoot, render } from '@wordpress/element';
import { App } from './App';
import './style.css';

const root = document.getElementById( 'g2rd-options-root' );
if ( root ) {
	if ( typeof createRoot === 'function' ) {
		createRoot( root ).render( <App /> );
	} else {
		render( <App />, root );
	}
}
