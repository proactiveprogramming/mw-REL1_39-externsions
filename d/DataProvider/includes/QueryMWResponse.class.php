<?php

class QueryMWResponse extends MWResponse {

    /**
     * Get pages contents
     *
     * @return array
     */
    public function getContents() {
        $result = array();
        if (array_key_exists('query', $this->_data)) {
            $query = $this->_data['query'];
            if (array_key_exists('pages', $query)) {
                $pages = $query['pages'];
                foreach($pages as $pageId => $attributes) {
                    $revisions = $attributes['revisions'];
                    foreach($revisions as $revision) {
                        $result[$pageId] = $revision['*'];
                        break;
                    }

                }
            }
        }
        return $result;
    }

    /**
     * @return null|string
     */
    public function getContent() {
        $result = null;
        $contents = $this->getContents();
        foreach ($contents as $content) {
            $result = $content;
            break;
        }
        return $result;
    }

}
