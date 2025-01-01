const path = require('path');
const glob = require('glob');

const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const MiniCssExtractPlugin   = require('mini-css-extract-plugin');
const TerserPlugin           = require('terser-webpack-plugin');

// separate and compile every .scss & .js file from root 'src' folder
const parseEntries = (type, outputFolder, postfix = '') => {
  return glob.sync(`./src/${type}`).reduce((obj, el) => {
    const name = path.parse(el).name;

    obj[`${outputFolder}/${name}${postfix}`] = el;
    return obj;
  }, {});
}

module.exports = {
  entry: {
    ...parseEntries('js/**.js', 'js'),
    ...parseEntries('scss/**.scss', 'css'),
  },
  output: {
    path: path.resolve(__dirname, 'grafema/dashboard/assets'),
    iife: false,
  },
  optimization: {
    minimize: true,
    minimizer: [
      new TerserPlugin({
        parallel: 4,
        extractComments: false,
        terserOptions: {
          compress: false,
          format: {
            comments: false,
            beautify: true,
            quote_style: 1,
          },
          keep_classnames: true, // save classes names
          keep_fnames: true, // save functions names
          mangle: false, // disable names obfuscation
        },
      }),
    ],
  },
  mode: 'production',
  module: {
    rules: [
      {
        test: /\.(sass|scss)$/,
        include: path.resolve(__dirname, 'src/scss'),
        use: [
          {
            loader: MiniCssExtractPlugin.loader,
            options: {},
          },
          {
            loader: 'css-loader',
            options: {
              sourceMap: false,
              url: false,
            },
          },
          {
            loader: 'postcss-loader',
            options: {
              postcssOptions: {
                plugins: [
                  require('autoprefixer')
                ],
              },
            },
          },
          {
            loader: 'sass-loader',
            options: {
              implementation: require('sass'),
              sourceMap: false,
            },
          },
        ],
      },
    ],
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: '[name].css',
    }),
    new CleanWebpackPlugin({
      cleanOnceBeforeBuildPatterns: ['**/*'],
      cleanAfterEveryBuildPatterns: ['css/**.min.js', 'css/**.js'],
    }),
  ],
}