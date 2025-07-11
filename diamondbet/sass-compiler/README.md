This is a SCSS compiler for `diamondbet` repo based on Webpack. Please contact richard.krejci@videoslots.com, or another member of the site team who develops and maintains this software, with bugs reporting or features hints.

## Installation

The compiler is based on Webpack 5.98 which runs under Node.js. Webpack 5.98 should be ideally run under the current Long Term Support (LTS) Node.js release.

When you have the compatible Node.js installed, run:

```JS
cd sass-compiler
npm install
```

This will install all compiler dependencies into `css-compiler/node_modules/` folder.

IMPORTANT: Never commit content from the  `css-compiler/node_modules/` folder! It's actually set to be ignored by Git. On the other hand, if you are changing dependencies (installing a module update or some new module), always commit both the ` package.json` and `package-lock.json`.

## How the compiler works

There is a following folder/directory structure in the `diamondbet` repo:

```
/css (CSS filed compiled from the SCSS sources stored in scss/ folder)
/sass-compiler (the compiler folder)
  - webpack.config.js (compiler Webpack configuration)
  - postcss.config.js (Post-css plugins configuration: actually only the Autoprefixer plugin is used)
/scss (SCSS files for compilation)
  - included/ (SCSS files included in every SCSS file)
```

The compiler compiles content from SCSS files in the `scss/` folder into CSS files in the `css/` folder. The compilation is done 1:1, so each SCSS file has its own CSS counterpart (for example `all.scss` compiles into `all.css`).

The `scss/` folder includes a folder `included/`. In this folder a SCSS-specific code (variables, mixins, etc.) is placed. This code is automatically imported in all SCSS files.

## Use

IMPORTANT: **Do your changes only in SCSS files**, as edits in the CSS file will be overwritten by the next SCSS compilation!

### Watch mode

Most used mode. When you run the compiler in this mode, it automatically compiles SCSS file every time when it is saved.

#### Standard

Run:

```JS
cd sass-compiler
npm run watch
```

#### Polling

Above watch mode will perhaps not work in the case that you are running the script (and Node.js) inside a Unix container or/and virtual machine. If the watching is not working at all or there is some weird behaviour, run the watch script in **[polling mode](https://webpack.js.org/configuration/watch/#watchoptionspoll)**. There are two possibilities:

##### Polling interval is set to 1 second:

```bash
cd sass-compiler
npm run watch:poll
```

##### Polling interval is set to desired value:

```bash
cd sass-compiler
npm run watch -- --watch-options-poll=<polling_value>
```

`<polling_value>`: polling interval in milliseconds or `true` (sets polling interval to default value).

IMPORTANT: Webpack in polling mode with too short polling cycle interval might consume a lot of CPU (and laptop battery). So try to find reasonable polling interval.

#### Windows error message in WSL

When you run the compiler under WSL (Windows Subsystem for Linux), you might see following error message:

```js
Watchpack Error (initial scan): Error: EACCES: permission denied, lstat '/mnt/c/DumpStack.log.tmp'
Watchpack Error (initial scan): Error: EACCES: permission denied, lstat '/mnt/c/hiberfil.sys'
Watchpack Error (initial scan): Error: EACCES: permission denied, lstat '/mnt/c/pagefile.sys'
Watchpack Error (initial scan): Error: EACCES: permission denied, lstat '/mnt/c/swapfile.sys'
```

You can ignore it as it doesn't have any influence on the compilation.

### Compiled CSS files refresh in PhpStorm

In PhpStorm the compiled CSS file content displayed in the editor is not refreshed after the compilation (but it is refreshed on the disk) – that's common problem with all external compilers in this IDE. You don't need this functionality very often, because usually we inspect generated CSS inside browser. However, if you need it, there are following options:

When using `npm run watch` or `npm run watch:poll`:

- enforce the refresh after compilation using `File-Reload All From Disk` (`Alt-Ctrl-Y` in Windows or `Alt-Cmd-Y` on Mac)

  or

- enforce the refresh after compilation by clicking outside the editor window and back

  or

- use temporary other editor or IDE which updates the compiled content, such as Visual Studio Code

When **not using the watch mode**:

- set PhpStorm [file watcher](https://www.jetbrains.com/help/phpstorm/2021.2/settings-tools-file-watchers.html) (set `sass-compiler/build-dev.sh` as a program in the watcher, scope should be limited to `diamondbet/scss/` recursively). This works but performance is poor.

### SCSS variables check

The SASS compiler is essential tool for brands styling. There is very important that SCSS variables organised in brand related files are organised in proper way, i.e.:

- each brand variables file (in `scss/included/<brand_name>/variables/_<main_scss_file_name>.scss`) has in each brand folder:
  - same number of lines
  - variables definitions in the same order
- variables in the variables file are used in the same order like in the main SCSS file which uses these variables (i.e. `scss/<brand_name>/<main_scss_file_name>.scss`)

To avoid wrong organisation of variables, an utility `check-scss-variables-order.js` was created. It can be used in two ways:

- during the SCSS files editing, when `npm run watch:poll` or `npm run watch` command is running
- for oner-run batch checking, using the command `npm run check-vars`

In both cases the utility displays on console errors found. There is obligatory for developer to commit only such SCSS files where no error message is displayed by this utility.

### Colours notation in uppercases

The SCSS to CSS compilation produces CSS files where hexadecimal representation of colours is using strictly uppercased letters. (This allows us avoid diverse unnecessary merging confusions when the casing was automatically changed in some files, for example using Prettier.) This output is done using the utility `convert-color-to-uppercase.js`.

### Build for develop

Compiles all SCSS files with the same setting as the watch mode but stops after the compilation.

```js
cd sass-compiler
npm run build:dev
```

### Build for production

IMPORTANT: Do not use this for now!

Proof of concept, it compiles SCSS files into minified CSS counterparts saved in `/dist/css` folder. The plan is to use this kind of command for optimised CSS builds after we do more amendments in the Videoslots front-end code.

```js
cd sass-compiler
npm run build:production
```

## Autoprefixer

The compiler uses [Autoprefixer](https://autoprefixer.github.io/) for automatic addition of browser prefixes to compiled CSS files. It works "out of the box" and usually there is no need to change its configuration. However, there is good to know that the configuration is set in the `sass-compiler/postcss.config.js` file. There is better to have the Autoprefixer configuration inside the `package.json` file, but it is actually not working, see this issue https://stackoverflow.com/questions/68649649/postcss-loader-webpack-plugin-looks-for-package-json-in-a-wrong-folder.

## `create-new-brand.js` script

The `sass-compiler` folder also includes `create-new-brand.js` script which is used for the automatic creation of a new brand files in `diamondbet` and `phive-config` repos.

Before you run the script make sure that you have installed all dependencies. 

Then run the script using:
```shell
node create-new-brand.js --name <new_brand_name> --source <source_brand_name>
```

where

`<new_brand_name>` is the name of the brand you want to create, it should be in lowercases and one word without hyphens or underscores. (Example: `megafortunes`)

`<source_brand_name>` is the name of existing brand which files will be used for the new brand files creation. (Example: `megariches`)

Example:
````shell
node create-new-brand.js --name megafortunes --source kungaslottet
````

NOTE: If you run

```shell
node create-new-brand.js -h
```

or

```shell
node create-new-brand.js --help
```

the script displays its usage info and options:
```
> node create-new-brand.js -h
Usage: node create-new-brand.js [options]

Creates new brand from an existing
source brand.

Options:
  -n, --name <string>    new brand name
  -s, --source <string>  source brand name
  -h, --help             display help for command
```

## .DS_Store files bug

There is a macOS only specific bug related to the compilation. The issue is described here https://stackoverflow.com/questions/68590117/how-to-ignore-ds-store-file-in-webpack-scss-compilation. Actually, a temporary fix for this bug is to remove manually the problematic `.DS_Store` file before the compilation.

## ToDo

- use Autoprefixer configuration from the `package.json` (after this issue https://stackoverflow.com/questions/68649649/postcss-loader-webpack-plugin-looks-for-package-json-in-a-wrong-folder is resolved)
- exclude `.DS_Store` files from the compilation (after this issue https://stackoverflow.com/questions/68590117/how-to-ignore-ds-store-file-in-webpack-scss-compilation is resolved)

## SCSS Guidelines

### Pure CSS in SCSS

With a few small exceptions, SCSS is a superset of CSS, which means essentially **all valid CSS is valid SCSS as well**. So you can add without problems pure CSS into SCSS files, following [company's CSS style guidelines](https://wikijs.videoslots.com/en/home/development/general-processes/css-guidelines). However, there are also many SCSS specific features that help you write easily robust and maintainable CSS. Below we are focusing on the most beneficial.

### Variables

SCSS allows you use [variables](https://sass-lang.com/documentation/variables), which are basically values assigned to names which start with `$`. A variable declaration looks a lot like a CSS property declaration: it’s written `<variable>: <expression>`.  Despite their simplicity, variables are one of the most useful tools SASS brings to the table. Variables make it possible to reduce repetition, do complex math, avoid magical numbers, and much more.

SCSS

```scss
$base-color: #c6538c;
$border-dark: rgba($base-color, 0.88);

.button {
  background-color: $base-color;
}

.alert {
  border: 1px solid $border-dark;
}
```

CSS OUTPUT

```css
.button {
	background-color: #c6538c;
}

.alert {
	border: 1px solid rgba(198, 83, 140, 0.88);
}
```

### Mixins

[Mixins](https://sass-lang.com/documentation/at-rules/mixin) allow you to define styles that can be re-used throughout your stylesheet. There are defined using the `@mixin` at-rule, which is written `@mixin <name> { ... }` or `@mixin name(<arguments...>) { ... }`.

Mixins are included into the current context using the `@include` at-rule, which is written `@include <name>` or `@include <name>(<arguments...>)`, with the name of the mixin being included.

SCSS

```scss
@mixin important-text {
  color: red;
  font-size: 25px;
  font-weight: bold;
  border: 1px solid blue;
}

.warning {
  @include important-text;
  background-color: green;
}
```

CSS Output

```CSS
.warning {
	color: red;
	font-size: 25px;
	font-weight: bold;
	border: 1px solid blue;
	background-color: green;
}
```

### Media queries

Compared to CSS, SCSS provides for media queries following advantages:

- media queries can be nested
- you can use SCSS variables for the media queries breakpoints
- you can use SCSS mixins for easy media queries use

So you can write things like:

SCSS

```scss
$phone-width: 568px;
$tablet-width: 768px;
$desktop-small-width: 1024px;

@mixin small-phone {
  @media (max-width: #{$phone-width}) {
    @content;
  }
}

@mixin tablet {
  @media (min-width: #{$tablet-width}) and (max-width: #{$desktop-small-width - 1px}) {
    @content;
  }
}

.main-text {
  font-size: 16px;

  @include small-phone {
    font-size: 18px;
  }

  @include tablet {
    font-size: 20px;
  }
}
```

CSS Output

```CSS
.main-text {
	font-size: 16px;
}

@media (max-width: 568px) {
	.main-text {
		font-size: 18px;
	}
}

@media (min-width: 768px) and (max-width: 1023px) {
	.main-text {
		font-size: 20px;
	}
}
```

### Comments

SCSS supports two types of comments:

- **Multiline comments** defined using `/* */` that are (usually) compiled to CSS. By default, multi-line comments are stripped from the compiled CSS in [compressed mode](https://sass-lang.com/documentation/cli/dart-sass#style). If a comment begins with `/*!`, though, it will always be included in the CSS output.
- **Single line comments** (also called **silent comments**) defined using `//` that are not compiled to CSS. They allow adding internal comments which are never visible by the user of the website, so developers can use them for internal documentation (which is a recommended practice).

SCSS

```SCSS
// This comment won't be included in the CSS.

/* But this comment will, except in compressed mode. */

/* It can also contain interpolation:
 * 1 + 1 = #{1 + 1} */

/*! This comment will be included even in compressed mode. */

p /* Multi-line comments can be written anywhere
   * whitespace is allowed. */ .sans {
  font: Helvetica, // So can single-line commments.
        sans-serif;
}
```

CSS

```css
/* But this comment will, except in compressed mode. */
/* It can also contain interpolation:
 * 1 + 1 = 2 */
/*! This comment will be included even in compressed mode. */
p .sans {
	font: Helvetica, sans-serif;
}
```

### Nesting

Sass lets you nest CSS selectors in the same way as HTML. Here's an example:

SCSS

```scss
.parent {
  color: red;

  .child {
    color: blue;
  }
}
```

CSS Output

```CSS
.parent {
	color: red;
}

.parent .child {
	color: blue;
}
```

However, nesting makes code complicated and complex, see for example [this explanation](https://www.sitepoint.com/beware-selector-nesting-sass/). There is better to use nesting only in a few special cases, for example for adding pseudo-classes and pseudo-elements:

SCSS

```SCSS
.element {
  /* Some CSS declarations */

  &:hover,
  &:focus {
    /* More CSS declarations for hover/focus state */
  }

  &::before {
    /* Some CSS declarations for before pseudo-element */
  }
}
```

CSS Output

```CSS
.element {
  /* Some CSS declarations */;
}

.element:hover, .element:focus {
    /* More CSS declarations for hover/focus state */;
}

.element::before {
    /* Some CSS declarations for before pseudo-element */;
}
```

Otherwise, try to avoid the SCSS nesting.

### More info

Official SCSS (SASS) documentation can be found [here](https://sass-lang.com/documentation).

You can improve your existing SCSS skills essentially reading this [Sass Guidelines](https://sass-guidelin.es/).
