<?php

declare(strict_types=1);

namespace OCA\PhotoFrame\Db;

use DateTime;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Security\ISecureRandom;

class FrameMapper extends QBMapper
{
  private ISecureRandom $random;

  public function __construct(IDBConnection $db, ISecureRandom $random)
  {
    parent::__construct($db, 'photoframe_frames', Entry::class);
    $this->random = $random;
  }

  /**
   * @param string $shareToken
   * @return Entry
   */
  public function getByShareToken(string $shareToken): ?Entry
  {
    $qb = $this->db->getQueryBuilder();

    $qb->select('*')
      ->from($this->getTableName())
      ->where(
        $qb->expr()->eq('share_token', $qb->createNamedParameter($shareToken, IQueryBuilder::PARAM_STR))
      );

    return $this->findEntity($qb);
  }

  public function createFrame(string $name, string $selectionMethod, string $entryLifetime, string $startDayAt, string $endDayAt): Entry
  {
    $entry = new Frame();
    $entry->setName($name);
    $entry->setSelectionMethod($selectionMethod);
    $entry->setEntryLifetime($entryLifetime);
    $entry->setStartDayAt($startDayAt);
    $entry->setEndDayAt($endDayAt);
    $entry->setShareToken($this->random->generate(64, ISecureRandom::CHAR_ALPHANUMERIC));

    $timestamp = new DateTime();
    $entry->setCreatedAt($timestamp);

    return $this->insert($entry);
  }
}
