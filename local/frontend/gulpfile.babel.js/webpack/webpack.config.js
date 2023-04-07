const VueLoaderPlugin = require('vue-loader/lib/plugin');

export const webpackConfig = {
  mode: process.env.NODE_ENV || 'development',

  module: {
    rules: [
      {
        test:   /\.vue$/,
        loader: 'vue-loader'
      },
      {
        test:   /\.pug$/,
        loader: 'pug-plain-loader'
      },
    ]
  },

  plugins: [
    new VueLoaderPlugin()
  ],

  entry:  {
    // // Дебаг фичи
    debug: './src/scripts/debug.js',
    
    // Основной бандл (футер и т.п.)
    main: './src/scripts/main.js',
  },
  output: {
    filename: '[name].bundle.js'
  }
};