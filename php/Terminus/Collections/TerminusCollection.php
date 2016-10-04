<?php

namespace Terminus\Collections;

use Terminus\Exceptions\TerminusException;
use Terminus\Request;

abstract class TerminusCollection
{
  /**
   * @var array
   */
    protected $args = [];
  /**
   * @var string
   */
    protected $collected_class = 'Terminus\Models\TerminusModel';
  /**
   * @var TerminusModel[]
   */
    protected $models = [];
  /**
   * @var boolean
   */
    protected $paged = false;

  /**
   * Instantiates the collection, sets param members as properties
   *
   * @param array $options Options with which to configure this collection
   */
    public function __construct(array $options = [])
    {
        $this->request = new Request();
    }

  /**
   * Adds a model to this collection
   *
   * @param object $model_data Data to feed into attributes of new model
   * @param array  $options    Data to make properties of the new model
   * @return TerminusModel
   */
    public function add($model_data, array $options = [])
    {
        $options = array_merge(
            ['id' => $model_data->id, 'collection' => $this,],
            $options
        );
        $model = new $this->collected_class($model_data, $options);
        $this->models[$model_data->id] = $model;
        return $model;
    }

  /**
   * Retrieves all models
   * TODO: Remove automatic fetching and make fetches explicit
   *
   * @return TerminusModel[]
   */
    public function all()
    {
        $models = array_values($this->getMembers());
        return $models;
    }

  /**
   * Fetches model data from API and instantiates its model instances
   *
   * @param array $options params to pass to url request
   * @return TerminusCollection $this
   */
    public function fetch(array $options = [])
    {
        $results = $this->getCollectionData($options);

        foreach ($results as $id => $model_data) {
            if (!isset($model_data->id)) {
                $model_data->id = $id;
            }
            $this->add($model_data);
        }

        return $this;
    }

  /**
   * Retrieves the model of the given ID
   *
   * @param string $id ID of desired model instance
   * @return TerminusModel $this->models[$id]
   * @throws TerminusException
   */
    public function get($id)
    {
        $models = $this->getMembers();
        if (isset($models[$id])) {
            return $models[$id];
        }
        throw new TerminusException(
            'Could not find {model} "{id}"',
            ['model' => $this->collected_class, 'id' => $id,],
            1
        );
    }

  /**
   * List Model IDs
   *
   * @return string[] Array of all model IDs
   */
    public function ids()
    {
        $models = $this->getMembers();
        $ids    = array_keys($models);
        return $ids;
    }

  /**
   * Returns an array of data where the keys are the attribute $key and the
   *   values are the attribute $value
   *
   * @param string $key   Name of attribute to make array keys
   * @param mixed  $value Name(s) of attribute(s) to comprise array values
   * @return array Array rendered as requested
   *         $this->attribute->$key = $this->attribute->$value
   */
    public function listing($key = 'id', $value = 'name')
    {
        $members = array_combine(
            array_map(
                function ($member) use ($key) {
                    return $member->get($key);
                },
                $this->models
            ),
            array_map(
                function ($member) use ($value) {
                    if (is_scalar($value)) {
                        return $member->get($value);
                    }
                    $list = [];
                    foreach ($value as $item) {
                        $list[$item] = $member->get($item);
                    }
                    return $list;
                },
                $this->models
            )
        );
        return $members;
    }

  /**
   * Retrieves collection data from the API
   *
   * @param array $options params to pass to url request
   * @return array
   */
    protected function getCollectionData($options = [])
    {
        $args = array_merge(['options' => ['method' => 'get',],], $this->args);
        if (isset($options['fetch_args'])) {
            $args = array_merge($args, $options['fetch_args']);
        }

        if ($this->paged) {
            $results = $this->request->pagedRequest($this->url, $args);
        } else {
            $results = $this->request->request($this->url, $args);
        }

        return $results['data'];
    }

  /**
   * Returns an array of data where the keys are the attribute $key and the
   *   values are the attribute $value, filtered by the given array
   *
   * @param array        $filters Attributes to match during filtration
   *   e.g. array('category' => 'other')
   * @param string       $key     Name of attribute to make array keys
   * @param string|array $value   Name(s) of attribute to make array values
   * @return array Array rendered as requested
   *         $this->attribute->$key = $this->attribute->$value
   */
    public function getFilteredMemberList(
        array $filters,
        $key = 'id',
        $value = 'name'
    ) {
        $members     = $this->getMembers();
        $member_list = [];

        $values = $value;
        if (!is_array($values)) {
            $values = [$value,];
        }
        foreach ($members as $member) {
            $member_list[$member->get($key)] = [];
            foreach ($values as $item) {
                $member_list[$member->get($key)][$item] = $member->get($item);
            }
            if (count($member_list[$member->get($key)]) < 2) {
                $member_list[$member->get($key)] =
                array_pop($member_list[$member->get($key)]);
            }
            foreach ($filters as $attribute => $match_value) {
                if ($member->get($attribute) != $match_value) {
                    unset($member_list[$member->get($key)]);
                    break;
                }
            }
        }
        return $member_list;
    }

  /**
   * Returns an array of data where the keys are the attribute $key and the
   *   values are the attribute $value
   *
   * @param string $key   Name of attribute to make array keys
   * @param string $value Name of attribute to make array values
   * @return array Array rendered as requested
   *         $this->attribute->$key = $this->attribute->$value
   */
    public function getMemberList($key = 'id', $value = 'name')
    {
        $member_list = $this->getFilteredMemberList([], $key, $value);
        return $member_list;
    }

  /**
   * Retrieves all members of this collection
   *
   * @return TerminusModel[]
   */
    protected function getMembers()
    {
        if (empty($this->models)) {
            $this->fetch();
        }
        return $this->models;
    }
}
