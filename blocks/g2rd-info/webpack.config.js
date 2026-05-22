const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const path = require("path");

module.exports = {
  ...defaultConfig,
  entry: {
    index: path.resolve(__dirname, "src/index.js"),
    "info-frontend": path.resolve(__dirname, "src/info-frontend.js"),
  },
	devtool: process.env.NODE_ENV === 'production' ? false : 'source-map',
};
