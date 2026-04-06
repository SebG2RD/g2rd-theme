const defaultConfig = require( "@wordpress/scripts/config/webpack.config" );
const path = require( "path" );

module.exports = {
	...defaultConfig,
	entry: {
		index:               path.resolve( __dirname, "src/index.js" ),
		"countdown-frontend": path.resolve( __dirname, "src/countdown-frontend.js" ),
	},
};
