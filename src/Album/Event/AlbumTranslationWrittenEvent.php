<?php declare(strict_types=1);

namespace Shopware\Album\Event;

use Shopware\Api\Write\WrittenEvent;

class AlbumTranslationWrittenEvent extends WrittenEvent
{
    const NAME = 'album_translation.written';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getEntityName(): string
    {
        return 'album_translation';
    }
}
