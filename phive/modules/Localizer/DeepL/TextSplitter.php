<?php

namespace Localizer\DeepL;

class TextSplitter
{
    private int $max_chunk_size;

    public function __construct($max_chunk_size)
    {
        $this->max_chunk_size = $max_chunk_size;
    }

    /**
     * Split string into chunks with size < max_chunk_size
     *
     * @param string $value
     * @return array<string>
     */
    public function getSplitted(string $value): array
    {
        $pieces = $this->splitIntoPieces($value);

        $chunks = [];
        $current_chunk = '';

        for ($i = 0; $i < count($pieces); $i++) {
            $piece = $pieces[$i];
            $is_reached_limit = strlen($current_chunk . $piece) > $this->max_chunk_size;
            $is_last_piece = $i === count($pieces) - 1;

            if ($is_reached_limit) {
                $chunks[] = $current_chunk;
                $current_chunk = '';
            }

            $current_chunk .= $piece;

            if ($is_last_piece) {
                $chunks[] = $current_chunk;
            }
        }

        return $chunks;
    }

    /**
     * Split string into small pieces that can be used to compose larger chunks.
     *
     * Flow:
     * 1. Split by <p> tag (Should be enough as <p> tag is widely used in our WYSIWYG editor
     *    which is the only place we usually get large strings from)
     *
     * 2. If it's not enough - split by any HTML tag
     * 3. If it's not enough - split by space character
     *
     * @param string $value
     * @return array
     */
    private function splitIntoPieces(string $value): array
    {
        // firstly split by paragraphs
        $paragraph_html_tag_regex = "<\/[\s]*p>";

        $splitted_by_paragraphs = preg_split("/($paragraph_html_tag_regex)/", $value, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        if ($this->isSplittedEnough($splitted_by_paragraphs)) {
            return $splitted_by_paragraphs;
        }

        // if splitting by paragraphs still leaves chunks > max_chunk_size - then split by all HTML tags
        $html_tag_regex = "<\/[\s]*[\w]+>";
        $splitted_by_html_tags = [];

        foreach ($splitted_by_paragraphs as $str) {
            if (strlen($str) < $this->max_chunk_size) {
                $splitted_by_html_tags[] = $str;
                continue;
            }

            $splitted_by_html_tags = array_merge(
                $splitted_by_html_tags,
                preg_split("/($html_tag_regex)/", $str, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE)
            );
        }

        if ($this->isSplittedEnough($splitted_by_html_tags)) {
            return $splitted_by_html_tags;
        }

        // if splitting by HTML tags still leaves chunks > max_chunk_size - then split by space characters.
        $splitted_by_spaces = [];

        foreach ($splitted_by_html_tags as $str) {
            if (strlen($str) < $this->max_chunk_size) {
                $splitted_by_spaces[] = $str;
                continue;
            }

            $splitted_by_spaces = array_merge(
                $splitted_by_spaces,
                preg_split("/(\s)/", $str, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE)
            );
        }

        return $splitted_by_spaces;
    }

    /**
     * Check if all chunks size < max_chunk_size
     *
     * @param array<string> $splitted
     * @return bool
     */
    private function isSplittedEnough(array $splitted): bool
    {
        foreach ($splitted as $string) {
            if (strlen($string) > $this->max_chunk_size) {
                return false;
            }
        }

        return true;
    }
}
