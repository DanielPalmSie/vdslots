const { execSync } = require("child_process");
const path = require("path");
const projectRoot = path.resolve(__dirname, "../..");

try {
    console.log("▶ Compiling SCSS...");
    execSync(`npx node-sass --importer ${projectRoot}/node_modules/node-sass-package-importer/dist/cli.js --output-style expanded --source-map true --source-map-contents true --precision 6 ${projectRoot}/build/scss/adminlte.scss ${projectRoot}/dist/css/adminlte.css`, { stdio: "inherit" });

    console.log("▶ Adding PostCSS Prefixes...");
    execSync(`npx postcss --config ${projectRoot}/build/config/postcss.config.js --replace "${projectRoot}/dist/css/*.css" "!${projectRoot}/dist/css/*.min.css"`, { stdio: "inherit" });

    console.log("▶ Minifying CSS...");
    execSync(`npx cleancss -O1 --format breakWith=lf --with-rebase --source-map --source-map-inline-sources --batch --batch-suffix ".min" --output ${projectRoot}/dist/css/ ${projectRoot}/dist/css/*.css "!${projectRoot}/dist/css/*.min.css"`, { stdio: "inherit" });

    console.log("✅ SCSS Compilation Complete!");
} catch (error) {
    console.error("❌ Error during compilation: \n", error);
    process.exit(1);
}
