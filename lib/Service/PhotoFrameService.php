<?php

declare(strict_types=1);

namespace OCA\PhotoFrame\Service;

use OCA\PhotoFrame\Db\Entry;
use OCA\PhotoFrame\Db\EntryMapper;
use OCA\PhotoFrame\Db\Frame;
use OCA\PhotoFrame\Db\FrameFile;
use OCA\PhotoFrame\Db\FrameMapper;
use OCP\Files\IRootFolder;
use OCP\Files\Node;

/**
 * @psalm-suppress UnusedClass
 */
class PhotoFrameService
{
  private EntryMapper $entryMapper;
  private IRootFolder $rootFolder;
  private Frame $frame;
  private string $timezone;

  public function __construct(
    EntryMapper $entryMapper,
    IRootFolder $rootFolder,
    Frame $frame,
  ) {
    $this->entryMapper = $entryMapper;
    $this->rootFolder = $rootFolder;
    $this->frame = $frame;
  }

  public function getCurrentFrameFile(): ?FrameFile
  {
    $latestFrameFile = null;
    $latestEntry = $this->entryMapper->getLatestEntry($this->frame->getId());

    if ($latestEntry && !$this->entryExpired($latestEntry)) {
      $latestFrameFile = $this->getFrameFileById($latestEntry->getFileId());
    }

    if ($latestFrameFile) {
      $latestFrameFile->setExpiresAt($this->getEntryExpiry($latestEntry));
      return $latestFrameFile;
    }

    $pickedFrameFile = $this->pickNewFrameFile();
    if (!$pickedFrameFile) {
      return null;
    }

    $entry = $this->entryMapper->createEntry($pickedFrameFile->getFileId(), $this->frame->getId());
    $pickedFrameFile->setExpiresAt($this->getEntryExpiry($entry));
    return $pickedFrameFile;
  }

  private function entryExpired(Entry $entry): bool
  {
    return $this->getEntryExpiry($entry) <= new \DateTime();
  }

  public function getEntryExpiry(Entry $entry)
  {
    $createdAt = (clone $entry->getCreatedAt())->setTimezone($this->frame->getTimezone());
    // return (clone $createdAt)->modify('+1 seconds');

    switch ($this->frame->getEntryLifetime()) {
      case FrameMapper::ENTRY_LIFETIME_ONE_DAY:
        $expiry = (clone $createdAt)->modify("24:00");
        return $expiry;

      case FrameMapper::ENTRY_LIFETIME_ONE_HOUR:
        $startTime = (clone $createdAt)->modify($this->frame->getStartDayAt());
        $endTime = (clone $startTime)->modify($this->frame->getEndDayAt());

        // Starting from the first rotation time, move forward until we are past the entry's creation time
        $rotationTime = (clone $startTime)->modify("+1 hour");
        while ($rotationTime < $createdAt) {
          $rotationTime->modify('+1 hour');
        }

        // If we are past the end of the day, show the image until next day
        if ($rotationTime >= $endTime) {
          $rotationTime->modify('24:00');
        }

        return $rotationTime;

      case FrameMapper::ENTRY_LIFETIME_1_2_DAY:
      case FrameMapper::ENTRY_LIFETIME_1_3_DAY:
      case FrameMapper::ENTRY_LIFETIME_1_4_DAY:
        $numPhotos = [
          FrameMapper::ENTRY_LIFETIME_1_2_DAY => 2,
          FrameMapper::ENTRY_LIFETIME_1_3_DAY => 3,
          FrameMapper::ENTRY_LIFETIME_1_4_DAY => 4,
        ][$this->frame->getEntryLifetime()];


        $startTime = (clone $createdAt)->modify($this->frame->getStartDayAt());
        $endTime = (clone $startTime)->modify($this->frame->getEndDayAt());
        $photoTTL = ceil(num: ($endTime->getTimestamp() - $startTime->getTimestamp()) / $numPhotos);

        // Starting from the first rotation time, move forward until we are past the entry's creation time
        $rotationTime = (clone $startTime)->modify("+$photoTTL seconds");
        while ($rotationTime < $createdAt) {
          $rotationTime->modify("+$photoTTL seconds");
        }

        // If we are past the end of the day, show the image until next day
        if ($rotationTime >= $endTime) {
          $rotationTime = (clone $startTime)->modify('24:00');
        }
        return $rotationTime;

      default:
        return -INF;
    }
  }

  private function pickNewFrameFile(): ?FrameFile
  {
    $usedFileIds = $this->entryMapper->getUsedFileIds($this->frame->getId());
    $availableFrameFiles = array_filter($this->frame->getFrameFiles(), function ($frameFile) use ($usedFileIds) {
      return !in_array($frameFile->getFileId(), $usedFileIds);
    });

    if (count($availableFrameFiles) === 0) {
      $this->entryMapper->deleteFrameEntries($this->frame->getId());
      $availableFrameFiles = $this->frame->getFrameFiles();
    }

    $selectionMethod = $this->frame->getSelectionMethod();
    switch ($selectionMethod) {
      case FrameMapper::SELECTION_METHOD_LATEST:
      case FrameMapper::SELECTION_METHOD_OLDEST:
        usort($availableFrameFiles, function ($a, $b) {
          $res = $b->getAddedAtTimestamp() - $a->getAddedAtTimestamp();
          if ($res === 0) {
            $res = $b->getModifiedAtTimestamp() - $a->getModifiedAtTimestamp();
          }
          return $res;
        });

        return $selectionMethod === FrameMapper::SELECTION_METHOD_LATEST
          ? $availableFrameFiles[0]
          : $availableFrameFiles[count($availableFrameFiles) - 1];

      case FrameMapper::SELECTION_METHOD_RANDOM:
        return $availableFrameFiles[array_rand($availableFrameFiles)];
    }
  }

  private function getFrameFileById(int $fileId): ?FrameFile
  {
    foreach ($this->frame->getFrameFiles() as $frameFile) {
      if ($frameFile->getFileId() === $fileId) {
        return $frameFile;
      }
    }
    return null;
  }

  public function getFrameFileNode(FrameFile $frameFile): Node
  {
    $nodes = $this->rootFolder
      ->getUserFolder($frameFile->getUserUid())
      ->getById($frameFile->getFileId());

    return current($nodes);
  }
}