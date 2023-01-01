// The fuck stack
export default function(d3, thefuck, WTF_PARAMS) {
  const svg = this;
  const SVG_WIDTH = svg.attr("width");
  const SVG_HEIGHT = svg.attr("height");
  const BAR_HEIGHT = 30;
  const MARGIN = 20;
  const GAP = 5;

  // TODO: Need to optimize.
  const stackYFomula = function(i) {
    return SVG_HEIGHT - ((i + 2.5) * (BAR_HEIGHT + GAP));
  };

  svg.classed("rtf-stack", true);

  let parents = [{
    left: MARGIN,
    width: SVG_WIDTH - MARGIN * 2
  }];

  // Process each layer.
  thefuck.layers.forEach((layer, layerIdx) => {
    // Group items by parentIndex.
    const groups = parents.map((p, i) => {
      return layer.items.filter((item) => {
        return ((item.parent || 0) === i);
      });
    });

    const nextParents = [];

    // Draw each group.
    groups.forEach((groupItems, parentIdx) => {
      const childCount = groupItems.length;
      const parentItem = parents[parentIdx];
      const availWidth = parentItem.width - (childCount - 1) * GAP;
      const itemGroup  = svg.append("g").classed("itemGroup", true);
      const labelGroup = svg.append("g").classed("labelGroup", true);

      // Draw empty items to rotate color.
      for (let i = 0; i < (layerIdx + parentIdx) % 5; i++) {
        itemGroup.append("rect");
      }

      // Draw each item in group.
      groupItems.forEach((item, childIdx) => {
        var left  = parentItem.left + childIdx * ((availWidth / childCount) + GAP);
        var width = availWidth / childCount;
        nextParents.push({ left, width });

        // Draw bar.
        itemGroup.append("rect")
          .attr("x", left)
          .attr("y", stackYFomula(layerIdx))
          .attr("width", width)
          .attr("height", BAR_HEIGHT);

        // Draw name.
        labelGroup.append("text")
          .attr("x", left + width / 2)
          .attr("y", stackYFomula(layerIdx) + BAR_HEIGHT / 2)
          .text(item.name);
      });
    });

    parents = nextParents;
  });
}
