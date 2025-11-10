<?php

declare(strict_types=1);

use Droath\Edenai\Enums\ServiceProviderEnum;

describe('ServiceProviderEnum', function (): void {
    test('has all required provider cases', function (): void {
        $cases = ServiceProviderEnum::cases();
        $caseNames = array_map(fn ($case) => $case->name, $cases);

        expect($caseNames)->toContain('GOOGLE')
            ->and($caseNames)->toContain('AMAZON')
            ->and($caseNames)->toContain('MICROSOFT')
            ->and($caseNames)->toContain('OPENAI')
            ->and($caseNames)->toContain('DEEPGRAM')
            ->and($caseNames)->toContain('ASSEMBLY_AI')
            ->and($caseNames)->toContain('REV_AI')
            ->and($caseNames)->toContain('SPEECHMATICS')
            ->and($caseNames)->toContain('IBMWATSON')
            ->and($caseNames)->toContain('AZURE');
    });

    test('value method returns correct lowercase strings for API requests', function (): void {
        expect(ServiceProviderEnum::GOOGLE->value)->toBe('google')
            ->and(ServiceProviderEnum::AMAZON->value)->toBe('amazon')
            ->and(ServiceProviderEnum::MICROSOFT->value)->toBe('microsoft')
            ->and(ServiceProviderEnum::OPENAI->value)->toBe('openai')
            ->and(ServiceProviderEnum::DEEPGRAM->value)->toBe('deepgram')
            ->and(ServiceProviderEnum::ASSEMBLY_AI->value)->toBe('assembly_ai')
            ->and(ServiceProviderEnum::REV_AI->value)->toBe('rev_ai')
            ->and(ServiceProviderEnum::SPEECHMATICS->value)->toBe('speechmatics')
            ->and(ServiceProviderEnum::IBMWATSON->value)->toBe('ibmwatson')
            ->and(ServiceProviderEnum::AZURE->value)->toBe('azure');
    });

    test('can be used in array contexts', function (): void {
        $providers = [
            ServiceProviderEnum::GOOGLE,
            ServiceProviderEnum::AMAZON,
            ServiceProviderEnum::OPENAI,
        ];

        expect($providers)->toHaveCount(3)
            ->and($providers[0])->toBe(ServiceProviderEnum::GOOGLE)
            ->and($providers[1])->toBe(ServiceProviderEnum::AMAZON)
            ->and($providers[2])->toBe(ServiceProviderEnum::OPENAI);
    });

    test('can be serialized for API requests', function (): void {
        $providers = [
            ServiceProviderEnum::GOOGLE,
            ServiceProviderEnum::DEEPGRAM,
        ];

        $serialized = array_map(fn ($provider) => $provider->value, $providers);

        expect($serialized)->toBe(['google', 'deepgram']);
    });

    test('enum is backed by string', function (): void {
        $reflection = new ReflectionEnum(ServiceProviderEnum::class);

        expect($reflection->isBacked())->toBeTrue()
            ->and($reflection->getBackingType()->getName())->toBe('string');
    });
});
