const defaultConfig = require("@wordpress/scripts/config/webpack.config");

module.exports = {
  ...defaultConfig,
  entry: {
    index: "./src/index.js",
    view:  "./src/view.js",
  },
	devtool: process.env.NODE_ENV === 'production' ? false : 'source-map',
};
