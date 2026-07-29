# Frontend build

The frontend is built with [Webpack Encore](https://symfony.com/doc/current/frontend.html),
configured in [webpack.config.js](../webpack.config.js):

- Single entry point: `assets/javascripts/seedlet.js`, which imports the ES module classes
  under `assets/javascripts/modules/` and the Sass entry point `assets/styles/seedlet.scss`.
- Output goes to `public/build/`, referenced by the Twig templates via
  `symfony/webpack-encore-bundle`'s `encore_entry_script_tags()`/`encore_entry_link_tags()`
  (see `config/packages/webpackEncore.php`).
- Babel (`@babel/preset-env` + `core-js` polyfills) targets `defaults and supports es6-module`.
- Sass is compiled through `sass-loader`; `quietDeps` is enabled because Bootstrap 5's own SCSS
  still relies on legacy Dart Sass APIs that would otherwise flood the build output with
  deprecation warnings unrelated to this project's own styles.
- Images under `assets/images` are copied and content-hashed into `public/build/images`.
- Third-party CSS assets ([Bootstrap](https://getbootstrap.com/), [Font Awesome](https://fontawesome.com/),
  Open Sans via `@openfonts/open-sans_latin-ext`) are imported directly from JS/Sass rather than
  loaded from a CDN, so the whole frontend is bundled and versioned by Encore.

## Building assets

```
make asset        # development build (make start uses this)
make asset-prod    # production build, minified + content-hashed (make deploy uses this)
make watch          # development build, rebuilt automatically on file changes
```

## Linting

Frontend code style is enforced with [semistandard](https://github.com/standard/semistandard):

```
make js-cs-test        # check
make js-cs-test-fix    # check and auto-fix
```

`make check` runs this alongside the PHP-CS-Fixer check (see [development.md](development.md)).
