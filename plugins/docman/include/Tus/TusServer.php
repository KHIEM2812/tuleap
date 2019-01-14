<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Docman\Tus;

use Http\Message\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class TusServer implements RequestHandlerInterface
{
    private const TUS_VERSION = '1.0.0';

    /**
     * @var ResponseFactory
     */
    private $response_factory;
    /**
     * @var TusDataStore
     */
    private $data_store;

    public function __construct(
        ResponseFactory $response_factory,
        TusDataStore $data_store
    ) {
        $this->response_factory = $response_factory;
        $this->data_store       = $data_store;
    }

    public function handle(\Psr\Http\Message\ServerRequestInterface $request) : ResponseInterface
    {
        $response = null;
        try {
            switch ($request->getMethod()) {
                case 'OPTIONS':
                    $response = $this->processOptions();
                    break;
                case 'HEAD':
                    $this->checkProtocolVersionIsSupported($request);
                    $file = $this->data_store->getFileInformationProvider()->getFileInformation($request);
                    if ($file !== null) {
                        $response = $this->processHead($file);
                    }
                    break;
                case 'PATCH':
                    $this->checkProtocolVersionIsSupported($request);
                    $file = $this->data_store->getFileInformationProvider()->getFileInformation($request);
                    if ($file !== null) {
                        $response = $this->processPatch($request, $file);
                    }
                    break;
                default:
                    return $this->response_factory->createResponse(405);
            }
        } catch (TusServerIncompatibleVersionException $exception) {
            $response = $this->response_factory->createResponse(
                $exception->getCode(),
                $exception->getMessage(),
                ['Tus-Version' => self::TUS_VERSION]
            );
        } catch (TusServerException $exception) {
            $response = $this->response_factory->createResponse(
                $exception->getCode(),
                $exception->getMessage(),
                ['Tus-Resumable' => self::TUS_VERSION]
            );
        }

        if ($response === null) {
            $response = $this->response_factory->createResponse(404);
        }

        if ($request->getMethod() === 'OPTIONS') {
            return $response;
        }
        return $response->withHeader('Tus-Resumable', self::TUS_VERSION);
    }

    private function processOptions() : ResponseInterface
    {
        return $this->response_factory->createResponse(204)
            ->withHeader('Tus-Version', self::TUS_VERSION);
    }

    private function processHead(TusFileInformation $file_information) : ResponseInterface
    {
        return $this->response_factory->createResponse(204)
            ->withHeader('Upload-Length', $file_information->getLength())
            ->withHeader('Upload-Offset', $file_information->getOffset())
            ->withHeader('Cache-Control', 'no-cache');
    }

    private function processPatch(\Psr\Http\Message\ServerRequestInterface $request, TusFileInformation $file_information) : ResponseInterface
    {
        $content_type_header         = $request->getHeaderLine('Content-Type');
        $found_expected_content_type = false;
        foreach (explode(';', $content_type_header) as $content_type_part) {
            if (trim($content_type_part) === 'application/offset+octet-stream') {
                $found_expected_content_type = true;
                break;
            }
        }
        if (! $found_expected_content_type) {
            return $this->response_factory->createResponse(415);
        }

        if (! $request->hasHeader('Upload-Offset')) {
            return $this->response_factory->createResponse(400);
        }

        if ((int) $request->getHeaderLine('Upload-Offset') !== $file_information->getOffset()) {
            return $this->response_factory->createResponse(409);
        }

        $copied_size = $this->data_store->getWriter()->writeChunk(
            $file_information,
            $file_information->getOffset(),
            $request->getBody()->detach()
        );

        $finisher = $this->data_store->getFinisher();
        if ($finisher !== null) {
            $finisher->finishUpload($file_information);
        }

        return $this->response_factory->createResponse(204)
            ->withHeader('Upload-Offset', $file_information->getOffset() + $copied_size);
    }

    /**
     * @throws TusServerIncompatibleVersionException
     */
    private function checkProtocolVersionIsSupported(\Psr\Http\Message\RequestInterface $request) : void
    {
        if ($request->getHeaderLine('Tus-Resumable') !== self::TUS_VERSION) {
            throw new TusServerIncompatibleVersionException(self::TUS_VERSION);
        }
    }
}
