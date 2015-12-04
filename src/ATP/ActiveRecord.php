<?php

namespace ATP;

class ActiveRecord
{
	//ActiveRecord static members
	private static $_adapter = null;
	private static $_databaseDef = null;
	private static $_classDefs = array();

	private $_data = array();
	private $_tableNamespace = null;
	
	public function __construct()
	{
		$this->clear();
	}
	
	public static function setAdapter($adapter)
	{
		self::$_adapter = $adapter;
	}
	
	public static function getAdapter()
	{
		return self::$_adapter;
	}
	
	public static function init()
	{
		//Load database definitions
		if(is_null(self::$_databaseDef)) self::_loadDatabaseDefinition();
		
		$obj = new static();
		
		//Set default class name
		$classNameFull = "\\" . get_class($obj);
		$classSplit = explode("\\",$classNameFull);
		$module = $classSplit[1];
		$class=end($classSplit);
		$table=\ATP\Inflector::underscore(\ATP\Inflector::pluralize($class));
		
		self::$_classDefs[$classNameFull] = array();
		self::$_classDefs[$classNameFull]['children'] = array();
		self::$_classDefs[$classNameFull]['owners'] = array();
		self::$_classDefs[$classNameFull]['defaultOrder'] = "id ASC";

		//Setup child relations
		self::$_classDefs[$classNameFull]['relations'] = $obj->_relations();
		
		//Setup class
		$obj->setTableNamespace(strtolower($module));
		$obj->_setup();
		
		//Determine final table name
		$tableFull = (is_null($obj->getTableNamespace()) ? $table : $obj->getTableNamespace() . "_" . $table);
		self::$_databaseDef['tables'][$tableFull]['class'] = $classNameFull;
		self::$_databaseDef['classes'][$classNameFull] = array();
		self::$_databaseDef['classes'][$classNameFull]['table'] = $tableFull;
		self::$_databaseDef['classes'][$classNameFull]['namespace'] = $obj->getTableNamespace();
		
		//Copy definition to class
		self::$_classDefs[$classNameFull]['namespace'] = $obj->getTableNamespace();
		self::$_classDefs[$classNameFull]['table'] = $tableFull;
		foreach(self::$_databaseDef['tables'][$tableFull] as $key => $data)
		{
			self::$_classDefs[$classNameFull][$key] = $data;
		}
	}
	
	protected function _relations()
	{
		return array();
	}
	
	protected function _setup()
	{
	}
	
	protected function setTableNamespace($ns)
	{
		$this->_tableNamespace = $ns;
		return $this;
	}
	
	public function getTableNamespace()
	{
		return $this->_tableNamespace;
	}
	
	protected function setTable($table)
	{
		static::$_table = $table;
		return $this;
	}
	
	public function isOrderedBy($order)
	{
		$classNameFull = "\\" . get_class($this);
		self::$_classDefs[$classNameFull]['defaultOrder'] = $order;
	}
	
	public function getDefinition()
	{
		$classNameFull = "\\" . get_class($this);
		return isset(self::$_classDefs[$classNameFull]) ? self::$_classDefs[$classNameFull] : null;
	}
	
	public function loadMultiple($params = array())
	{
		//Get definition
		$def = $this->getDefinition();
		
		//Construct query
		$sql = "SELECT * from {$def['table']}";		
		if(!empty($params['joins'])) $sql .= " " . implode(" ", $params['joins']);
		if(!empty($params['where'])) $sql .= " WHERE " . (is_array($params['where']) ? implode(" AND ", $params['where']) : $params['where']);
		$sql .= " ORDER BY " . (empty($params['orderBy']) ? $def['defaultOrder'] : $params['orderBy']);
		if(!empty($params['limit'])) $sql .= " LIMIT {$params['limit']}";

		//Get the records
		$results = $this->getAdapter()->query($sql, isset($params['data']) ? $params['data'] : array());
		
		if(isset($params['useArrays']) && $params['useArrays'])
		{
			$rows = $results;
		}
		else
		{
			$rows = new \ATP\ActiveRecord\ModelList($def['table']);
			
			foreach($results as $row)
			{
				$obj = new static();
				$obj->loadFromArray($row);
				$rows[] = $obj;
			}
		}
		
		return $rows;		
	}
	
	public function getCount($params = array())
	{
		$def = $this->getDefinition();
		
		$sql = "SELECT count(*) count from {$def['table']}";
		if(!empty($params['where'])) $sql .= " WHERE " . (is_array($params['where']) ? implode(" AND ", $params['where']) : $params['where']);
		if(!empty($params['limit'])) $sql .= " LIMIT {$params['limit']}";
		
		$results = $this->getAdapter()->query($sql, isset($params['data']) ? $params['data'] : array());
		return $results->current()->count;
	}
	
	public function save()
	{
		$def = $this->getDefinition();
		$db = $this->getAdapter();
		
		$filesToCopy = array();
		
		$columns = array();
		$placeHolders = array();
		$updates = array();
		$values = array();
		
		//Add fields
		foreach(array_keys($def['columns']) as $column)
		{
			if($column == 'id') continue;
			
			$setField = true;
			
			//Check for owner fields
			if($this->isOwnerField($column))
			{
				$ownerName = substr($column, 0, -3);
				if(isset($this->_data[$ownerName])) $this->$column = $this->_data[$ownerName]->id;
			}
			
			$value = $this->$column;
			
			//Run preSave functions
			$columnCamelized = \ATP\Inflector::camelize($column);
			$func = "preSave{$columnCamelized}";
			if(method_exists($this, $func)) $value = $this->$func($value);

			//Process file fields
			if(is_array($value))
			{
				if($value['error'] != 4)
				{
					$filesToCopy[$column] = $value;
					$file = new \ATP\ActiveRecord\File();
					$file->setFrom($value);
					$value = $file;
					$this->$column = $file;
				}
				else
				{
					//Don't update the file field if there was an error or no new file was updated
					$setField = false;
				}
			}
			
			//Serialize file fields
			if(is_object($value))
			{
				$value = $value->toJson();
			}
			
			//Add the field to the query
			if($setField)
			{
				if($this->isOwnerField($column) && empty($value))
				{
					$value = null;
				}
			
				$columns[] = "`$column`";
				$values[] = $value;			
				$placeHolders[] = "?";
				$updates[] = "`{$column}`=?";
			}
		}
		
		if(!$this->id)
		{
			//Insert new object if id is not set
			$sql  = "INSERT INTO {$def['table']} ";
			$sql .= "(" . implode(", ", $columns) . ") values (";
			$sql .= implode(", ", $placeHolders);
			$sql .= ") ON DUPLICATE KEY UPDATE ";
			$sql .= implode(", ", $updates);
			$values = array_merge($values, $values);
		}
		else
		{
			//Otherwise, update existing object
			$sql  = "UPDATE {$def['table']} SET ";
			$sql .= implode(", ", $updates);
			$sql .= " WHERE id=" . $this->id;
			$sql .= " LIMIT 1";
		}
		
		//Save object
		$stmt = $db->query($sql, $values);
		
		//Update id if needed
		$id = $this->id;
		if(empty($id))
		{
			$this->id = $db->getDriver()->getLastGeneratedValue();
		}
		
		//Process files
		foreach($filesToCopy as $name => $data)
		{
			$path = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
			$path .= "/uploads";
			$path .= "/{$def['table']}/{$this->id}/";
			if(!is_dir($path)) mkdir($path, 0755, true);
			$path .= $data['name'];
			if(!move_uploaded_file($data['tmp_name'], $path)) die();
		}
		
		$this->postSave();
	}
	
	public function postSave()
	{
	}
	
	public function delete()
	{
		$def = $this->getDefinition();
		$db = $this->getAdapter();
		
		$sql = "DELETE FROM {$def['table']} WHERE id={$this->id} LIMIT 1";
		$db->query($sql, array());
	}
	
	public function clear()
	{
		$this->_data = array();
		
		$def = $this->getDefinition();
		if(is_null($def)) return;
		foreach(array_keys($def['columns']) as $column) $this->_data[$column] = null;
	}
	
	public function loadFromArray($row, $runPostLoadProcessing = true)
	{
		foreach($row as $column => $value)
		{
			$this->_data[$column] = $runPostLoadProcessing ? $this->_postLoadValue($column, $value) : $value;
		}
	}
	
	public function getRawData()
	{
		return $this->_data;
	}
	
	public function setFrom($obj)
	{
		$data = is_array($obj) ? $obj : $obj->getRawData();
		foreach($data as $name => $value)
		{
			$func = "filter" . Inflector::camelize($name);
			$this->_data[$name] = method_exists($this, $func) ? $this->$func($value) : $value;
		}
		return $this;
	}
	
	public function isAFile($column)
	{
		return strpos($column, "_file") !== false;
	}
	
	public function filePath($column)
	{
		$def = $this->getDefinition();
		return "/uploads/{$def['table']}/{$this->id}/{$this->$column->name}";
	}
	
	public function identity()
	{
		return $this->id;
	}
	
	public function __toString()
	{
		return $this->displayName() . "";
	}
	
	public function displayName()
	{
		return $this->id;
	}
	
	public function ownerFields()
	{
		$def = $this->getDefinition();
		return $def['owners'];
	}
	
	public function ownerTable($column)
	{
		$def = $this->getDefinition();
		return $def['owners'][$column];
	}
	
	public function isOwnerField($column)
	{
		$owners = $this->ownerFields();
		return array_key_exists($column, $owners);
	}
	
	public function childrenFields()
	{
		$def = $this->getDefinition();
		return array_keys($def['children']);		
	}
	
	public function relatedObjectFields()
	{
		//TODO
		return array();
	}
	
	public function dataColumns()
	{
		$def = $this->getDefinition();
		return array_diff(
			array_keys($def['columns']),
			array_keys($def['owners']),
			array('id')
		);
	}

    public function __isset($column)
    {
        $column = Inflector::underscore($column);
        return isset($this->_data[$column]);
    }

	public function &__get($column)
	{
		$def = $this->getDefinition();
	
		//Return the column value if it exists
		$column = Inflector::underscore($column);
		if(array_key_exists($column, $this->_data)) return $this->_data[$column];
	
		//Handler owner object fields
		$ownerIdField = "{$column}_id";
		if(array_key_exists($ownerIdField, $this->_data))
		{
			//Load the owner object
			$ownerTable = $def['owners'][$ownerIdField];
			$ownerClass = self::$_databaseDef['tables'][$ownerTable]['class'];
			$this->_data[$column] = new $ownerClass();
			if(!empty($this->_data[$ownerIdField]))
			{
				$this->_data[$column]->loadById($this->_data[$ownerIdField]);
			}
			return $this->_data[$column];
		}
	
		//Handle related objects
		if(array_key_exists($column, $def['relations']))
		{
			$relation = $def['relations'][$column];
			$objClass = $relation['class'];
			$obj = new $objClass();
			
			$relationTable = $relation['via'];
			$joinTableInfo = array_flip(self::$_databaseDef['relations'][$relationTable]);
			
			$objDef = $obj->getDefinition();
			$objTable = $objDef['table'];
			
			$thisColumn = $joinTableInfo[$def['table']];
			$otherColumn = $joinTableInfo[$objTable];
		
			$objects = $obj->loadMultiple(array(
				'where' => "{$relationTable}.{$thisColumn}=?",
				'joins' => array(
					"LEFT JOIN {$relationTable} ON {$objTable}.id={$relationTable}.{$otherColumn}"
				),
				'data' => array($this->id)
			));
			return $objects;
		}
	
		throw new \ATP\ActiveRecord\Exception("Unknown column {$column} in model " . get_class($this));
	}
	
	public function __set($column, $value)
	{
		$column = \ATP\Inflector::underscore($column);
		if(!array_key_exists($column, $this->_data))
		{
			$ownerIdColumn = "{$column}_id";
			if(!array_key_exists($ownerIdColumn, $this->_data))
			{
				throw new \ATP\ActiveRecord\Exception("Unknown column {$column} in model " . get_class($this));
			}
		}

		$this->_data[$column] = $value;
	}
	
	public function __call($func, $params)
	{
		if(strpos($func, "loadBy") === 0) return $this->_loadBy($func, $params);
		if(strpos($func, "get") === 0 && strpos($func, "By") !== false) return $this->_getChildren($func, $params);
		
		throw new \ATP\ActiveRecord\Exception("Unknown function {$func} in " . get_class($this));
	}
	
	// -- Internal functions -- //
	
	private static function _loadDatabaseDefinition()
	{
		$db = self::getAdapter();
	
		//Get table columns
		$dbData = array();
		$tables = $db->query("SHOW TABLES")->execute();
		foreach($tables as $tableListRow)
		{
			$table = current($tableListRow);
			$columns = $db->query("DESCRIBE {$table}")->execute();
			$columnData = array();
			foreach($columns as $columnListRow)
			{
				$columnData[$columnListRow['Field']] = $columnListRow['Type'];
			}
			$dbData['tables'][$table]['columns'] = $columnData;
		}
		
		//Get relationships
		$schema = $db->getDriver()->getConnection()->getCurrentSchema();
		$relations = $db->query("
			SELECT table_name, column_name, referenced_table_name, referenced_column_name
			FROM information_schema.key_column_usage
			WHERE table_schema='{$schema}' AND referenced_table_schema IS NOT NULL;
		")->execute();
		$relationData = array();
		foreach($relations as $relation)
		{
			$table = $relation['table_name'];
			$column = $relation['column_name'];
			$otherTable = $relation['referenced_table_name'];
			
			$dbData['tables'][$table]['owners'][$column] = $otherTable;
			$dbData['tables'][$otherTable]['children'][$table][] = $column;

			$table = $relation['table_name'];
			unset($relation['table_name']);
			$relationData[$table][$relation['column_name']] = $relation['referenced_table_name'];
			
		}
		$dbData['relations'] = $relationData;
		
		self::$_databaseDef = $dbData;
	}
	
	private function _postLoadValue($column, $value)
	{
		if($this->isAFile($column))
		{
			$file = new \ATP\ActiveRecord\File();
			$file->setFrom($value);
			$value = $file;
		}
		
		$column = ucfirst(Inflector::camelize($column));
		$func = "postLoad{$column}";
		return method_exists($this, $func)
			? $this->$func($value)
			: $value;
	}
	
	private function _loadBy($func, $params)
	{
		$success = true;
	
		$column = Inflector::underscore(str_replace("loadBy", "", $func));
		$queryParams = array(
			'where' => "{$column} = ?",
			'data' => array($params[0]),
			'useArrays' => true
		);
		
		$rows = $this->loadMultiple($queryParams);
		reset($rows);
		if(count($rows) == 0)
		{
			$success = false;
		
			$def = $this->getDefinition();
			$row = array();
			foreach(array_keys($def['columns']) as $column)
			{
				$row[$column] = null;
			}
		}
		else
		{
			//Necessary to get the first row as an array
			foreach($rows as $row)
			{
				break;
			}
		}
		$this->loadFromArray($row);
		return $success;
	}
	
	private function _getChildren($func, $params)
	{
		$parts = explode("By", $func);
		
		if(count($parts) > 2) throw new \ATP\ActiveRecord\Exception("Ambiguous children call {$func}");
		
		$table = \ATP\Inflector::underscore(substr($parts[0], 3));
		$field = \ATP\Inflector::underscore($parts[1]) . "_id";
		$class = self::$_databaseDef['tables'][$table]['class'];
		
		$obj = new $class();
		$children = $obj->loadMultiple(array(
			'where' => "{$field} = ?",
			'data' => array($this->id)
		));
		
		return $children;
	}
}
