// https://github.com/jprichardson/node-fs-extra
const fs = require('fs-extra');

// https://github.com/adamreisnz/replace-in-file#readme
const replaceInFileSync = require('replace-in-file').replaceInFileSync;

// https://nodejs.org/api/readline.html#readline
const readline = require('node:readline');
const readlineInterface = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

// https://github.com/tj/commander.js#readme
const { program } = require('commander');
program
  .name('node create-new-brand.js')
  .description('Creates new brand from an existing source brand.')
  .requiredOption('-n, --name <string>', 'new brand name')
  .requiredOption('-s, --source <string>', 'source brand name')
;
const programOptions = program.parse().opts();

const config = {
  source_brand_name: programOptions.source,
  new_brand_name: programOptions.name,

  clone: [
    {
      message: 'Cloning brand SCSS files which load main SCSS files:',
      source: `../scss/__brand-templates__/root/`,
      destination: '../scss/<new_brand_name>/'
    },
    {
      message: 'Cloning included brand SCSS files:',
      source: '../scss/__brand-templates__/included/',
      destination: '../scss/included/<new_brand_name>/',
      postProcessing: [
        {
          message: 'Amending content in brand _variables.scss file:\n- Replacing NEW and __brand-templates__ string by the new brand name.',
          action: (message) => replaceInFiles({
              files: processPath('../scss/included/<new_brand_name>/_variables.scss'),
              from: [/NEW/g, /'__brand-templates__'/g],
              to: `'${config.new_brand_name}'`
            }, message)
        }
      ]
    },
    {
      message: 'Cloning SCSS brand variables files from the source brand:',
      source: '../scss/included/<source_brand_name>/variables/',
      destination: '../scss/included/<new_brand_name>/variables/',
      postProcessing: [
        {
          message: 'Amending images paths in SCSS new brand variables files.',
          action: (message) => replaceInFiles({
            files: [
              processPath('../scss/included/<new_brand_name>/variables/*'),
            ],
            from: [
              new RegExp(`../../images/${config.source_brand_name}`, 'g'),
              new RegExp(`/diamondbet/images/${config.source_brand_name}`, 'g'),
            ],
            to: '#{$brand-image-path}'
          }, message)
        }
      ]
    },
    {
      message: 'Cloning diamondbet root folder files from the source brand:',
      source: '../<source_brand_name>/',
      destination: '../<new_brand_name>/',
      postProcessing: [
        {
          message: 'Amending content in new brand diamondbet root folder.',
          action: (message) => replaceInFiles({
            files: processPath('../<new_brand_name>/*'),
            from: [
              new RegExp(config.source_brand_name, 'g'),
              new RegExp(capitalizeFirstLetter(config.source_brand_name), 'g')
            ],
            to: [
              config.new_brand_name,
              capitalizeFirstLetter(config.new_brand_name)
            ]
          }, message)
        }
      ]
    },
    {
      message: 'Cloning font files from the source brand:',
      source: '../fonts/<source_brand_name>/',
      destination: '../fonts/<new_brand_name>/'
    },
    {
      message: 'Cloning image files from the source brand:',
      source: '../images/<source_brand_name>/',
      destination: '../images/<new_brand_name>/'
    },
    {
      message: 'Cloning phive config files (be carefull, different repo!!!!):',
      source: '../../phive/config/brand-<source_brand_name>/',
      destination: '../../phive/config/brand-<new_brand_name>/',
      postProcessing: [
        {
          message: 'Replacing references to brand in all Phive config files.',
          action: (message) => replaceInFiles({
            files: [
              processPath('../../phive/config/brand-<new_brand_name>/*'),
              processPath('../../phive/config/brand-<new_brand_name>/*/*')
            ],
            from: [
              new RegExp(config.source_brand_name, 'g'),
              new RegExp(capitalizeFirstLetter(config.source_brand_name), 'g')
            ],
            to: [
              config.new_brand_name,
              capitalizeFirstLetter(config.new_brand_name)
            ]
          }, message)
        }
      ]
    }
  ],

  after_clonning: [{
    message: 'Amending the SASS compiler brand folders list.',
    action: (message) => {
      displayMessage(message);
      askYesNoQuestion(
        'Do you want to add new brand folder support to the SASS compiler AND check-scss-variables-order.js?',
        () => {
          replaceInFiles({
            files: [
              './webpack.config.js',
              './check-scss-variables-order.js'
            ],
            from: /const brandFolders = \[(.*?)\]/g,
            to: (match) => {
              const newBrandInArray = match.replace(']', `, '${config.new_brand_name}']`)
              return newBrandInArray;
            }
          }, 'OK, amending SASS compiler AND check-scss-variables-order.js code.')
        },
        () => displayMessage('OK, you answered NO, so you have to add new brand folder to SASS compiler AND check-scss-variables-order.js manually, please consult branding documentation.')
       )
    }
  }]
};

// https://stackoverflow.com/questions/9781218/how-to-change-node-jss-console-font-color
const messageColors = {
  original: '\x1b[0m',

  blue: '\x1b[36m',
  green: '\x1b[32m',
  red: '\x1b[31m',
  yellow: '\x1b[33m'
};

function displayMessage (message) {
  console.log(`\n${messageColors.green}${message}${messageColors.original}`);
}

function askYesNoQuestion(question, yesCallback, noCallback) {
  readlineInterface.question(`${messageColors.red}${question} (y/N)${messageColors.original}`, (answer) => {
    answer = answer.trim().toLowerCase();
    if (answer === 'y') {
     readlineInterface.close();
     return yesCallback();
    }

    readlineInterface.close();
    return noCallback();
  });
}

function replaceInFiles (options, message) {
  try {
    const results = replaceInFileSync(options);
    displayMessage(message);
    console.log('Replacement results:', results);
  }
  catch (error) {
    console.error('Error occurred:', error);
  }
}

function capitalizeFirstLetter(string) {
  return string.charAt(0).toUpperCase() + string.slice(1);
}

function processPath (path) {
  return path
    .replace('<source_brand_name>', config.source_brand_name)
    .replace('<new_brand_name>', config.new_brand_name)
  ;
}

function cloneBrand (config) {
  config.clone.forEach((clonedItem) => {
    const source = processPath(clonedItem.source);
    const destination = processPath(clonedItem.destination);

    const postProcessing = clonedItem.postProcessing ?? null;

    displayMessage(`${clonedItem.message}${messageColors.blue}\nsource: ${messageColors.yellow}${source}${messageColors.blue}\ndestination: ${messageColors.yellow}${destination}`);

    // copy directory, even if it has subdirectories or files
    fs.copySync(source, destination);

    if (postProcessing !== null) {
      postProcessing.forEach((postProcessingItem) => {
        postProcessingItem.action(postProcessingItem.message);
      });
    }
  });

  config.after_clonning.forEach((afterClonningItem) => {
    afterClonningItem.action(afterClonningItem.message);
  })
}

function main () {
  displayMessage(`Creating new brand: ${messageColors.yellow}${config.new_brand_name}${messageColors.green}\nfrom the source brand: ${messageColors.yellow}${config.source_brand_name}`);

  cloneBrand(config);
}

main();