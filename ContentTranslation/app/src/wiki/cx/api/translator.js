import Translation from "../models/translation";
import MTProviderGroup from "../../mw/models/mtProviderGroup";
import { siteMapper } from "../../../utils/mediawikiHelper";
import PublishFeedbackMessage from "../models/publishFeedbackMessage";
import CorporaRestoredUnit from "../models/corporaRestoredUnit";

/**
 * @param {String} offset
 * @return {Promise<Translation[]>}
 */
async function fetchTranslations(offset) {
  if (mw.user.isAnon()) {
    return Promise.resolve([]);
  }
  const params = {
    action: "query",
    format: "json",
    assert: "user",
    formatversion: 2,
    list: "contenttranslation",
    sectiontranslationsonly: true,
  };

  if (offset) {
    params["offset"] = offset;
  }

  const api = new mw.Api();

  return api.get(params).then(async (response) => {
    const apiResponse = response.query.contenttranslation.translations;
    let results = apiResponse.map((item) => new Translation(item));

    if (response.continue?.offset) {
      const restOfResults = await fetchTranslations(response.continue.offset);
      results = results.concat(restOfResults);
    }

    return results;
  });
}

/**
 * @param {string} translationId
 * @return {Promise<CorporaRestoredUnit[]>}
 */
function fetchTranslationUnits(translationId) {
  if (mw.user.isAnon()) {
    return Promise.resolve([]);
  }
  const params = {
    action: "query",
    format: "json",
    assert: "user",
    formatversion: 2,
    translationid: translationId,
    list: "contenttranslation",
  };

  const api = new mw.Api();

  return api.get(params).then((response) => {
    const { translation } = response.query.contenttranslation;

    return Object.values(translation.translationUnits).map(
      (unit) => new CorporaRestoredUnit(unit)
    );
  });
}

/**
 * Fetches translation for a given sentence, language pair and MT provider service
 * and a promise that resolves to the translation. In case of api call failure,
 * it returns a promise that resolves to the provided sentence as is.
 * @param sourceLanguage
 * @param targetLanguage
 * @param provider
 * @param sentence
 * @param {String} token
 * @return {Promise<String>}
 */
async function fetchSegmentTranslation(
  sourceLanguage,
  targetLanguage,
  provider,
  sentence,
  token
) {
  if (!sentence) {
    return;
  }
  let relativeUrl = `/translate/${sourceLanguage}/${targetLanguage}`;

  if (provider !== MTProviderGroup.ORIGINAL_TEXT_PROVIDER_KEY) {
    relativeUrl += `/${provider}`;
  }

  const cxserverAPI = siteMapper.getCXServerUrl(relativeUrl);

  return fetch(cxserverAPI, {
    headers: { "Content-Type": "application/json", Authorization: token },
    method: "POST",
    body: JSON.stringify({ html: `<div>${sentence}</div>` }),
  })
    .then((response) => response.json())
    .then((data) => {
      // Remove root div that was added by cxserver
      const regExp = /<div>(?<content>(.|\s)*)<\/div>/;

      return regExp.exec(data.contents)?.groups?.content;
    })
    .catch((error) => Promise.reject(error));
}

/**
 * Given a wikitext representing a block template,
 * a language and a page title, this method returns
 * the HTML for the rendering of this template.
 *
 * @param {string} wikitext
 * @param {string} language
 * @param {string} title
 * @return {Promise<string>}
 */
const parseTemplateWikitext = (wikitext, language, title) => {
  const api = siteMapper.getApi(language);

  return Promise.resolve(
    api.post({
      origin: "*",
      action: "visualeditor",
      paction: "parsefragment",
      page: title,
      wikitext: wikitext,
      pst: true,
    })
  )
    .then((response) => response.visualeditor.content)
    .catch((error) => {
      mw.log.error(error);

      return Promise.reject(error);
    });
};

/**
 * Given a page title and a section number, this method returns
 * a promise which resolves to the contents of the section in the
 * given position, inside the given page. To fetch these contents,
 * a parse request to Action API is being send. Since a Promise is
 * being return, caller method is responsible to handle any Promise
 * rejection.
 * @param {String} pageTitle - The title of the requested page
 * @param {String} language - The language of the requested page
 * @param {Number} sectionNumber - A number that indicates the requested section position within the page sections
 * @return {Promise<string|null>}
 */
const getSectionContents = async (pageTitle, language, sectionNumber) => {
  const params = {
    action: "parse",
    page: pageTitle,
    format: "json",
    section: sectionNumber,
    disabletoc: true,
    disablelimitreport: true,
    disableeditsection: true,
    disablestylededuplication: true,
    formatversion: 2,
  };

  const api = siteMapper.getApi(language);

  try {
    // Sample request: https://en.wikipedia.org/w/api.php?action=parse&page=Oxygen&format=json&formatversion=2&section=2&disabletoc=true&disablelimitreport=true&disableeditsection=true&disablestylededuplication=true
    const response = await api.get(params);

    // Successful responses are always expected to contain "parse.text" path. Add checks anyway.
    return response?.parse?.text;
  } catch (error) {
    return null;
  }
};

/**
 * Given the appropriate publish parameters (html, source/target page titles,
 * source/target section titles, source/target languages, revision, section
 * position number), this method publishes a new section to the target page,
 * and returns a promise resolving to an object containing a "publishFeedbackMessage" and
 * a "targetTitle" property. When publishing is successful, the resolved object contains a null
 * "publishFeedbackMessage" property and a "targetTitle" property containing the URL-encoded
 * target title, as it is returned from the publishing API. In case of error, the resolved
 * object contains a PublishFeedbackMessage model as "publishFeedbackMessage" and a null
 * "targetTitle".
 *
 * @param {Object} publishParams
 * @param {String} publishParams.html - HTML to be published
 * @param {String} publishParams.sourceTitle
 * @param {String} publishParams.targetTitle
 * @param {String} publishParams.sourceSectionTitle
 * @param {String} publishParams.targetSectionTitle
 * @param {String} publishParams.sourceLanguage
 * @param {String} publishParams.targetLanguage
 * @param {Number} publishParams.revision
 * @param {String} publishParams.captchaId
 * @param {String} publishParams.captchaWord
 * @param {boolean} publishParams.isSandbox
 * @return {Promise<{publishFeedbackMessage: PublishFeedbackMessage|null, targetTitle: string|null}>}
 */
const publishTranslation = ({
  html,
  sourceTitle,
  targetTitle,
  sourceSectionTitle,
  targetSectionTitle,
  sourceLanguage,
  targetLanguage,
  revision,
  captchaId,
  captchaWord,
  isSandbox,
}) => {
  const params = {
    action: "cxpublishsection",
    title: targetTitle,
    html,
    sourcetitle: sourceTitle,
    sourcerevid: revision,
    sourcesectiontitle: sourceSectionTitle,
    targetsectiontitle: targetSectionTitle,
    sourcelanguage: sourceLanguage,
    targetlanguage: targetLanguage,
    issandbox: isSandbox,
  };

  if (captchaId) {
    params.captchaid = captchaId;
    params.captchaword = captchaWord;
  }
  const api = new mw.Api();

  return api
    .postWithToken("csrf", params)
    .then((response) => {
      response = response.cxpublishsection;

      if (response.result === "error") {
        if (response.edit.captcha) {
          return {
            publishFeedbackMessage: new PublishFeedbackMessage({
              type: "captcha",
              status: "error",
              details: response.edit.captcha,
            }),
            targetTitle: null,
          };
        }
        // there is no known case for which this error will be shown
        // this will be handled by the following "catch" block as "Unknown error"
        throw new Error();
      }

      return {
        publishFeedbackMessage: null,
        targetTitle: response.targettitle,
      };
    })
    .catch((error, details) => {
      let text;
      details = details || {};

      if (details.exception) {
        text = details.exception.message;
      } else if (details.error) {
        text = details.error.info;
      } else {
        text = "Unknown error";
      }

      return {
        publishFeedbackMessage: new PublishFeedbackMessage({
          text,
          status: "error",
        }),
        targetTitle: null,
      };
    });
};

/**
 * Given the appropriate parameters (source/target page titles, source/target section titles,
 * source/target languages, revision, section position number, content containing parallel
 * corpora translation units and section id of the page section to be saved), this method
 * sends a request to "sxsave" API action to store the draft translation to the "cx_translations"
 * database table, persist (if needed) the section translation into the "cx_section_translations"
 * table and store the parallel corpora translation units inside the "cx_corpora" table.
 * Finally, it returns a promise resolving to a PublishFeedbackMessage model in case
 * of error, or to null in case of successful saving.
 *
 * @param {object} publishParams
 * @param {string} publishParams.sourceTitle The title of the source page
 * @param {string} publishParams.targetTitle The title of the target page
 * @param {string} publishParams.sourceSectionTitle The title of the source section
 * @param {string} publishParams.targetSectionTitle The title of the target section
 * @param {string} publishParams.sourceLanguage The language of the source page
 * @param {string} publishParams.targetLanguage The language of the target page
 * @param {number} publishParams.revision The revision of the source page
 * @param {boolean} publishParams.isLeadSection Whether section is a lead section or not
 * @param {number|"new"} publishParams.units The parallel corpora translation units
 * @param {string} publishParams.sectionId The id of the source page section
 * @param {boolean} publishParams.isSandbox
 * @return {Promise<PublishFeedbackMessage|null>}
 */
const saveTranslation = ({
  sourceTitle,
  targetTitle,
  sourceSectionTitle,
  targetSectionTitle,
  sourceLanguage,
  targetLanguage,
  revision,
  isLeadSection,
  units,
  sectionId,
  isSandbox,
}) => {
  const params = {
    action: "sxsave",
    targettitle: targetTitle,
    sourcetitle: sourceTitle,
    sourcerevision: revision,
    sourcesectiontitle: sourceSectionTitle,
    targetsectiontitle: targetSectionTitle,
    sourcelanguage: sourceLanguage,
    targetlanguage: targetLanguage,
    isleadsection: isLeadSection,
    content: JSON.stringify(units),
    sectionid: sectionId,
    issandbox: isSandbox,
  };

  const api = new mw.Api();

  return api
    .postWithToken("csrf", params)
    .then(() => null)
    .catch((error, details) => {
      let text;

      if (details.exception) {
        text = details.exception.message;
      } else if (details.error) {
        text = details.error.info;
      } else {
        text = "Unknown error";
      }

      return new PublishFeedbackMessage({ text, status: "error" });
    });
};

export default {
  fetchTranslations,
  fetchTranslationUnits,
  fetchSegmentTranslation,
  parseTemplateWikitext,
  publishTranslation,
  saveTranslation,
};
