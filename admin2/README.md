**Welcome to Admin 2 (BackOffice).**

_Project installation notes:_
<https://wikijs.videoslots.com/en/home/development/for-new-hires/Admin2-Back-Office-Install-Instructions>

Admin2 is a Silex/Symfony project that uses AdminLTE template library for the project styling, found in `phive_admin/` folder.

### Project styling notes:
As part of the updates done in this project, the following changes were implemented:
* The AdminLTE version was updated from 2.x to 3.2.
* The project now uses Bootstrap 4.6.
* The project now uses JQuery 3.6
* Custom Styling Directory: A new folder structure, `phive_admin/customization`, was created to organize custom styles, plugins, and scripts.
  * `phive_admin/customization/css`: contains old css files with custom styling that are still used in the project.
  * `phive_admin/customization/scss`: contains SCSS files for introducing and managing custom styling.
    * `/skins`: this folder contains SCSS files for custom skins, allowing you to apply brand colors to the website. Skins are associated with the `APP_SKIN` variable in the .env file.
    * `/_colors.scss`: this file contains the color variables used throughout the project, enabling easy modification of the Bootstrap color scheme.
    * `/_overrides.scss`: this file includes custom styling for the project.
    * `/skins/index.scss` `/_colors.scss` and `/index.scss` are all imported into the `phive_admin/build/scss/adminlte.scss` file to compile the custom styles.
* ⚠️ Modifying AdminLTE
    * AdminLTE is designed to work **out of the box**, so `npm install` is **not required** unless you need to customize styles.
      However, AdminLTE 3.2 has outdated peer dependencies, requiring `--legacy-peer-deps` or `--force` to install. This can cause unwanted modifications to the `plugins/` folder.
      To avoid conflicts, we've **removed the postinstall script** and now install dependencies exactly as specified in `package-lock.json`.

### Compilation Process

**Node versions used for compilation:**
- 🟢 **Node.js:** v16.20.2
- 📦 **NPM:** v8.10.0

1. **Install dependencies:**
    In `phive_admin`, run:	

   ```bash
   npm ci --legacy-peer-deps --ignore-scripts
   ```
    This ensures that the `plugins/` folder remains unmodified and the packages are installed correctly according to the package-lock.json configuration.


2. **Run the SCSS compilation script**

   When updating project styling, modify the SCSS files in the `customization` folder. Then, navigate to `phive_admin/customization/scripts`, run the script, and recompile in `phive_admin`.
   ```bash
   node custom-scss-compile.js
   ```

    This process will generate the compiled CSS files in the `phive_admin/dist/css/` folder:
    * `adminlte.css`
    * `adminlte.css.map`
    * `adminlte.min.css` - minified compiled css file imported on layout.blade.php, this is the most important file to include all styling in the project.
    * `adminlte.min.css.map`


3. **Common issues and fixes when compiling**

   * Permission errors for `phive_admin` folder:
       ```bash
       chown -R www-data:www-data *
       ```
   * Issues with node-sass (e.g., missing or incompatible binaries):
   If you encounter issues with node-sass after running the script, run the following to rebuild the binaries:
     ```bash
     npm rebuild node-sass
     ```

## `bootstrap-wysihtml5` removal note

The `bootstrap-wysihtml5` plugin was removed from the project as it contained vulnerable Handlebars library version. The plugin provides a funcionality of WYSIWYG HTML editor, but wasn't used in the `admin2` project. If you need the WYSIWYG HTML editor functionality, either install [Summernote](https://github.com/summernote/summernote) or upgrade project's AdminLTE template to the latest version (which uses `Summernote` instead of `bootstrap-wysihtml5`).
