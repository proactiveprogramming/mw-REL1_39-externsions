import { getInitialLanguagePair } from "./getInitialLanguagePair";

let mockWikiLanguage = "bn";
jest.mock("./mediawikiHelper", () => {
  return {
    siteMapper: {
      getCurrentWikiLanguageCode() {
        return mockWikiLanguage;
      },
    },
  };
});

mw.config.get = (parameter) => {
  if (parameter === "wgContentTranslationTranslateInTarget") {
    return false;
  }

  return null;
};

let urlFrom;
let urlTo;
jest.spyOn(URLSearchParams.prototype, "get").mockImplementation((key) => {
  if (key === "from") {
    return urlFrom;
  } else if (key === "to") {
    return urlTo;
  } else {
    return null;
  }
});

const supportedLanguageCodes = ["en", "es", "el", "de", "bn", "ig", "ha", "sq"];

describe("vuex application/actions/initializeLanguages tests", () => {
  // supportedLanguages refer to all languages that are supported by cxserver

  it("should respect URL params when they are properly supported/enabled", () => {
    urlFrom = "en";
    urlTo = "el";
    // both source and target languages have to be supported by cxserver
    // target language should also be enabled for Section Translation if enabledTargetLanguages
    // (in configuration parameter SectionTranslationTargetLanguages) are defined
    const pair1 = getInitialLanguagePair(null, supportedLanguageCodes);
    expect(pair1.sourceLanguage).toBe("en");
    expect(pair1.targetLanguage).toBe("el");
    const pair2 = getInitialLanguagePair(["el"], supportedLanguageCodes);

    // source language doesn't need to be enabled
    expect(pair2.sourceLanguage).toBe("en");
    expect(pair2.targetLanguage).toBe("el");
  });

  it("should fallback to wiki language (bn) as target language if URL param 'to' not supported or not enabled", () => {
    // sq not enabled here
    urlTo = "sq";
    const pair3 = getInitialLanguagePair(
      ["bn", "ig", "ha"],
      supportedLanguageCodes
    );
    expect(pair3.sourceLanguage).toBe("en");
    expect(pair3.targetLanguage).toBe("bn");

    // fr not supported here
    urlTo = "fr";
    const pair4 = getInitialLanguagePair(null, supportedLanguageCodes);
    expect(pair4.sourceLanguage).toBe("en");
    expect(pair4.targetLanguage).toBe("bn");
  });

  it("should fallback to default (es) as target language if no URL param 'to', not supported current wiki language and no enabled languages", () => {
    urlTo = null;
    // fr not supported here
    mockWikiLanguage = "fr";
    const pair5 = getInitialLanguagePair(null, supportedLanguageCodes);
    expect(pair5.sourceLanguage).toBe("en");
    expect(pair5.targetLanguage).toBe("es");
  });

  it("should fallback to first enabled language as target language if no URL param 'to' and not supported/enabled current wiki language (fr)", () => {
    const pair6 = getInitialLanguagePair(["ig", "ha"], supportedLanguageCodes);
    expect(pair6.sourceLanguage).toBe("en");
    expect(pair6.targetLanguage).toBe("ig");
  });
});
