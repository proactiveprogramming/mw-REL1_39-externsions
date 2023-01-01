/**
 * The script is a script for generating inline style.
 * Inline style is necessary for downloaded SVG.
 */

import fs from "fs";

new Promise((resolve) => {
  console.log("export default {");
  resolve();
}).then(() => {
  return new Promise((resolve) => {
    fs.readdir("target", (err, files) => {
      const cssFiles = files.filter((f) => {
        return f.endsWith(".min.css");
      });
      resolve(cssFiles);
    });
  });
}).then((cssFiles) => {
  const inlineTasks = cssFiles.map((f) => {
    return new Promise((resolve) => {
      fs.readFile("target/" + f, (err, data) => {
        const graph = f.slice(0,-8);
        const css = data.toString().replace(/\.render-the-fuck[^>]*>/g,"");
        const template = "  {GRAPH}: \"{CSS}\",";
        console.log(template.replace("{GRAPH}", graph).replace("{CSS}", css));
        resolve();
      });
    });
  });
  return Promise.all(inlineTasks);
}).then(() => {
  console.log("};");
});
