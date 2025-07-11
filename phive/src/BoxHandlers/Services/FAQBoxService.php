<?php

declare(strict_types=1);

namespace Videoslots\BoxHandlers\Services;

use FAQBoxBase2;
use Laraphive\Domain\Content\DataTransferObjects\FAQCategory;
use Laraphive\Domain\Content\DataTransferObjects\FAQQuestion;

require_once __DIR__ . '/../../../modules/BoxHandler/boxes/diamondbet/FAQBoxBase2.php';

final class FAQBoxService
{
    /**
     * @var string
     */
    private const PAGE_ALIAS = 'faq';

    /**
     * @var string
     */
    private const PARENT_PAGE_ALIAS = 'customer-service';

    /**
     * @var string
     */
    private const BOX_CLASS = 'FAQBox2';


    /**
     * @var \FAQBoxBase2|null
     */
    private ?FAQBoxBase2 $FAQ_box_base = null;

    /**
     * @var int|null
     */
    private ?int $box_id = null;

    public function __construct()
    {
        $box_id = $this->findBoxId();

        if (! is_null($box_id)) {
            $this->FAQ_box_base = new FAQBoxBase2($box_id);
            $this->FAQ_box_base->init();
        }
    }

    /**
     * @api
     * @param string $search_term
     *
     * @return array
     */
    public function getFAQ(string $search_term): array
    {
        if (is_null($this->FAQ_box_base)) {
            return [];
        }

        if (! empty($search_term)) {
            return $this->searchFAQ($search_term);
        }

        return $this->getAllFAQ();
    }

    /**
     * @api
     * @param string $search_term
     *
     * @return array
     */
    public function searchFAQ(string $search_term): array
    {
        $lang = phive('Localizer')->getLanguage();

        $answers = $this->FAQ_box_base->searchStr($search_term, 'answer', $lang);
        $questions = $this->FAQ_box_base->searchStr($search_term, 'question', $lang);

        $merged = array_values(array_unique(array_merge($answers, $questions), SORT_REGULAR));

        // Filter out items with empty answer or question aliases
        $filtered = array_filter($merged, function($item) {
            return !empty($item['answer']['alias']) && !empty($item['question']['alias']);
        });

        return array_map(
            fn ($item) => new FAQQuestion(
                $item['question']['alias'],
                $item['answer']['alias'],
            ),
            array_values($filtered)
        );
    }

    /**
     * @api
     *
     * @return array
     */
    public function getAllFAQ(): array
    {
        return array_map(
            fn ($i) => new FAQCategory(
                "faqbox." . $this->findBoxId() . ".cat." . $i,
                array_map(
                    fn ($j) => new FAQQuestion(
                        "faqbox.question." . $this->findBoxId() . ".$i.$j",
                        "faqbox.answer." . $this->findBoxId() . ".$i.$j.html",
                    ),
                    range(0, $this->FAQ_box_base->numq_arr[$i] - 1)
                ),
            ),
            range(0, $this->FAQ_box_base->num_cat - 1),
        );
    }

    /**
     * @return int|null
     */
    private function findBoxId(): ?int
    {
        if ($this->box_id) {
            return $this->box_id;
        }

        $page_id = $this->getPageId();

        if (is_null($page_id)) {
            return null;
        }

        $query = sprintf(
            "SELECT box_id FROM boxes WHERE page_id = %s AND box_class = '%s'",
            $page_id,
            self::BOX_CLASS
        );

        $this->box_id = (int) phive('SQL')->getValue($query);

        return $this->box_id;
    }

    /**
     * @return int|null
     */
    private function getPageId(): ?int
    {
        $pager = phive('Pager');
        $parent = $pager->getPageByAlias(self::PARENT_PAGE_ALIAS);

        if (is_null($parent)) {
            return null;
        }

        $page = $pager->getPageByAlias(self::PAGE_ALIAS, (int) $parent['page_id']);

        if (is_null($page)) {
            return null;
        }

        return (int) $page['page_id'];
    }
}
