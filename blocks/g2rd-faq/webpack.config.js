const defaultConfig = require("@wordpress/scripts/config/webpack.config");

module.exports = {
  ...defaultConfig,
  entry: {
    index: "./src/index.js",
    view:  "./src/view.js",
    style: "./src/style.css",
  },
	devtool: process.env.NODE_ENV === 'production' ? false : 'source-map',
};
