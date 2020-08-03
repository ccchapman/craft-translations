<?php
/**
 * Translations for Craft plugin for Craft CMS 3.x
 *
 * Translations for Craft eliminates error prone and costly copy/paste workflows for launching human translated Craft CMS web content.
 *
 * @link      http://www.acclaro.com/
 * @copyright Copyright (c) 2018 Acclaro
 */

namespace acclaro\translations\services\fieldtranslator;

use Craft;
use craft\base\Field;
use craft\base\Element;
use acclaro\translations\services\App;
use acclaro\translations\Translations;
use acclaro\translations\services\ElementTranslator;

class MatrixFieldTranslator extends GenericFieldTranslator
{
    /**
     * {@inheritdoc}
     */
    public function toTranslationSource(ElementTranslator $elementTranslator, Element $element, Field $field)
    {
        $source = array();

        $blocks = $element->getFieldValue($field->handle)->all();

        if ($blocks) {
            
            $new = 0;
            foreach ($blocks as $block) {
                $blockId = $block->id ?? 'new' . ++$new;
                $blockSource = $elementTranslator->toTranslationSource($block);

                foreach ($blockSource as $key => $value) {
                    $key = sprintf('%s.%s.%s', $field->handle, $blockId, $key);

                    $source[$key] = $value;
                }
            }
        }

        return $source;
    }

    /**
     * {@inheritdoc}
     */
    public function toPostArray(ElementTranslator $elementTranslator, Element $element, Field $field)
    {
        $fieldHandle = $field->handle;

        $blocks = $element->getFieldValue($fieldHandle)->all();

        if (!$blocks) {
            return [];
        }

        $post = array(
            $fieldHandle => array(),
        );

        $new = 0;
        foreach ($blocks as $i => $block) {
            $blockId = $block->id ?? 'new' . ++$new;
            $post[$fieldHandle][$blockId] = array(
                'type'              => $block->getType()->handle,
                'enabled'           => $block->enabled,
                // 'enabledForSite'    => isset($block->getAttributes()['enabledForSite']) ? $block->getAttributes()['enabledForSite'] : null,
                // 'siteId'            => $element->siteId,
                'fields'            => $elementTranslator->toPostArray($block),
            );
        }
        return $post;
    }

    /**
     * {@inheritdoc}
     */
    public function toPostArrayFromTranslationTarget(ElementTranslator $elementTranslator, Element $element, Field $field, $sourceSite, $targetSite, $fieldData)
    {
        $fieldHandle = $field->handle;
        
        // echo '<pre>';
        // echo "//======================================================================<br>// matrix translator toPostArrayFromTranslationTarget()<br>//======================================================================<br>";
        // var_dump($fieldData);
        $blocks = $element->getFieldValue($fieldHandle)->all();
        
        $post = array(
            $fieldHandle => array(),
        );
        
        $fieldData = array_values($fieldData);        
        
        $new = 0;
        foreach ($blocks as $i => $block) {
            // echo '<pre>';
            //     echo "//======================================================================<br>// blocks()<br>//======================================================================<br>";
            // var_dump($block->id);
            $blockId = $block->id ?? 'new' . ++$new;
            $block->id = $blockId;
            $blockData = isset($fieldData[$i]) ? $fieldData[$i] : array();
            
            // echo '<pre>';
            // echo "//======================================================================<br>// fieldHandle()<br>//======================================================================<br>";
            // var_dump($block->id);
            // var_dump($block->getType()->handle);
            // var_dump($block->getAttributes()['enabled']);
            // var_dump(isset($block->getAttributes()['enabledForSite']) ? $block->getAttributes()['enabledForSite'] : null);
            // var_dump($targetSite);
            // var_dump($elementTranslator->toPostArrayFromTranslationTarget($block, $sourceSite, $targetSite, $blockData, true));
            
            $post[$fieldHandle][$blockId] = array(
                'type'              => $block->getType()->handle,
                'enabled'           => $block->getAttributes()['enabled'],
                'enabledForSite'    => isset($block->getAttributes()['enabledForSite']) ? $block->getAttributes()['enabledForSite'] : null,
                'siteId'            => $targetSite,
                'fields'            => $elementTranslator->toPostArrayFromTranslationTarget($block, $sourceSite, $targetSite, $blockData, true),
            );
        }
        // echo '<pre>';
        // echo "//======================================================================<br>// post<br>//======================================================================<br>";
        // var_dump($post);
        
        return $post;
    }

    /**
     * {@inheritdoc}
     */
    public function getWordCount(ElementTranslator $elementTranslator, Element $element, Field $field)
    {
        $blocks = $this->getFieldValue($elementTranslator, $element, $field)->all();

        if (!$blocks) {
            return 0;
        }

        $wordCount = 0;

        foreach ($blocks as $i => $block) {
            $wordCount += $elementTranslator->getWordCount($block);
        }

        return $wordCount;
    }
}