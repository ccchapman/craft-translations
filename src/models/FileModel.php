<?php
/**
 * Translations for Craft plugin for Craft CMS 3.x
 *
 * Translations for Craft eliminates error prone and costly copy/paste workflows for launching human translated Craft CMS web content.
 *
 * @link      http://www.acclaro.com/
 * @copyright Copyright (c) 2018 Acclaro
 */

namespace acclaro\translations\models;

use Craft;
use craft\base\Model;
use yii\validators\NumberValidator;
use acclaro\translations\Constants;
use acclaro\translations\Translations;
use craft\elements\Asset;
use craft\elements\GlobalSet;
use craft\helpers\ElementHelper;
use craft\validators\SiteIdValidator;
use craft\validators\DateTimeValidator;

/**
 * @author    Acclaro
 * @package   Translations
 * @since     1.0.0
 */
class FileModel extends Model
{
    /**
     * @var \acclaro\translations\services\repository\FileRepository $_service
     */
    private $_service;

    public $id;

    public $orderId;

    public $elementId;

    public $draftId;

    public $sourceSite;

    public $targetSite;

    public $status;

    public $wordCount;

    public $source;

    public $target;

    public $previewUrl;

    public $serviceFileId;

    public $dateUpdated;

    public $dateDelivered;

    public $dateDeleted;

    public $reference;

    public function init(): void
    {
        parent::init();

        $this->_service = Translations::$plugin->fileRepository;

        $this->status = $this->status ? : Constants::FILE_STATUS_NEW;
        $this->sourceSite = $this->sourceSite ?: '';
        $this->targetSite = $this->targetSite ?: '';
    }

    public function rules(): array
    {
        return [
            [['orderId', 'elementId', 'draftId', 'sourceSite', 'targetSite'], 'required'],
            [['sourceSite', 'targetSite'], SiteIdValidator::class],
            ['wordCount', NumberValidator::class],
            [['dateCreated', 'dateUpdated', 'dateDelivered', 'dateDeleted'], DateTimeValidator::class],
        ];
    }

    public function getStatusLabel()
    {
        switch ($this->status) {
            case Constants::FILE_STATUS_MODIFIED:
                return 'Modified';
            case Constants::FILE_STATUS_PREVIEW:
            case Constants::FILE_STATUS_IN_PROGRESS:
                return 'In progress';
            case Constants::FILE_STATUS_REVIEW_READY:
                return 'Ready for review';
            case Constants::FILE_STATUS_COMPLETE:
                return 'Ready to apply';
            case Constants::FILE_STATUS_CANCELED:
                return 'Canceled';
            case Constants::FILE_STATUS_PUBLISHED:
                return 'Applied';
            case Constants::FILE_STATUS_FAILED:
                return 'Failed';
            default :
                return 'New';
        }
    }

    public function getStatusColor()
    {
        switch ($this->status) {
            case Constants::FILE_STATUS_MODIFIED:
                return 'purple';
            case Constants::FILE_STATUS_PREVIEW:
            case Constants::FILE_STATUS_IN_PROGRESS:
                return 'orange';
            case Constants::FILE_STATUS_REVIEW_READY:
                return 'yellow';
            case Constants::FILE_STATUS_COMPLETE:
                return 'blue';
            case Constants::FILE_STATUS_FAILED:
            case Constants::FILE_STATUS_CANCELED:
                return 'red';
            case Constants::FILE_STATUS_PUBLISHED:
                return 'green';
            default:
                return '';
        }
    }

    public function hasDraft()
    {
        return $this->_service->getDraft($this);
    }

    public function isNew()
    {
        return $this->status === Constants::FILE_STATUS_NEW;
    }

    public function isModified()
    {
        return $this->status === Constants::FILE_STATUS_MODIFIED;
    }

    public function isCanceled()
    {
        return $this->status === Constants::FILE_STATUS_CANCELED;
    }

    public function isComplete()
    {
        return $this->status === Constants::FILE_STATUS_COMPLETE;
    }

    public function isInProgress()
    {
        return $this->status === Constants::FILE_STATUS_IN_PROGRESS;
    }

    public function isReviewReady()
    {
        return $this->status === Constants::FILE_STATUS_REVIEW_READY;
    }

    public function isPublished()
    {
        return $this->status === Constants::FILE_STATUS_PUBLISHED;
    }

	public function getCpEditUrl()
	{
		return Translations::$plugin->urlGenerator->generateFileUrl($this->getElement(), $this);
	}

	public function getUiLabel()
	{
        if ($this->isComplete() && $this->hasDraft()) {
			return Translations::$plugin->orderRepository->getFileTitle($this);
		}
		if ($element = $this->getElement($this->isPublished())) {
			if (isset($element->title)) return $element->title;
			if (isset($element->name)) return $element->name;
		}
		return 'Not Found!';
	}

	public function getPreviewUrl()
	{
        $previewUrl = Translations::$plugin->urlGenerator->generateFileWebUrl($this->getElement(), $this);

		if ($this->isPublished()) return $previewUrl;

		return $this->previewUrl ?? $previewUrl;
	}

	public function hasSourceTargetDiff()
	{
		$hasDiff = false;
		if ($this->isReviewReady() || $this->isComplete() || $this->isPublished()) {
			$hasDiff = (bool) Translations::$plugin->fileRepository->getSourceTargetDifferences(
				$this->source, $this->target);
		}

		return $hasDiff;
	}

    public function getElement()
	{
        $element = Translations::$plugin->elementRepository->getElementById($this->elementId, $this->targetSite);

		if (! $element) {
            $element = Translations::$plugin->elementRepository->getElementById($this->elementId, $this->sourceSite);
		}

        /** Check element as an entry could have been deleted */
        if ($element && $element->getIsDraft()) {
			$element = $element->getCanonical();
		}

		return $element;
	}

    public function hasTmMisalignments($ignoreReference = false)
    {
        if ($this->reference !== null) {
            return $ignoreReference ?: $this->_service->isReferenceChanged($this);
        }

        return $this->_service->hasTmMisalignments($this);
    }

    public function getTmMisalignmentFile()
    {
        $element = Translations::$plugin->elementRepository->getElementById($this->elementId, $this->sourceSite);

        $targetSite = $this->targetSite;
        $source = $this->source;

        $targetElement = Translations::$plugin->elementRepository->getElementById($this->elementId, $targetSite);

        if ($this->isComplete()) {
            $draft = $this->_service->getDraft($this);
            $targetElement = $draft ?: $targetElement;
        }

        if ($element instanceof GlobalSet) {
            $entrySlug= ElementHelper::normalizeSlug($element->name);
        } else if ($element instanceof Asset) {
            $assetFilename = $element->getFilename();
            $fileInfo = pathinfo($assetFilename);
            $entrySlug= basename($assetFilename, '.' . $fileInfo['extension']);
        } else {
            $entrySlug= $element->slug;
        }

        $targetLang = Translations::$plugin->siteRepository->normalizeLanguage(Craft::$app->getSites()->getSiteById($targetSite)->language);

        $filename = sprintf('%s-%s_%s_%s_TM.%s',$this->elementId, $entrySlug, $targetLang, date("Ymd\THi"), Constants::FILE_FORMAT_CSV);

        $TmData = [
            'sourceContent' => $source,
            'sourceElementSite' => $this->sourceSite,
            'targetElement' => $targetElement,
            'targetElementSite' => $targetSite
        ];
        $fileContent = Translations::$plugin->elementToFileConverter->createTmFileContent($TmData);

        return [
            'fileName' => $filename,
            'fileContent' => $fileContent
        ];
    }
}
