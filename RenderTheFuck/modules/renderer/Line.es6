// The fuck line
export default function(d3, thefuck, P) {
  const svg = this;
  svg.classed("rtf-line", true);

  const legendCircleR = 7;
  const legendTextHeight = 12;
  const focusedPointR = 4;
  const focusedLabelHeight = 20;
  const xScaleTextHeight = 11;

  // Draw legends.
  const legendIcon = svg.append("g").classed("legendIcon", true);
  const legendText = svg.append("g").classed("legendText", true);
  const fnLegendY = function(d, i) { return i * 25; };
  legendIcon.selectAll("circle").data(thefuck.lines).enter().append("circle")
    .attr("cx", 0)
    .attr("cy", fnLegendY)
    .attr("r", legendCircleR);
  legendText.selectAll("text").data(thefuck.lines).enter().append("text")
    .attr("x", 0)
    .attr("y", fnLegendY)
    .text(function(d) { return d.name; });

  // Move legends to proper location.
  const legendTextWidth = P.fnMaxTextWidth(legendText);
  const textTx = svg.attr("width") - (P.margin + legendTextWidth);
  const textTy = P.margin + focusedLabelHeight + P.textMargin + legendTextHeight;
  const iconTx = textTx - (legendCircleR + 5);
  const iconTy = P.margin + focusedLabelHeight + P.textMargin + legendCircleR;
  legendIcon.attr("transform", P.fnTranslate(iconTx, iconTy));
  legendText.attr("transform", P.fnTranslate(textTx, textTy));

  // Draw chart area
  const chart = svg.append("g").classed("chart", true);
  const chartBg = chart.append("rect");

  // Get min/max value
  const extremeValues = thefuck.lines.map((line) => {
    return {
      min: Math.min.apply(null, line.data),
      max: Math.max.apply(null, line.data)
    };
  });
  const min = Math.min.apply(null, extremeValues.map((item) => { return item.min; }));
  const max = Math.max.apply(null, extremeValues.map((item) => { return item.max; }));

  // Get lBound, uBound
  const dist = max - min;
  const scaleExp = Math.floor(Math.log10(dist)-1);
  let scaleBase = 1;
  for (let b of [2, 5]) {
    if (10 * b * Math.pow(10, scaleExp) < dist) {
      scaleBase = b;
    }
  }
  const scale = scaleBase * Math.pow(10, scaleExp);
  const lBound = Math.floor(min / scale - 1) * scale;
  const uBound = Math.ceil(max / scale + 1) * scale;

  // Draw y scale
  let origY = svg.attr("height") - (P.margin + P.titleHeight + xScaleTextHeight + P.textMargin);
  if (thefuck.settings.subtitle) {
    origY -= P.subtitleHeight;
  }
  const chartHeight = origY - P.margin - focusedLabelHeight - P.textMargin;
  const yRatio = chartHeight / (uBound - lBound);
  const valueToY = function(d) { return Math.floor((d - lBound) * yRatio); };
  const yScaleGroup = chart.append("g").classed("yscale", true);
  const scaleValues = [];
  for (let v=lBound; v<uBound; v+=scale*2) {
    scaleValues.push(v);
  }
  yScaleGroup.selectAll("line").data(scaleValues).enter().append("line")
    .attr("x1", 0)
    .attr("y1", valueToY)
    .attr("x2", 5)
    .attr("y2", valueToY);
  yScaleGroup.selectAll("text").data(scaleValues).enter().append("text")
    .attr("x", 0)
    .attr("y", 0)
    .attr("transform", function(d) { return P.fnCartesianCoord(-5, valueToY(d) - 5); })
    .text(P.fnValue);
  const yScaleWidth = P.fnMaxTextWidth(yScaleGroup);

  // Move chart to proper location.
  const origX = P.margin + yScaleWidth + 5;
  chart.attr("transform", P.fnCartesianCoord(origX, origY));

  // TODO: Draw x scale
  const usedWidth = [
    P.margin,
    yScaleWidth,
    P.textMargin,
    P.elementMargin,
    legendCircleR * 2 + 1,
    P.textMargin,
    legendTextWidth,
    P.margin
  ];
  const chartWidth = usedWidth.reduce(P.fnSub, svg.attr("width"));
  const xRatio = chartWidth / (thefuck.labels.length - 1);
  const xScaleGroup = chart.append("g").classed("xscale", true);
  const indices = [
    1,
    Math.floor(thefuck.labels.length / 2),
    thefuck.labels.length - 2
  ];
  xScaleGroup.selectAll("line").data(indices).enter().append("line")
    .attr("x1", function(d) { return d * xRatio; })
    .attr("y1", 0)
    .attr("x2", function(d) { return d * xRatio; })
    .attr("y2", 5);
  xScaleGroup.selectAll("text").data(indices).enter().append("text")
    .attr("transform", function(d) { return P.fnCartesianCoord(d * xRatio, - (xScaleTextHeight + P.textMargin)); })
    .text(function(d) { return thefuck.labels[d]; });

  // Resize background
  chartBg
    .attr("width", chartWidth)
    .attr("height", chartHeight);

  // Axises
  const axisPath = d3.path();
  axisPath.moveTo(0, chartHeight);
  axisPath.lineTo(0, 0);
  axisPath.lineTo(chartWidth, 0);
  chart.append("path").classed("axis", true)
    .attr("d", axisPath.toString());

  // Draw lines
  const pathFunction = d3.line()
    .x(function(d, i) { return Math.floor(i * xRatio); })
    .y(function(d) { return Math.floor((d - lBound) * yRatio); });
  const lineGroup = chart.append("g").classed("line", true);
  lineGroup.selectAll("path").data(thefuck.lines).enter()
    .append("path")
    .attr("d", function(d) { return pathFunction(d.data); });

  // Draw Focused Position
  let defaultFocusedPosition = thefuck.labels.length - 1;
  if (thefuck.settings.defaultIndex !== undefined) {
    defaultFocusedPosition = thefuck.settings.defaultIndex;
  }
  const focusedPosition = chart.append("g").classed("focusedPosition", true)
    .attr("visibility", "hidden");
  const focusedAxis = focusedPosition.append("line").classed("focusedAxis", true)
    .attr("x1", 0)
    .attr("y1", 0)
    .attr("x2", 0)
    .attr("y2", chartHeight);
  const focusedLabel = focusedPosition.append("text").classed("focusedLabel", true)
    .attr("transform", "scale(1 -1)")
    .attr("x", 0)
    .attr("y", -P.textMargin);
  const focusedPointGroup = focusedPosition.append("g").classed("focusedPoint", true);
  focusedPointGroup.selectAll("circle").data(thefuck.lines).enter()
    .append("circle")
    .attr("r", focusedPointR);
  const focusedValueBorderGroup = focusedPosition.append("g").classed("focusedValueBorder", true)
    .attr("transform", "translate(9 -12)");
  focusedValueBorderGroup.selectAll("rect").data(thefuck.lines).enter()
    .append("rect")
    .attr("x", 0)
    .attr("y", 0)
    .attr("rx", 8)
    .attr("ry", 8)
    .attr("width", 50)
    .attr("height", 25);
  const focusedValueGroup = focusedPosition.append("g").classed("focusedValue", true)
    .attr("transform", "translate(14 -1) scale(1 -1)");
  focusedValueGroup.selectAll("text").data(thefuck.lines).enter()
    .append("text")
    .attr("x", 0)
    .attr("y", 0);
  const popFocusedAxis = function(index) {
    const focusedAxisX = Math.floor(index * xRatio);
    const values = thefuck.lines.map((line) => {
      return line.data[index];
    });

    focusedAxis
      .attr("x1", focusedAxisX)
      .attr("x2", focusedAxisX);

    focusedLabel
      .attr("transform", P.fnCartesianCoord(focusedAxisX, chartHeight))
      .text(thefuck.labels[index]);

    focusedPointGroup.selectAll("circle").data(values)
      .attr("cx", focusedAxisX)
      .attr("cy", valueToY);

    // Draw value and border sequentially.
    values.forEach((d, i) => {
      const currBorder = focusedValueBorderGroup.select("rect:nth-child(N)".replace("N", i + 1));
      const currValue  = focusedValueGroup.select("text:nth-child(N)".replace("N", i + 1));
      const currY1 = valueToY(d);
      const currY2 = valueToY(d) + 25;

      // Prevent border from overlapping.
      let currX = focusedAxisX;
      for (let n = i - 1; n >= 0; n--) {
        const prevBorder = focusedValueBorderGroup.select("rect:nth-child(N)".replace("N", n + 1));
        const prevY1 = parseInt(prevBorder.attr("y"));
        const prevY2 = prevY1 + 25;
        if (P.fnBetween(currY1, prevY1, prevY2) || P.fnBetween(currY2, prevY1, prevY2)) {
          const prevX = parseInt(prevBorder.attr("x"));
          const prevW = parseInt(prevBorder.attr("width"));
          currX = prevX + prevW + P.textMargin;
          break;
        }
      }

      currBorder.attr("x", currX);
      currBorder.attr("y", currY1);
      currValue.attr("transform", P.fnTranslate(currX, -1 * valueToY(d)));
      currValue.text(d);
    });

    // Display
    focusedPosition.attr("visibility", "visible");
  };

  if (defaultFocusedPosition >= 0) {
    popFocusedAxis(defaultFocusedPosition);
  }

  let currentFocusedPosition = defaultFocusedPosition;
  const eventReceiver = chart.append("rect").classed("eventReceiver", true)
    .attr("width", chartWidth)
    .attr("height", chartHeight);

  eventReceiver.on("mousemove", function() {
    const index = Math.round(d3.mouse(this)[0] / xRatio);
    if (index >= 0 && index < thefuck.labels.length) {
      currentFocusedPosition = index;
      popFocusedAxis(index);
    }
  });

  // Unexpected action occured.
  eventReceiver.on("mouseleave", function() {
    currentFocusedPosition = defaultFocusedPosition;
    if (currentFocusedPosition === defaultFocusedPosition) {
      if (defaultFocusedPosition < 0) {
        focusedPosition.attr("visibility", "hidden");
      } else {
        popFocusedAxis(defaultFocusedPosition);
      }
    }
  });
}
