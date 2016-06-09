<?php namespace Bolt\Extension\Thirdwave\Export;

use Bolt\Storage;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;


/**
 * Class ExportQuery
 *
 * @author  G.P. Gautier <ggautier@thirdwave.nl>
 * @version 0.5.0, 2016/06/09
 */
class ExportQuery
{


    /**
     * @var Connection
     */
    protected $connection;


    /**
     * @var Storage
     */
    protected $storage;


    /**
     * @var QueryBuilder
     */
    protected $query;


    /**
     * @var array
     */
    protected $parameters = array();


    /**
     * @var string
     */
    protected $contenttype;


    /**
     * @param Connection $connection
     * @param Storage    $storage
     */
    public function __construct(Connection $connection, Storage $storage)
    {
        $this->connection = $connection;
        $this->storage    = $storage;
        $this->query      = $connection->createQueryBuilder();
    }


    /**
     * @return array
     */
    public function results()
    {
        return $this->connection->executeQuery($this->query, $this->parameters)->fetchAll();
    }


    /**
     * @return int
     */
    public function count()
    {
        $this->query->select('COUNT(id) AS rows');

        $count = $this->connection->executeQuery($this->query, $this->parameters)->fetch();

        return $count['rows'];
    }


    /**
     * @param array $profile
     * @return $this
     */
    public function profile(array $profile)
    {
        return $this
          ->contenttype($profile['contenttype'])
          ->fields($profile['fields'])
          ->filters($profile['filters'] ?: array())
          ->relations($profile['relations'] ?: array())
          ->sorting($profile['sorting'] ?: array());
    }


    /**
     * @param string $contenttype
     * @return $this
     */
    public function contenttype($contenttype)
    {
        $this->contenttype = $contenttype;

        $this->query->from($this->storage->getTablename($contenttype), $contenttype);

        return $this;
    }


    /**
     * @param array $fields
     * @return $this
     */
    public function fields(array $fields)
    {
        $this->query->select($fields);

        return $this;
    }


    /**
     * @param array $filters
     * @return $this
     */
    public function filters(array $filters)
    {
        if (empty($filters)) {
            return $this;
        }

        $where = $this->query->expr()->andX();

        for ($i = 0; $i < count($filters['fields']); $i++) {
            $operation = $filters['operators'][$i];

            switch ($operation) {
                case 'isNull':
                case 'isNotNull':
                    $where->add($this->query->expr()->$operation($filters['fields'][$i]));
                    break;
                default:
                    $where->add($this->query->expr()->$operation($filters['fields'][$i], '?'));

                    $this->parameters[] = $filters['values'][$i];

                    break;
            }
        }

        $this->query->where($where);

        return $this;
    }


    /**
     * @param array $relations
     * @return $this
     */
    public function relations(array $relations)
    {
        foreach ($relations as $relation => $keys) {
            $where = $this->query->expr()->andX();
            $where->add($this->query->expr()->eq('relations.from_contenttype',
              $this->query->expr()->literal($this->contenttype)));
            $where->add($this->query->expr()->eq('relations.from_id', $this->contenttype . ".id"));
            $where->add($this->query->expr()->eq('relations.to_contenttype', $this->query->expr()->literal($relation)));
            $where->add($this->query->expr()->in('relations.to_id', $keys));

            $this->query->innerJoin(
              $this->contenttype,
              $this->storage->getTablename('relations'),
              'relations',
              $where
            );
        }

        return $this;
    }


    /**
     * @param array $sorting
     * @return $this
     */
    public function sorting(array $sorting)
    {
        for ($i = 0; $i < count($sorting['fields']); $i++) {
            $this->query->addOrderBy(
              $sorting['fields'][$i],
              strtoupper($sorting['directions'][$i])
            );
        }

        return $this;
    }
}