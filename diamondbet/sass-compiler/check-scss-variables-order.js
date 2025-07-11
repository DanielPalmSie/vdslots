const fs = require("fs");
const path = require("path");

const SCSS_DIRECTORY_PATH = "../scss";
const VAR_DIRECTORY_PATH = "../scss/included/variables";

const brandFolders = ['mrvegas', 'kungaslottet', 'megariches', 'dbet'];

//Function to get all the scss files under variables
function getAllScssFileNameGroup(directory) {
    const files = fs.readdirSync(directory);
    const fileGroups = [];

    files.forEach((file) => {
        fileGroups.push([
            `../scss/included/variables/${file}`,
        ]);

        brandFolders.forEach((folder) => {
            fileGroups.push([
                `../scss/included/${folder}/variables/${file}`,
            ]);
        });
    });

    return fileGroups;
}

// Function to read a file and extract Sass variables names, ignoring values that are variables
function extractVariableNames(filePath) {
    const data = fs.readFileSync(filePath, "utf8");
    const lines = data.split("\n");
    const variableNames = [];

    lines.forEach((line) => {
        // Updated Regex to match both commented and uncommented Sass variable definitions
        const match = line.match(/^(\/\/)?\s*\$([\w-]+):\s*(.*);/);
        if (match) {
            const variableName = match[2]; // Adjusted to capture the correct group

            // We're now capturing all variable names, regardless of the value
            variableNames.push(variableName);
        }
    });

    return variableNames;
}

// Function to extract the order of variables as they first appear in a scss file
function extractVariableFirstUseOrder(filePath, variables) {
    const content = fs.readFileSync(filePath, "utf8");
    let variableFirstUseOrder = [];
    variables.forEach(variable => {
        const regex = new RegExp(`\\$${variable}\\b`, 'g');
        const index = content.search(regex);
        if (index !== -1) variableFirstUseOrder.push({variable, index});
    });

    variableFirstUseOrder.sort((a, b) => a.index - b.index);
    return variableFirstUseOrder.map(entry => entry.variable);
}

// Function to check the variable order between pairs of files
function checkVariablesOrderForPair(filePath, includedFilePath) {
    const variableDefinitionsOrder = extractVariableNames(includedFilePath);
    const variablesFirstUseOrder = extractVariableFirstUseOrder(filePath, variableDefinitionsOrder);
    const mismatches = []; // Store mismatches for this pair

    // if (variableDefinitionsOrder.length !== variablesFirstUseOrder.length) {
    //     return { type: 'countMismatch', filePath, includedFilePath, details: `Mismatch in the number of variables used between ${filePath} and ${includedFilePath}` };
    // }

    for (let i = 0; i < variablesFirstUseOrder.length; i++) {
        if (variableDefinitionsOrder[i] !== variablesFirstUseOrder[i]) {
            mismatches.push({ found: variableDefinitionsOrder[i], expected: variablesFirstUseOrder[i], Variable_position: `${i + 1}th variable in the file` });
            break;
        }
    }

    if (mismatches.length > 0) {
        return { type: 'orderMismatch', includedFilePath, mismatches };
    }

    return { type: 'consistent', filePath };
}

// Iterate through each .scss file in the ../scss directory and compare with its counterpart
function checkAllFiles() {
    const files = fs.readdirSync(SCSS_DIRECTORY_PATH);
    const results = []; // Collect all results here

    files.forEach(file => {
        if (path.extname(file) === ".scss") {
            const filePath = path.join(SCSS_DIRECTORY_PATH, file);
            const includedFilePath = path.join(VAR_DIRECTORY_PATH, '_' + file);

            if (fs.existsSync(includedFilePath)) {
                const result = checkVariablesOrderForPair(filePath, includedFilePath);
                results.push(result); // Collect result
            }
        }
    });

    // Now format and print a summary of results
    return formatAndPrintSummary(results);
}

function formatAndPrintSummary(results) {
    let consistentCount = 0;
    results.forEach(result => {
        if (result.type === 'consistent') {
            consistentCount++;
        } else if (result.type === 'orderMismatch') {
            console.log(`Order mismatches in ${result.includedFilePath}:`);
            result.mismatches.forEach(mismatch => {
                console.log({mismatch});
            });
        }
    });

    console.log(`${consistentCount} files have consistent variable order.`);
    console.log(`${results.length - consistentCount} files have inconsistencies.`);
    return results.length - consistentCount === 0;
}

function compareVariablesOrder(filePathSet, variablesSets) {
    const reference = variablesSets[0];
    let inconsistenciesFound = false;
    for (let i = 1; i < variablesSets.length; i++) {
        for (let j = 0; j < Math.min(reference.length, variablesSets[i].length); j++) {
            if (reference[j] !== variablesSets[i][j]) {
                console.log({
                    CompareFiles: `Mismatch found between ${filePathSet[0]} and ${filePathSet[i]}`,
                    expected: reference[j],
                    found: variablesSets[i][j],
                    Variable_position: `${j + 1}th variable in the file`
                });
                inconsistenciesFound = true;
                break; // Stop comparing after the first mismatch to simplify output
            }
        }
    }
    return inconsistenciesFound;
}

const checkVariablesOrder = () => {
    const filePathSetGroup = getAllScssFileNameGroup(VAR_DIRECTORY_PATH);
    let allConsistent = true;
    filePathSetGroup.forEach(filePathSet => {
        const variablesSets = filePathSet.map(extractVariableNames);
        if(compareVariablesOrder(filePathSet, variablesSets)) allConsistent = false;
    });
    return allConsistent;
}

// Run the check
let checkStatus = true;
console.log(`\nSummary:`);
console.log("\x1b[36m\n1. Checking if the variables are defined in the same order like in the main file:\n\x1b[0m")
if (!checkAllFiles()) {
    console.error("Variable order inconsistencies found.\n");
    checkStatus = false;
} else {
    console.log("Variable order is consistent across all files.\n");
}

console.log("\x1b[36m\n2. Checking if the variables files have the same number of lines and same variables order:\n\x1b[0m");
if (!checkVariablesOrder()) {
    console.error("Variables definition order between all the brands scss inconsistencies found.");
    checkStatus = false;
} else {
    console.log("Variable order between all the brands scss is consistent.\n");
}

if(!checkStatus) {
    process.exit(1);
}
