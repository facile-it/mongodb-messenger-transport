<?php

namespace Facile\MongoDbMessenger\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ORM;
use DateTime;

/**
 * Currency
 *
 * @ORM\Document()
 * ORM\Indexes({
 *   ORM\Index(keys={"queueName"="asc"}),
 *   ORM\Index(keys={"deliveredAt"="asc"})
 *   ORM\Index(keys={"availableAt"="asc"})
 * })
 */
class QueueDocument
{
    /**
     * @var string
     *
     * @ORM\Id(strategy="UUID", type="string")
     */
    public string $id;

    /**
     * @var string
     *
     * @ORM\Field(name="queueName", type="string", nullable=false)
     */
    public string $queueName;

    /**
     * @var string
     *
     * @ORM\Field(name="body", type="string", nullable=false)
     */
    public string $body;

    /**
     * @var string
     *
     * @ORM\Field(name="firstErrorMessage", type="string", nullable=false)
     */
    public string $firstErrorMessage;

    /**
     * @var DateTime|null
     *
     * @ORM\Field(name="deliveredAt", type="date", nullable=true)
     */
    public ?DateTime $deliveredAt;

    /**
     * @var DateTime|null
     *
     * @ORM\Field(name="availableAt", type="date", nullable=true)
     */
    public ?DateTime $availableAt;

    /**
     * @var DateTime|null
     *
     * @ORM\Field(name="createdAt", type="date", nullable=true)
     */
    public ?DateTime $createdAt;

    /**
     * @var DateTime|null
     *
     * @ORM\Field(name="firstErrorAt", type="date", nullable=true)
     */
    public ?DateTime $firstErrorAt;

    /**
     * @var string|null
     *
     * @ORM\Field(name="deliveredTo", type="string", nullable=true)
     */
    public ?string $deliveredTo;

    /**
     * @var int|null
     *
     * @ORM\Field(name="retryCount", type="int", nullable=true)
     */
    public int $retryCount = 0;

    public function getInsertedId(): string
    {
        return $this->id;
    }

    public function fromArray(array $params)
    {
        $className = (static::class);
        $obj = $this;

        foreach($params as $key => $data) {
            if (property_exists($obj, $key)) {
                $obj->$key = $data;
            }
        }

        return $obj;
    }


    public function toArray(array $list = null): array
    {
        $list = get_object_vars($this);
        foreach ($list as $key => $item) {
            $nameMethod = 'get' . ucfirst($key);
            if (method_exists($this, $nameMethod)) {
                $list[$key] = $this->$nameMethod();
                continue;
            }

            $nameMethod = 'is' . ucfirst($key);
            if (method_exists($this, $nameMethod)) {
                $list[$key] = $this->$nameMethod();
                continue;
            }
        }

        return $list;
    }

}