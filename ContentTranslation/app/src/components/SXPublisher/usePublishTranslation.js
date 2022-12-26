import { getUrl } from "@/utils/mediawikiHelper";
import siteApi from "@/wiki/mw/api/site";
import useApplicationState from "@/composables/useApplicationState";
import { computed, ref, watch } from "vue";

const decodeHtml = (html) => {
  const template = document.createElement("div");
  template.innerHTML = html;

  return template.innerText;
};

/**
 * @param {Store} store
 * @param {RefImpl<boolean>} isPublishDialogActive
 * @param {RefImpl<boolean>} isPublishingDisabled
 * @param {string|null} targetTitle the URL-encoded title of the target article
 * @return {Promise<void>}
 */
const handlePublishResult = async (
  store,
  isPublishDialogActive,
  isPublishingDisabled,
  targetTitle
) => {
  if (isPublishingDisabled.value) {
    isPublishDialogActive.value = false;

    return;
  }
  const {
    currentSourceSection,
    sourceLanguage,
    targetLanguage,
    currentSourcePage,
  } = useApplicationState(store);

  const translatedTitle = currentSourceSection?.value.title;
  const isSandboxTarget = store.getters["application/isSandboxTarget"];

  // the rest of the code inside this method is only executed when publishing is successful
  if (currentSourceSection.value.isLeadSection && !isSandboxTarget) {
    // Add wikibase link, wait for it, but failure is acceptable
    try {
      await siteApi.addWikibaseLink(
        sourceLanguage.value,
        targetLanguage.value,
        currentSourcePage.value.title,
        translatedTitle // TODO: This is wrong and should be fixed. Target page title should be used here, NOT target section title
      );
    } catch (error) {
      mw.log.error("Error while adding wikibase link", error);
    }
  }
  /** Remove warning about leaving SX */
  store.commit("application/setTranslationInProgress", false);

  // for successful publishing, targetTitle is required to be a non-empty string
  if (!targetTitle) {
    const errorMessage =
      "[CX] Target title is empty after successful publishing";
    mw.log.error(errorMessage);
    throw new Error(errorMessage);
  }

  // sx-published-section query param will trigger 'sx.publishing.followup'
  // module to be loaded inside target article's page, after redirection.
  // Since targetTitle is URL encoded, we should decode it before using it
  // as an argument for "mw.util.getUrl"
  location.href = getUrl(decodeURIComponent(targetTitle), {
    "sx-published-section": decodeHtml(translatedTitle),
    "sx-source-page-title": decodeHtml(currentSourcePage.value.title),
    "sx-source-language": sourceLanguage.value,
    "sx-target-language": targetLanguage.value,
  });
};

const usePublishTranslation = (store) => {
  const isPublishDialogActive = ref(false);
  const publishStatus = ref("pending");
  const publishOptionsOn = ref(false);
  const captchaDialogOn = ref(false);
  const captchaDetails = ref(null);
  /**
   * Feedback messages that contain publishing-related warnings or errors. If only
   * warnings exist inside this array, publishing is enabled. If at least one error
   * exist, publishing functionality is disabled.
   * @type {Ref<PublishFeedbackMessage[]>}
   */
  const publishFeedbackMessages = ref([]);

  const isPublishingDisabled = computed(() =>
    publishFeedbackMessages.value.some((message) => message.isError)
  );

  watch(publishOptionsOn, (newValue) => {
    if (!newValue) {
      publishFeedbackMessages.value = [];
    }
  });

  /**
   * @param {string|number|null} captchaAnswer
   */
  const doPublish = async (captchaAnswer = null) => {
    /**
     * Set initial publish status to "pending" before
     * publish request
     */
    publishStatus.value = "pending";
    isPublishDialogActive.value = true;

    /** @type {{publishFeedbackMessage: PublishFeedbackMessage|null, targetTitle: string|null}} */
    const { publishFeedbackMessage, targetTitle } = await store.dispatch(
      "translator/publishTranslation",
      { captchaId: captchaDetails.value?.id, captchaAnswer }
    );

    // if the feedback message is of type "captcha", set the captcha details and open the captcha dialog
    if (!!publishFeedbackMessage && publishFeedbackMessage.type === "captcha") {
      captchaDetails.value = publishFeedbackMessage.details;
      isPublishDialogActive.value = false;
      captchaDialogOn.value = true;

      return;
    } else if (!!publishFeedbackMessage) {
      publishFeedbackMessages.value.push(publishFeedbackMessage);
      publishFeedbackMessages.value.sort((m1, m2) => +m2.isError - +m1.isError);
    }
    // make sure to reset captcha details, when no CAPTCHA is requested
    captchaDetails.value = null;
    publishStatus.value = isPublishingDisabled.value ? "failure" : "success";
    /**
     * Show feedback animation to user for 1 second
     * before handling the publishing result
     */
    setTimeout(
      () =>
        handlePublishResult(
          store,
          isPublishDialogActive,
          isPublishingDisabled,
          targetTitle
        ),
      1000
    );
  };

  const onCaptchaDialogClose = () => {
    captchaDialogOn.value = false;
    captchaDetails.value = null;
  };

  const configureTranslationOptions = () => (publishOptionsOn.value = true);

  return {
    captchaDetails,
    captchaDialogOn,
    configureTranslationOptions,
    doPublish,
    isPublishDialogActive,
    isPublishingDisabled,
    onCaptchaDialogClose,
    publishOptionsOn,
    publishFeedbackMessages,
    publishStatus,
  };
};

export default usePublishTranslation;
