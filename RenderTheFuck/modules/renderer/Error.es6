export default function(d3, thefuck, WTF_PARAMS) {
  const svg = this;
  const MSG_H = 25;

  let msgCount = 1;
  if (typeof thefuck.settings.message !== "string") {
    msgCount = thefuck.settings.message.length;
  }
  const h = MSG_H * (msgCount + 0.6);

  svg.attr("height", h);
  svg.classed("rtf-error", true);

  // Draw error message
  let messages = thefuck.settings.message;
  if (typeof thefuck.settings.message === "string") {
    messages = [thefuck.settings.message];
  }
  svg.selectAll("text").data(messages).enter()
    .append("text")
    .attr("x", svg.attr("width") / 2)
    .attr("y", function(d, i) { return (i + 1) * MSG_H; })
    .attr("text-anchor", "middle")
    .attr("font-size", "1.2em")
    .attr("fill", "#a00000")
    .text(function(d) { return d; });
}
