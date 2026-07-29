import Encore from '@symfony/webpack-encore';

if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
  .setOutputPath('public/build/')
  .setPublicPath('/build')

  .addEntry('seedlet', './assets/javascripts/seedlet.js')

  .splitEntryChunks()

  .enableSingleRuntimeChunk()

  .cleanupOutputBeforeBuild()

  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())

  .configureBabel((config) => {
    config.presets = [
      [
        '@babel/preset-env',
        {
          corejs: 3,
          targets: 'defaults and supports es6-module'
        }
      ]
    ];
  })

  .enableSassLoader()
  .copyFiles({
    from: './assets/images',
    to: 'images/[path][name].[hash:8].[ext]'
  })
;

export default await Encore.getWebpackConfig();
