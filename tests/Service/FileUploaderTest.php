<?php

declare(strict_types=1);

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

namespace App\Tests\Service;

use App\Entity\UploadedFile;
use App\Service\FileUploader;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\HttpFoundation\File\File;

class FileUploaderTest extends TestCase
{
    private const string DOCUMENT = __DIR__.'/../../src/DataFixtures/Files/document.pdf';

    private string $baseDir;

    private string $privateDir;

    private string $publicDir;

    private Filesystem $privateFilesystem;

    private FileUploader $fileUploader;

    public function testUploadStoresThePrivateFileInATwoLevelHierarchy(): void
    {
        $uploadedFile = $this->fileUploader->upload(new File(self::DOCUMENT), FileUploader::PRIVATE);
        $filename = $uploadedFile->getFilename();

        $this->assertMatchesRegularExpression('#^[0-9a-f]{2}/[0-9a-f]{2}/[0-9a-f-]{36}\.pdf$#', $filename);
        $this->assertSame('document.pdf', $uploadedFile->getOriginalFilename());
        $this->assertSame('application/pdf', $uploadedFile->getMimeType());
        $this->assertFileExists("{$this->privateDir}/{$filename}");
        $this->assertFileDoesNotExist("{$this->publicDir}/{$filename}");
    }

    public function testRemoveDeletesTheFileAndPrunesItsEmptyDirectories(): void
    {
        $uploadedFile = $this->givenAStoredFile('ab/cd/one.pdf');

        $this->fileUploader->remove($uploadedFile);

        $this->assertFileDoesNotExist("{$this->privateDir}/ab/cd/one.pdf");
        $this->assertDirectoryDoesNotExist("{$this->privateDir}/ab/cd");
        $this->assertDirectoryDoesNotExist("{$this->privateDir}/ab");
    }

    public function testRemoveKeepsADirectoryThatStillHoldsASibling(): void
    {
        $uploadedFile = $this->givenAStoredFile('ab/cd/one.pdf');
        $this->givenAStoredFile('ab/cd/two.pdf');

        $this->fileUploader->remove($uploadedFile);

        $this->assertFileDoesNotExist("{$this->privateDir}/ab/cd/one.pdf");
        $this->assertFileExists("{$this->privateDir}/ab/cd/two.pdf");
    }

    public function testRemovePrunesOnlyUpToTheFirstNonEmptyDirectory(): void
    {
        $uploadedFile = $this->givenAStoredFile('ab/cd/one.pdf');
        $this->givenAStoredFile('ab/ef/other.pdf');

        $this->fileUploader->remove($uploadedFile);

        $this->assertDirectoryDoesNotExist("{$this->privateDir}/ab/cd");
        $this->assertFileExists("{$this->privateDir}/ab/ef/other.pdf");
    }

    public function testRemoveAnAlreadyMissingFileIsANoOp(): void
    {
        $uploadedFile = (new UploadedFile())
            ->setFilename('ab/cd/gone.pdf')
            ->setOriginalFilename('document.pdf')
            ->setVisibility(FileUploader::PRIVATE)
        ;

        $this->fileUploader->remove($uploadedFile);

        $this->assertFileDoesNotExist("{$this->privateDir}/ab/cd/gone.pdf");
    }

    #[\Override]
    protected function setUp(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $this->baseDir = sys_get_temp_dir()."/avirontours-file-uploader-{$suffix}";
        $this->privateDir = "{$this->baseDir}/private";
        $this->publicDir = "{$this->baseDir}/public";

        $this->privateFilesystem = new Filesystem(new LocalFilesystemAdapter($this->privateDir));
        $this->fileUploader = new FileUploader(
            new Filesystem(new LocalFilesystemAdapter($this->publicDir)),
            $this->privateFilesystem,
            'https://example.org/uploads',
            $this->privateDir,
            $this->publicDir,
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        (new SymfonyFilesystem())->remove($this->baseDir);
    }

    private function givenAStoredFile(string $filename): UploadedFile
    {
        $this->privateFilesystem->write($filename, 'content');

        return (new UploadedFile())
            ->setFilename($filename)
            ->setOriginalFilename('document.pdf')
            ->setVisibility(FileUploader::PRIVATE)
        ;
    }
}
