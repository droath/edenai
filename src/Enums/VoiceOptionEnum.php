<?php

declare(strict_types=1);

namespace Droath\Edenai\Enums;

/**
 * Voice gender/type options for text-to-speech synthesis.
 *
 * This enum represents the available voice options supported by the EdenAI
 * text-to-speech API. These values control the gender/type of the synthesized
 * voice output.
 *
 * @package Droath\Edenai\Enums
 */
enum VoiceOptionEnum: string
{
    /**
     * Female voice option for text-to-speech synthesis.
     */
    case FEMALE = 'FEMALE';

    /**
     * Male voice option for text-to-speech synthesis.
     */
    case MALE = 'MALE';
}
