// d3.js
// Bundle size with: (tested at 2018-03-02)
// +------------------------------------+
// |    import    | compiled | uglified |
// |--------------|----------|----------|
// |  all d3 APIs |   830453 |   538174 |
// | used d3 APIs |   199245 |   125715 |
// +------------------------------------+
// import * as d3 from "d3";
import { select as d3Select } from "d3-selection";
import { selectAll as d3SelectAll } from "d3-selection";
import { mouse as d3Mouse } from "d3-selection";
import { line as d3Line } from "d3-shape";
import { path as d3Path } from "d3-path";
const d3 = {
  select: d3Select,
  selectAll: d3SelectAll,
  mouse: d3Mouse,
  line: d3Line,
  path: d3Path
};

// Validation schemas
import { validate } from "jsonschema";
import LineSchema from "./schemas/line.schema.json";
import StackSchema from "./schemas/stack.schema.json";
import TimelineSchema from "./schemas/timeline.schema.json";
const WTF_SCHEMAS = {
  line: LineSchema,
  stack: StackSchema,
  timeline: TimelineSchema
};

// Renderer functions
import TheFuckCommon from "./renderer/Common.es6";
import TheFuckError from "./renderer/Error.es6";
import TheFuckLine from "./renderer/Line.es6";
import TheFuckStack from "./renderer/Stack.es6";
import TheFuckTimeline from "./renderer/Timeline.es6";
const WTF_RENDERERS = {
  error: TheFuckError,
  line: TheFuckLine,
  stack: TheFuckStack,
  timeline: TheFuckTimeline
};

// Inline style
import TheFuckStyles from "./styles.es6";

// Global parameters for all renderers.
const WTF_PARAMS = {
  // Common settings
  margin: 15,
  elementMargin: 10,
  textMargin: 5,
  titleHeight: 24,
  subtitleHeight: 16,

  // Common functions
  fnValue: (d) => { return d; },
  fnAdd: (a, b) => { return a + b; },
  fnSub: (a, b) => { return a - b; },
  fnBetween: (n, a, b) => {
    const l = Math.max(a, b);
    const s = Math.min(a, b);
    return (n >= s && n <= l);
  },
  fnTranslate: (x, y) => {
    return "translate(X Y)"
      .replace("X", x)
      .replace("Y", y);
  },
  fnCartesianCoord: (x, y) => {
    return "matrix(1 0 0 -1 X Y)"
      .replace("X", x)
      .replace("Y", y);
  },
  fnMaxTextWidth: (sel) => {
    const allWidth = sel.selectAll("text").nodes().map((e) => {
      return e.getBBox().width;
    });
    return Math.max.apply(null, allWidth);
  }
};

// Scan hook points (CSS class render-the-fuck)
d3.selectAll(".render-the-fuck").each(function() {
  const svg = d3.select(this);
  const id  = svg.attr("id");
  let thefuck = window.RENDER_THE_FUCK[id];
  let hasError = false;

  const AVAILABLE_WTFS = ["line", "stack", "timeline"];
  if (AVAILABLE_WTFS.indexOf(thefuck.wtf) !== -1) {
    const vdErrors = validate(thefuck, WTF_SCHEMAS[thefuck.wtf]).errors;
    if (vdErrors.length > 0) {
      const vdMsg = vdErrors.map(function(err) {
        const n = err.property;
        return n.charAt(0).toUpperCase() + n.slice(1) + " " + err.message;
      });

      hasError = true;
      thefuck = {
        "wtf": "error",
        "settings": {
          "message": vdMsg
        }
      };
    }
  } else {
    hasError = true;
    if (thefuck.wtf !== "error") {
      thefuck = {
        "wtf": "error",
        "settings": {
          "message": "Unrecognize what the fuck."
        }
      };
    }
  }

  // Render the fuck.
  TheFuckCommon.call(svg, d3, thefuck, WTF_PARAMS);
  WTF_RENDERERS[thefuck.wtf].call(svg, d3, thefuck, WTF_PARAMS);

  // Render download link.
  if (!hasError) {
    // Create BLOB
    const ftid  = id + "-footer";
    const style = TheFuckStyles["common"] + TheFuckStyles[thefuck.wtf];
    const body  = svg.html();
    const w     = svg.attr("width");
    const h     = svg.attr("height");
    const blob  = new Blob(
      [
        "<?xml version=\"1.0\"?>",
        "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{W}\" height=\"{H}\">"
          .replace("{W}", w)
          .replace("{H}", h),
        body.replace("<defs></defs>", "<defs><style type=\"text/css\">" + style + "</style></defs>"),
        "</svg>"
      ],
      {"type": "image/xml+svg"}
    );
    const link = window.URL.createObjectURL(blob);

    // Create <a> element.
    d3.select("#" + ftid).append("a")
      .attr("href", link)
      .attr("download", id + ".svg")
      .text("Download");
  }
});
