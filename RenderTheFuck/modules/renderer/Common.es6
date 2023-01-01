// The fuck line
export default function(d3, thefuck, WTF_PARAMS) {
  const svg = this;

  // Draw background
  svg.append("rect").classed("bg", true)
    .attr("x", 0)
    .attr("y", 0)
    .attr("rx", 15)
    .attr("ry", 15)
    .attr("width", "100%")
    .attr("height", "100%");

  // Draw graph title
  if (thefuck.settings !== undefined) {
    const x = svg.attr("width") / 2;
    let y = svg.attr("height") - WTF_PARAMS.margin;

    if (thefuck.settings.subtitle) {
      svg.append("text")
        .classed("subtitle", true)
        .attr("x", x)
        .attr("y", y)
        .text(thefuck.settings.subtitle);
      y -= WTF_PARAMS.subtitleHeight;
    }

    svg.append("text")
      .classed("title", true)
      .attr("x", x)
      .attr("y", y)
      .text(thefuck.settings.title);
  }
}
