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

namespace App\EventListener;

use App\Entity\UploadedFile;
use App\Service\FileUploader;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use League\Flysystem\FilesystemException;
use Psr\Log\LoggerInterface;

/**
 * Removes the physical file of a deleted UploadedFile, once the transaction is committed.
 *
 * `preRemove` cannot do this: it fires inside EntityManager::remove(), so the file would go before the
 * DELETE is even issued and a failed flush would leave a live row pointing at nothing. `postFlush` is the
 * first event dispatched after the commit, but by then the unit of work has forgotten what it deleted —
 * hence the collect-at-onFlush, act-at-postFlush pair.
 */
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
final class UploadedFileRemover
{
    /**
     * @var list<UploadedFile>
     */
    private array $pendingRemovals = [];

    public function __construct(private readonly FileUploader $fileUploader, private readonly LoggerInterface $logger)
    {
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $em = $eventArgs->getObjectManager();
        $uow = $em->getUnitOfWork();

        $pendingRemovals = [];

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if (!$entity instanceof UploadedFile) {
                continue;
            }

            // The row is still there: load the proxy now, or reading the filename at postFlush would
            // lazy-load an already deleted entity and blow up with an EntityNotFoundException.
            $em->initializeObject($entity);

            $pendingRemovals[] = $entity;
        }

        // Assigned, never appended: a flush that throws between the two events never reaches postFlush,
        // so its queue must be discarded here. Appending would carry those files over to the next flush
        // and delete them even though the rollback left their rows in place.
        $this->pendingRemovals = $pendingRemovals;
    }

    public function postFlush(PostFlushEventArgs $eventArgs): void
    {
        // Drain before acting: postFlush also fires on flushes that deleted nothing, and a queue left
        // behind would remove the same files again on every one of them.
        $pendingRemovals = $this->pendingRemovals;
        $this->pendingRemovals = [];

        foreach ($pendingRemovals as $uploadedFile) {
            try {
                $this->fileUploader->remove($uploadedFile);
            } catch (FilesystemException $e) {
                $this->logger->error('Unable to remove the uploaded file.', [
                    'filename' => $uploadedFile->getFilename(),
                    'exception' => $e,
                ]);
            }
        }
    }
}
