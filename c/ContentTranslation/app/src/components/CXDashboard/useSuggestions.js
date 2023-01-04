import { computed, ref } from "vue";
import { useStore } from "vuex";
import { useEventLogging } from "@/plugins/eventlogging";
import useApplicationState from "@/composables/useApplicationState";

const useSuggestions = () => {
  const store = useStore();
  const { sourceLanguage, targetLanguage } = useApplicationState(store);

  const logEvent = useEventLogging();

  const sectionSuggestionsLoading = computed(
    () => store.state.suggestions.sectionSuggestionsLoadingCount > 0
  );
  const pageSuggestionsLoading = computed(
    () => store.state.suggestions.pageSuggestionsLoadingCount > 0
  );

  const showRefreshButton = computed(
    () => !sectionSuggestionsLoading.value && !pageSuggestionsLoading.value
  );

  /**
   * Current index of section suggestions slice to be displayed inside the Dashboard
   * @type {Ref<number>}
   */
  const currentSectionSuggestionsSliceIndex = ref(0);
  /**
   * Current index of section suggestions slice to be displayed inside the Dashboard
   * @type {Ref<number>}
   */
  const currentPageSuggestionsSliceIndex = ref(0);

  const { maxSuggestionsPerSlice } = store.state.suggestions;

  /**
   * Maximum number of different suggestion slices to be displayed inside the Dashboard.
   * Once, user suggestion refreshes reaches this limit, the first suggestion slice is
   * displayed again.
   *
   * @type {number}
   */
  const maxSuggestionsSlices = 4;

  /**
   * @type {ComputedRef<SectionSuggestion[]>}
   */
  const currentSectionSuggestionsSlice = computed(() =>
    store.getters["application/getSectionSuggestionsSliceByIndex"](
      currentSectionSuggestionsSliceIndex.value
    )
  );

  /**
   * @type {ComputedRef<ArticleSuggestion[]>}
   */
  const currentPageSuggestionsSlice = computed(() =>
    store.getters["application/getPageSuggestionsSliceByIndex"](
      currentPageSuggestionsSliceIndex.value
    )
  );

  /**
   * This action refreshes section and page suggestions current slice.
   *
   * 1. If current page or section suggestion slice is not full (less than 3 suggestions are displayed to the user),
   * this action will add enough suggestions to fill the suggestion slices.
   * 2. If current suggestion slices are full, current suggestion slices will be replaced by new ones.
   * 3. If maximum slices limit has been reached, the same suggestions are being shown starting from the beginning.
   */
  const onSuggestionRefresh = () => {
    // If next slice has not been fetched yet, and max slices not reached, fetch it now
    fetchNextSectionSuggestionSlice();
    fetchNextPageSuggestionSlice();
  };

  const fetchNextSectionSuggestionSlice = () => {
    const isCurrentSliceFull =
      currentSectionSuggestionsSlice.value.length === maxSuggestionsPerSlice;

    const nextIndex =
      (currentSectionSuggestionsSliceIndex.value + 1) % maxSuggestionsSlices;

    const isNextSliceFetched =
      isCurrentSliceFull &&
      store.getters["application/getSectionSuggestionsSliceByIndex"](nextIndex)
        .length > 0;

    if (!isCurrentSliceFull || !isNextSliceFetched) {
      store.dispatch("suggestions/fetchNextSectionSuggestionsSlice");
    }
    isCurrentSliceFull && increaseCurrentSectionSuggestionsSliceIndex();
  };

  const fetchNextPageSuggestionSlice = () => {
    const isCurrentSliceFull =
      currentPageSuggestionsSlice.value.length === maxSuggestionsPerSlice;

    const nextIndex =
      (currentPageSuggestionsSliceIndex.value + 1) % maxSuggestionsSlices;

    const isNextSliceFetched =
      isCurrentSliceFull &&
      store.getters["application/getPageSuggestionsSliceByIndex"](nextIndex)
        .length > 0;

    if (!isCurrentSliceFull || !isNextSliceFetched) {
      store.dispatch("suggestions/fetchNextPageSuggestionsSlice");
    }

    isCurrentSliceFull && increaseCurrentPageSuggestionsSliceIndex();
  };

  /**
   * @param {SectionSuggestion} suggestion
   */
  const discardSectionSuggestion = (suggestion) => {
    logEvent({
      event_type: "dashboard_discard_suggestion",
      translation_source_language: sourceLanguage.value,
      translation_target_language: targetLanguage.value,
    });
    store.commit("suggestions/removeSectionSuggestion", suggestion);
    fetchNextSectionSuggestionSlice();
  };

  /**
   * @param {ArticleSuggestion} suggestion
   */
  const discardPageSuggestion = (suggestion) => {
    logEvent({
      event_type: "dashboard_discard_suggestion",
      translation_source_language: sourceLanguage.value,
      translation_target_language: targetLanguage.value,
    });
    store.commit("suggestions/removePageSuggestion", suggestion);
    fetchNextPageSuggestionSlice();
  };

  /**
   * Current suggestions slice index belongs to [0, state.maxSuggestionsSlices - 1] range.
   * That is because user can get at most "maxSuggestionsSlices" suggestion pages. After
   * that limit has been reached, same suggestions are being displayed starting from the
   * first ones again.
   *
   * @return {number}
   */
  const increaseCurrentSectionSuggestionsSliceIndex = () =>
    (currentSectionSuggestionsSliceIndex.value =
      (currentSectionSuggestionsSliceIndex.value + 1) % maxSuggestionsSlices);

  /**
   * Current suggestions slice index belongs to [0, state.maxSuggestionsSlices - 1] range.
   * That is because user can get at most "maxSuggestionsSlices" suggestion pages. After
   * that limit has been reached, same suggestions are being displayed starting from the
   * first ones again.
   *
   * @return {number}
   */
  const increaseCurrentPageSuggestionsSliceIndex = () =>
    (currentPageSuggestionsSliceIndex.value =
      (currentPageSuggestionsSliceIndex.value + 1) % maxSuggestionsSlices);

  return {
    currentPageSuggestionsSlice,
    currentSectionSuggestionsSlice,
    discardPageSuggestion,
    discardSectionSuggestion,
    onSuggestionRefresh,
    pageSuggestionsLoading,
    sectionSuggestionsLoading,
    showRefreshButton,
  };
};

export default useSuggestions;
