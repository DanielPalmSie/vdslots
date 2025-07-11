const postcss = require('postcss');

const convertColorToUppercase = postcss.plugin('convertColorToUppercase', () => {
  return (root) => {
    root.walkDecls(decl => {
        const hexColorRegex = /#([a-f\d]{3,4}|[a-f\d]{6}|[a-f\d]{8})\b/gi;
        decl.value = decl.value.replace(hexColorRegex, (match) => match.toUpperCase());
    });
  };
});

module.exports = convertColorToUppercase;
