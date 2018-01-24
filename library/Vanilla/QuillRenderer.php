<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;

/**
 * A PHP quill.js renderer for Vanilla.
 */
class QuillRenderer {

    /**
     * Render an HTML string from a quill string delta.
     *
     * @param string $deltaString - A Quill insert-only delta. https://quilljs.com/docs/delta/.
     */
    public function renderDelta(string $deltaString) {
        $html = "";
        $blocks = $this->makeBlocks($deltaString);

        foreach($blocks as $block) {
            $html .= $this->renderBlock($block);
        }

        return $html;
    }

    /**
     * Make the quill operations array.
     *
     * @param string $deltaString - A Quill insert-only delta. https://quilljs.com/docs/delta/.
     *
     * @returns QuillBlock[]
     */
    private function makeBlocks(string $deltaString): array {
        $delta = json_decode($deltaString, true);

        /** @var QuillOperation[] $operations */
        $operations = [];

        foreach($delta as $opArray) {
            $operations[] = new QuillOperation($opArray);
        }
        $blockFactory = new QuillBlockFactory($operations);

        return $blockFactory->blocks;
    }

    /**
     * Render an block element.
     *
     * @param QuillBlock $block The block of operations to render.
     */
    private function renderBlock(QuillBlock $block) {
        $attributes = [];
        $addNewLine = false;
        $result = "";

        // Don't render no-ops
        if (count($block->operations) < 1) {
            return "";
        }

        switch($block->blockType) {
            case QuillBlock::TYPE_PARAGRAPH:
                $containerTag = "p";

                foreach ($block->operations as $op) {
                    // Replace only a newline with just a break.
                    $op->content = preg_replace("/^\\n$/", "<br>", $op->content);
                    // Replace double newlines with an opening and closing <p> tags and a <br> tag.
                    $op->content = preg_replace("/[\\n]{2,}/", "</p><p><br></p><p>", $op->content);
                    // Replace all newlines with opening and closing <p> tags.
                    $op->content = preg_replace("/\\n/", "</p><p>", $op->content);
                }

                if ($block->blockIndentLevel > 0) {
                    $attributes["class"] = 'ql-indent-'.$block->blockIndentLevel;
                }
                break;
            case QuillBlock::TYPE_BLOCKQUOTE:
                $containerTag = "blockquote";
                break;
            case QuillBlock::TYPE_HEADER:
                $containerTag = "h" . $block->headerLevel;
                break;
            case QuillBlock::TYPE_LIST:
                $containerTag = $block->listType === QuillBlock::LIST_TYPE_BULLET ? "ul" : "ol";
                break;
            case QuillBlock::TYPE_CODE:
                $containerTag = "pre";
                $attributes = [
                    "class" => "ql-syntax",
                    "spellcheck" => "false",
                ];
                $addNewLine = true;
                break;
            default:
                return "";
        }

        $result .= "<$containerTag";
        foreach ($attributes as $attrKey => $attrValue) {
            $result .= " $attrKey=\"$attrValue\"";
        }
        $result .= ">";

        foreach($block->operations as $key => $op) {
            $result .= $this->renderOperation($op);
        }

        if ($addNewLine) {
            $result .= "\n";
        }

        $result .= "</$containerTag>";
        return $result;
    }

    /**
     * Render a string type operation
     *
     * @param QuillOperation $operation
     */
    private function renderOperation(QuillOperation $operation) {
        // Don't render ops without content.
        if ($operation->content === "") {
            return "";
        }

        $tags = [];

        if ($operation->list) {
            $listTag = ["name" => "li"];
            $indent = $operation->indent;
            if ($indent > 0) {
                $listTag["attributes"] = [
                    "class" => "ql-indent-$indent",
                ];
            }
            $tags[] = $listTag;
        }

        if ($operation->link) {
            $tags[] = [
                "name" => "a",
                "attributes" => [
                    "href" => $operation->link,
                    "target" => "_blank"
                ],
            ];
        }

        if ($operation->bold) {
            $tags[] = ["name" => "strong"];
        }

        if ($operation->italic) {
            $tags[] = ["name" => "em"];
        }

        if ($operation->strike) {
            $tags[] = ["name" => "s"];
        }

        $beforeTags = [];
        $afterTags = [];
        foreach ($tags as $tag) {
            $openingTag = "<{$tag['name']}";

            if (val("attributes", $tag)) {
                foreach ($tag["attributes"] as $attrKey => $attr) {
                    $openingTag .= " $attrKey=\"$attr\"";
                }
            }
            $openingTag .= ">";
            array_push($beforeTags, $openingTag);
            array_unshift($afterTags, "</{$tag['name']}>");
        }

        return implode("", $beforeTags) . $operation->content . implode("", $afterTags);
    }

    /**
     * Render an image type operation
     *
     * @param QuillOperation $operation
     */
    private function renderImageInsert(QuillOperation $operation) {
        return "<p>".$operation->content."</p>";
    }
}