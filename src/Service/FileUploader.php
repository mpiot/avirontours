<?php

/*
 * Copyright 2020 Mathieu Piot
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Service;

use App\Entity\UploadedFile;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile as HttpUploadedFile;
use Symfony\Component\Uid\Uuid;

use function Symfony\Component\String\u;

class FileUploader
{
    public const string PUBLIC = 'public';
    public const string PRIVATE = 'private';

    public function __construct(
        private readonly FilesystemOperator $uploadsFilesystem,
        private readonly FilesystemOperator $privateUploadsFilesystem,
        private readonly string $uploadsBaseUrl,
        private readonly string $uploadsPrivateDir,
        private readonly string $uploadsPublicDir,
    ) {
    }

    public function upload(File $file, string $visibility = self::PUBLIC): UploadedFile
    {
        $extension = $file->guessExtension() ?? $file->getExtension();
        $newFilename = $this->buildDirectoryHierarchy(Uuid::v4()->toRfc4122()).'.'.$extension;

        $originalFilename = $file->getFilename();
        if ($file instanceof HttpUploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        }

        $stream = fopen($file->getPathname(), 'r');
        $this->getFilesystem($visibility)->writeStream(
            $newFilename,
            $stream
        );

        if (\is_resource($stream)) {
            fclose($stream);
        }

        $uploadedFile = new UploadedFile();
        $uploadedFile
            ->setFilename($newFilename)
            ->setOriginalFilename($originalFilename)
            ->setMimeType($file->getMimeType())
            ->setVisibility($visibility)
        ;

        return $uploadedFile;
    }

    public function remove(UploadedFile $uploadedFile): void
    {
        $path = $uploadedFile->getFilename();

        $filesystem = $this->getFilesystem($uploadedFile->getVisibility());
        $filesystem->delete($path);

        // Delete empty directories
        $directories = u($path)->split('/');
        array_pop($directories);
        $numberDirectories = \count($directories);

        for ($i = 0; $i < $numberDirectories; ++$i) {
            $path = implode('/', $directories);
            if ([] !== $filesystem->listContents($path)->toArray()) {
                break;
            }

            $filesystem->deleteDirectory($path);
            array_pop($directories);
        }
    }

    public function getPublicPath(UploadedFile $uploadedFile): string
    {
        $path = $uploadedFile->getFilename();

        return $this->uploadsBaseUrl.'/'.$path;
    }

    public function getAbsolutePath(UploadedFile $uploadedFile): string
    {
        $path = $uploadedFile->getFilename();

        if (self::PRIVATE === $uploadedFile->getVisibility()) {
            return $this->uploadsPrivateDir.'/'.$path;
        }

        return $this->uploadsPublicDir.'/'.$path;
    }

    private function getFilesystem(string $visibility): FilesystemOperator
    {
        if (self::PRIVATE === $visibility) {
            return $this->privateUploadsFilesystem;
        }

        return $this->uploadsFilesystem;
    }

    private function buildDirectoryHierarchy(string $filename): string
    {
        $chunks = u($filename)
            ->slice(0, 4)
            ->chunk(2)
        ;

        return u('/')
            ->join($chunks)
            ->append('/', $filename)
            ->toString()
        ;
    }
}
