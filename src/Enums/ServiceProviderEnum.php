<?php


declare(strict_types=1);

namespace Droath\Edenai\Enums;

/**
 * Package-wide enumeration of AI service providers supported by Eden AI.
 *
 * This enum provides type-safe provider selection across all resource types.
 * Each case maps to the Eden AI provider identifier string used in API requests.
 *
 * @package Droath\Edenai\Enums
 */
enum ServiceProviderEnum: string
{
    case GOOGLE = 'google';
    case AMAZON = 'amazon';
    case MICROSOFT = 'microsoft';
    case OPENAI = 'openai';
    case DEEPGRAM = 'deepgram';
    case ASSEMBLY_AI = 'assembly_ai';
    case REV_AI = 'rev_ai';
    case SPEECHMATICS = 'speechmatics';
    case IBMWATSON = 'ibmwatson';
    case AZURE = 'azure';
    case API4AI = 'api4ai';
    case BASE64 = 'base64';
    case CLARIFAI = 'clarifai';
    case MINDEE = 'mindee';
    case SENTISIGHT = 'sentisight';
    case MISTRAL = 'mistral';
}
