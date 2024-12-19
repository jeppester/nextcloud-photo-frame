<?php

declare(strict_types=1);

namespace OCA\PhotoFrame\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method int getName()
 * @method string getShareToken()
 * @method string getSelectionMethod()
 * @method string getEntryLifetime()
 * @method string getStartDayAt()
 * @method string getEndDayAt()
 * @method DateTime getCreatedAt()
 *
 * @method void setName(string $name)
 * @method void setShareToken(string $shareToken)
 * @method void setSelectionMethod(string $selectionMethod)
 * @method void setEntryLifetime(string $entryLifetime)
 * @method void setStartDayAt(string $startDayAt)
 * @method void setEndDayAt(string $endDayAt)
 * @method void setCreatedAt(\DateTime $createdAt)
 */
class Frame extends Entity
{
  /** @var string */
  protected $name;

  /** @var string */
  protected $shareToken;

  /** @var string */
  protected $selectionMethod;
  /** @var string */
  protected $entryLifetime;
  /** @var string */
  protected $startDayAt;
  /** @var string */
  protected $endDayAt;
  /** @var \DateTime */
  protected $createdAt;

  public function __construct()
  {
    $this->addType('name', 'integer');
    $this->addType('share_token', 'string');
    $this->addType('selection_method', 'string');
    $this->addType('entry_lifetime', 'string');
    $this->addType('start_day_at', 'string');
    $this->addType('end_day_at', 'string');
    $this->addType('created_at', 'datetime');
  }
}
