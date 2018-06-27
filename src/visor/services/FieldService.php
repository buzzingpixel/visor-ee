<?php

namespace buzzingpixel\visor\services;

use EllisLab\ExpressionEngine\Service\Model\Facade as ModelFacade;
use EllisLab\ExpressionEngine\Service\Database\Query as QueryBuilder;
use EllisLab\ExpressionEngine\Service\Model\Collection as ModelCollection;
use EllisLab\ExpressionEngine\Model\Channel\ChannelField as ChannelFieldModel;

/**
 * Class FieldService
 */
class FieldService
{
    /** @var ModelCollection $fields */
    private $fields;

    /** @var QueryBuilder $queryBuilder */
    private $queryBuilder;

    /**
     * FieldService constructor
     * @param ModelFacade $modelFacade
     * @param QueryBuilder $queryBuilder
     */
    public function __construct(
        ModelFacade $modelFacade,
        QueryBuilder $queryBuilder
    ) {
        $this->fields = $modelFacade->get('ChannelField')->all();
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Gets field ID by name
     * @param $fieldName
     * @return int
     */
    public function getFieldIdByName($fieldName)
    {
        /** @var ChannelFieldModel $field */
        $field = $this->fields->filter('field_name', $fieldName)->first();

        return $field instanceof ChannelFieldModel ?
            ((int) $field->getProperty('field_id')) :
            0;
    }

    /**
     * Gets a field's type by name
     * @param $fieldName
     * @return int
     */
    public function getFieldTypeByName($fieldName)
    {
        /** @var ChannelFieldModel $field */
        $field = $this->fields->filter('field_name', $fieldName)->first();

        return $field instanceof ChannelFieldModel ?
            $field->getProperty('field_type') :
            null;
    }

    private $gridColIdStorage = [];

    /**
     * Gets a grid column ID
     * @param $fieldId
     * @param $colName
     * @return int
     */
    public function getGridColId($fieldId, $colName)
    {
        $storeName = $fieldId . '-' . $colName;

        if (isset($this->gridColIdStorage[$storeName])) {
            return $this->gridColIdStorage[$storeName];
        }

        $query = $this->queryBuilder->select('col_id')
            ->where('field_id', $fieldId)
            ->where('col_name', $colName)
            ->get('grid_columns')
            ->row();

        if (! isset($query->col_id)) {
            return 0;
        }

        $val = (int) $query->col_id;

        $this->gridColIdStorage[$storeName] = $val;

        return (int) $query->col_id;
    }

    /**
     * Gets grid column names
     * @param int $fieldId
     * @return array
     */
    public function getGridColumnIds($fieldId)
    {
        $query = $this->queryBuilder->select('col_id')
            ->where('field_id', $fieldId)
            ->get('grid_columns')
            ->result();

        $ids = [];

        foreach ($query as $item) {
            $ids[] = $item->col_id;
        }

        return $ids;
    }

    private $matrixColIdStorage = [];

    /**
     * Gets a Matrix column ID
     * @param $fieldId
     * @param $colName
     * @return int
     */
    public function getMatrixColId($fieldId, $colName)
    {
        $storeName = $fieldId . '-' . $colName;

        if (isset($this->matrixColIdStorage[$storeName])) {
            return $this->matrixColIdStorage[$storeName];
        }

        $query = $this->queryBuilder->select('col_id')
            ->where('field_id', $fieldId)
            ->where('col_name', $colName)
            ->get('matrix_cols')
            ->row();

        $val = (int) $query->col_id;

        $this->gridColIdStorage[$storeName] = $val;

        return (int) $query->col_id;
    }

    /**
     * Gets grid column names
     * @param int $fieldId
     * @return array
     */
    public function getMatrixColumnIds($fieldId)
    {
        $query = $this->queryBuilder->select('col_id')
            ->where('field_id', $fieldId)
            ->get('matrix_cols')
            ->result();

        $ids = [];

        foreach ($query as $item) {
            $ids[] = $item->col_id;
        }

        return $ids;
    }
}
