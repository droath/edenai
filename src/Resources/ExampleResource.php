<?php

declare(strict_types=1);

namespace Droath\Edenai\Resources;

use JsonException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Droath\Edenai\DTOs\ExampleRequestDTO;
use Droath\Edenai\DTOs\ExampleResponseDTO;

/**
 * Example resource implementation demonstrating the resource pattern.
 *
 * This class serves as a reference implementation for creating API resources.
 * It demonstrates:
 * - Extending AbstractResource
 * - Implementing getBasePath() for endpoint grouping
 * - Using HTTP verb methods from parent class
 * - Request DTO validation before sending
 * - Response DTO parsing after receiving
 *
 * Usage example:
 * ```php
 * $client = new ApiClient(
 *     baseUrl: 'https://api.example.com',
 *     apiKey: 'your-api-key',
 * );
 *
 * $resource = new ExampleResource($client);
 *
 * // List all examples
 * $response = $resource->list();
 *
 * // Get a specific example
 * $example = $resource->get('abc123');
 * echo $example->id; // 'abc123'
 * echo $example->count; // 42
 *
 * // Create a new example
 * $request = new ExampleRequestDTO(
 *     name: 'John Doe',
 *     age: 30,
 *     status: 'active',
 *     tags: ['php', 'api'],
 * );
 * $newExample = $resource->create($request);
 * ```
 *
 * @package Droath\Edenai\Resources
 */
final class ExampleResource extends AbstractResource
{
    /**
     * Get the base path for example endpoints.
     *
     * All example operations will be prefixed with '/example'.
     * For example:
     * - list() -> GET /example
     * - get($id) -> GET /example/{id}
     * - create($dto) -> POST /example
     *
     * @return string The base path '/example'
     */
    public function getBasePath(): string
    {
        return '/example';
    }

    /**
     * List all examples.
     *
     * Sends a GET request to /example to retrieve all examples.
     *
     * @return ResponseInterface The raw PSR-7 HTTP response
     *
     * @throws \Droath\Edenai\Exceptions\ApiException On HTTP errors
     * @throws \Droath\Edenai\Exceptions\NetworkException On network failures
     */
    public function list(): ResponseInterface
    {
        return $this->get('');
    }

    /**
     * Get a specific example by ID.
     *
     * Sends a GET request to /example/{id} and parses the response into
     * an ExampleResponseDTO instance.
     *
     * @param string $id The example ID to retrieve
     *
     * @return ExampleResponseDTO The parsed response data
     *
     * @throws \Droath\Edenai\Exceptions\ApiException On HTTP errors
     * @throws \Droath\Edenai\Exceptions\NetworkException On network failures
     * @throws JsonException If response JSON is invalid
     */
    public function getById(string $id): ExampleResponseDTO
    {
        $response = $this->get('/' . $id);

        $data = json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return ExampleResponseDTO::fromResponse($data);
    }

    /**
     * Create a new example.
     *
     * Validates the request DTO, sends a POST request to /example with the
     * serialized data, and parses the response into an ExampleResponseDTO.
     *
     * This method demonstrates:
     * - Request DTO validation (happens in ExampleRequestDTO constructor)
     * - Serialization via toArray() method
     * - Response parsing via fromResponse() factory method
     *
     * @param ExampleRequestDTO $request The validated request data
     *
     * @return ExampleResponseDTO The parsed response data
     *
     * @throws \Droath\Edenai\Exceptions\ApiException On HTTP errors
     * @throws \Droath\Edenai\Exceptions\NetworkException On network failures
     * @throws InvalidArgumentException If request DTO validation fails
     * @throws JsonException If response JSON is invalid
     */
    public function create(ExampleRequestDTO $request): ExampleResponseDTO
    {
        // Request DTO is already validated in constructor
        // toArray() provides serialized data for HTTP request body
        $response = $this->post('', $request->toArray());

        $data = json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return ExampleResponseDTO::fromResponse($data);
    }

    /**
     * Update an existing example.
     *
     * Sends a PUT request to /example/{id} with the updated data.
     *
     * @param string $id The example ID to update
     * @param ExampleRequestDTO $request The validated request data
     *
     * @return ExampleResponseDTO The parsed response data
     *
     * @throws \Droath\Edenai\Exceptions\ApiException On HTTP errors
     * @throws \Droath\Edenai\Exceptions\NetworkException On network failures
     * @throws InvalidArgumentException If request DTO validation fails
     * @throws JsonException If response JSON is invalid
     */
    public function update(string $id, ExampleRequestDTO $request): ExampleResponseDTO
    {
        $response = $this->put('/' . $id, $request->toArray());

        $data = json_decode(
            $response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return ExampleResponseDTO::fromResponse($data);
    }

    /**
     * Delete an example by ID.
     *
     * Sends a DELETE request to /example/{id}.
     *
     * @param string $id The example ID to delete
     *
     * @return ResponseInterface The raw PSR-7 HTTP response
     *
     * @throws \Droath\Edenai\Exceptions\ApiException On HTTP errors
     * @throws \Droath\Edenai\Exceptions\NetworkException On network failures
     */
    public function deleteById(string $id): ResponseInterface
    {
        return $this->delete('/' . $id);
    }
}
