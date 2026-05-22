const defaultConfig = require("@wordpress/scripts/config/webpack.config");

module.exports = {
  ...defaultConfig,
  entry: {
    index: "./src/index.js",
  },
	devtool: process.env.NODE_ENV === 'production' ? false : 'source-map',
};
