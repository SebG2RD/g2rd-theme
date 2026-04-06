const defaultConfig = require("@wordpress/scripts/config/webpack.config");

module.exports = {
  ...defaultConfig,
  entry: {
    index: "./index.js",
    "countdown-frontend": "./countdown-frontend.js",
  },
};
