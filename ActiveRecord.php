<?php

namespace ATP;

class ActiveRecord
{
	protected static $_definitions = array();
	
	private static $_adapter = null;
	
	private $_data = array();
	
	public function __construct($id = null)
	{
		if(!is_null($id)) $this->load($id);
	}
	
	public static function setAdapter($adapter)
	{
		self::$_adapter = $adapter;
	}
	
	public static function init()
	{
		$obj = new static(null);
		
		$classFull = $obj->definitionIndex();
		
		self::$_definitions[$classFull] = array(
			'dataFields' => array(),
			'fileFields' => array(),
			'contentFields' => array(),
			'htmlFields' => array(),
			'booleanFields' => array(),
			'dateFields' => array(),
			'owners' => array(),
			'subObjects' => array(),
			'relatedObjects' => array(),
			'identityField' => 'id',
			'adminColumns' => array(),
			'displayColumn' => 'id'
		);
		
		$parts = explode("\\",get_class($obj));
		array_pop($parts);
		$namespace = implode("\\", $parts);
		self::$_definitions[$classFull]['namespace'] = $namespace;
		
		$obj->livesIn(Inflector::pluralize(Inflector::underscore($classFull)));
		
		$obj->isOrderedBy("id ASC");
		$obj->createDefinition();
		
		//echo "<pre>";print_r(self::$_definitions);die();
	}

	private function getAdapter()
	{
		return self::$_adapter;
	}
	
	public function load($id)
	{
		$def = $this->getDefinition();
		$idField = Inflector::underscore($def['identityField']);
		
		$where = "t.{$idField}=?";
		$data = array($id);
		if(is_numeric($id))
		{
			$where .= " OR t.id=?";
			$data[] = $id;
		}
		
		$rows = static::loadMultiple($where, $data, array(), null, null, true);		
		if(count($rows) == 0) return false;
		
		$this->loadFromArray($rows[0]);
		return true;
	}
	
	public function setFrom($obj)
	{
		$data = is_array($obj) ? $obj : $obj->_data;
		foreach($data as $name => $value)
		{
			//Handle special fields
			switch($this->adminFieldType(Inflector::camelize($name)))
			{
				case 'boolean':
					if(is_array($value)) $value = count($value) == 2;
					else $value = $value ? true : false;
					break;
				default:
					break;
			}			
		
			$func = "filter" . Inflector::camelize($name);
			$this->_data[$name] = method_exists($this, $func) ? $this->$func($value) : $value;
		}
		return $this;
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
		
		//Determine if a duplicate identity is about to be created
		$idField = Inflector::underscore($def['identityField']);
		$objects = static::loadMultiple("{$idField} = \"{$this->$idField}\"");
		if(count($objects) > 0)
		{
			$obj = $objects[0];
			if($obj->id != $this->id)
			{
				throw new \ATP\ActiveRecord\Exception("Duplicate {$idField}");
			}
		}
		
		//Add id field if populated
		if($this->id)
		{
			$columns[] = 'id';
			$placeHolders[] = "?";
			$updates[] = "id=?";
			$values[] = $this->id;
		}
		
		//Add data fields
		foreach($def['dataFields'] as $field)
		{
			$setField = true;
		
			$column = Inflector::underscore($field);
			
			$fieldName = lcfirst($field);
			$value = $this->$fieldName;

			$func = "preSave{$field}";
			$value = method_exists($this, $func)
				? $this->$func($value)
				: $value;
			
			//Process file fields
			if(is_array($value))
			{
				if($value['error'] != 4)
				{
					$filesToCopy[$field] = $value;
					$file = new \ATP\ActiveRecord\File();
					$file->setFrom($value);
					$value = $file;
					$this->$fieldName = $file;
				}
				else
				{
					$setField = false;
				}
			}
			
			//Serialize file fields
			if(is_object($value))
			{
				$value = $value->toJson();
			}

			if($setField)
			{
				$columns[] = $column;
				$values[] = $value;			
				$placeHolders[] = "?";
				$updates[] = "{$column}=?";
			}
			
		}
		
		//Add owner fields
		foreach($def['owners'] as $column => $obj)
		{
			$column = \ATP\Inflector::underscore($column) . "_id";
			$columns[] = $column;
			$placeHolders[] = "?";
			$updates[] = "{$column}=?";
			$values[] = $this->_data[$column];
		}
		
		//Construct query
		if(!$this->id)
		{
			$sql  = "INSERT INTO {$def['table']} ";
			$sql .= "(" . implode(", ", $columns) . ") values (";
			$sql .= implode(", ", $placeHolders);
			$sql .= ") ON DUPLICATE KEY UPDATE ";
			$sql .= implode(", ", $updates);
			$values = array_merge($values, $values);
		}
		else
		{
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
		
		//Update sub objects
		// Todo
		
		//Update related objects
		foreach($def['relatedObjects'] as $column => $relation)
		{
			$columnFull = Inflector::variablize($column) . "List";

			//Make sure to load related objects if they haven't been
			if(!isset($this->$columnFull)) $this->$columnFull;

			//Delete all references in mapping table
			$keyName = $relation['columnName'];
			$sql = "DELETE FROM {$relation['mappingTable']} WHERE {$keyName}_id= ?";
			$db->query($sql, array($this->id));			
			
			foreach($this->$columnFull as $obj)
			{
				//Get other definition
				$defOther = $obj->getDefinition();
			
				//Insert reference to this object
				$className = $relation['columnName'];
				$otherClassName = $relation['relatedObjectColumnName'];
				$sql = "INSERT INTO {$relation['mappingTable']} ({$className}_id, {$otherClassName}_id) values (?, ?)";
				$db->query($sql, array($this->id, $obj->id));
			}
		}
	}

	protected function _preSave()
	{
		//Update the object before saving
	}
	
	public function delete()
	{
		$def = $this->getDefinition();
		$db = $this->getAdapter();
		
		//Delete this object
		$sql = "DELETE FROM {$def['table']} WHERE id={$this->id} LIMIT 1";
		$db->query($sql);
		
		//Todo: delete sub objects
		
		//Delete related object mapping entries
		foreach($def['relatedObjects'] as $column => $relation)
		{
			//Make sure to load related objects if they haven't been
			$columnFull = Inflector::camelize($column) . "List";
			$className = Inflector::singularize($def['table']);

			//Delete all references in mapping table
			$sql = "DELETE FROM {$relation['mappingTable']} WHERE {$className}_id= ?";
			$db->query($sql, array($this->id));			
		}
	}
	
	public function loadMultiple($where = null, $data = array(), $joins = array(), $orderBy = null, $limit = null, $arrays = false)
	{
		//Get this model's definition
		$def = $this->getDefinition();
		
		//Create columns list
		$columns = "t.id id";
		foreach($def['dataFields'] as $field)
		{
			$columns .= ", " . Inflector::underscore($field);
		}
		
		//Update joins
		if(!is_array($joins)) $joins = array($joins);
		
		//Add owner id columns to list
		foreach($def['owners'] as $column => $table)
		{
			$columns .= ", t." . \ATP\Inflector::underscore($column) . "_id";
		}
		
		//Build sql
		$sql = "SELECT {$columns} FROM {$def['table']} t";
		if(!empty($joins)) $sql .= " " . implode(" ", $joins);
		if(!empty($where)) $sql .= " WHERE " . (is_array($where) ? implode(" AND ", $where) : $where);
		$sql .= " ORDER BY " . (is_null($orderBy) ? $def['orderBy'] : $orderBy);
		if(!empty($limit)) $sql .= " LIMIT {$limit}";
		
		//Fetch the results
		$results = $this->getAdapter()->query($sql, $data);
		
		//Create the returned objects
		if($arrays)
		{
			$rows = $results;
		}
		else
		{
			$rows = new \ATP\ActiveRecord\ModelList($this->modelType());
			
			foreach($results as $row)
			{
				$obj = new static();
				$obj->loadFromArray($row);
				$rows[] = $obj;
			}
		}
		
		return $rows;
	}
	
	public function loadFromArray($row)
	{
		foreach($row as $column => $value)
		{
			$this->_data[$column] = $this->_postLoadValue($column, $value);
		}
	}
	
	public function toJson($maxRecursionDepth = 0)
	{
		return json_encode($this->toArray($maxRecursionDepth));
	}
	
	public function toArray($maxRecursionDepth = 0, $objectStack = array())
	{
		$def = $this->getDefinition();
		
		//Don't render this object if it's been done already
		$identifier = "{$def['table']}::{$this->identity()}";		
		if(in_array($identifier, $objectStack)) return "\"*RECURSION*\"";		
		$objectStack[] = $identifier;
		
		$fields = array();
		
		//Add standard fields
		$fields['id'] = $this->id;
		
		//Add data fields
		foreach($def['dataFields'] as $field)
		{
			$column = Inflector::variablize($field);
			
			$fieldName = lcfirst($field);
			$fields[$column] = $this->$fieldName;
		}		
		
		//Add custom fields
		$this->_toArrayCustom($fields);
		
		if($maxRecursionDepth > 0)
		{
			//Add owners
			
			//Add sub-objects
			
			//Add related objects
			foreach($def['relatedObjects'] as $column => $relation)
			{
				$columnFull = Inflector::variablize($column) . "List";

				//Make sure to load related objects if they haven't been
				if(!isset($this->$columnFull)) $this->$columnFull;
				
				$objList = array();
				foreach($this->$columnFull as $obj)
				{
					$objList[$obj->identity()] = $obj->toArray($maxRecursionDepth - 1, $objectStack);
				}
				$fields[$columnFull] = $objList;
			}
		}
		
		return $fields;
	}
	
	protected function _toArrayCustom(&$fields)
	{
	}
	
	private function _postLoadValue($column, $value)
	{
		$column = ucfirst(Inflector::camelize($column));
		
		if($this->isAFile($column))
		{
			$file = new \ATP\ActiveRecord\File();
			$file->setFrom($value);
			$value = $file;
		}
		
		$func = "postLoad{$column}";
		return method_exists($this, $func)
			? $this->$func($value)
			: $value;
	}
	
	protected function definitionIndex()
	{
		$parts = explode("\\",get_class($this));
		return end($parts);
	}
	
	public function getDefinition()
	{
		return self::$_definitions[$this->definitionIndex()];
	}
	
	public function modelType()
	{
		return \ATP\Inflector::underscore(\ATP\Inflector::pluralize($this->definitionIndex()));
	}
	
	public function isIdentifiedBy($field)
	{
		self::$_definitions[$this->definitionIndex()]['identityField'] = $field;
		return $this;
	}
	
	public function identity()
	{
		$def = $this->getDefinition();
		$field = Inflector::underscore($def['identityField']);
		return $this->$field;
	}
	
	protected function livesIn($tableName)
	{
		self::$_definitions[$this->definitionIndex()]['table'] = $tableName;
		self::$_definitions[$this->definitionIndex()]['name'] = Inflector::singularize($tableName);
		return $this;
	}
	
	protected function hasData()
	{
		self::$_definitions[$this->definitionIndex()]['dataFields'] = func_get_args();
		return $this;
	}
	
	public function hasFiles()
	{
		self::$_definitions[$this->definitionIndex()]['fileFields'] = func_get_args();
		return $this;
	}
	
	public function isAFile($column)
	{
		return $this->adminFieldType($column) == 'file';
	}
	
	public function filePath($column)
	{
		$def = $this->getDefinition();
	
		$path = "/uploads/{$def['table']}/{$this->id}/";
		$path .= $this->$column->name;
		return $path;
	}
	
	public function hasContent()
	{
		self::$_definitions[$this->definitionIndex()]['contentFields'] = func_get_args();
		return $this;
	}
	
	public function isContent($column)
	{
		return $this->adminFieldType($column) == 'content';
	}

	public function hasHtmlContent()
	{
		self::$_definitions[$this->definitionIndex()]['htmlFields'] = func_get_args();
		return $this;
	}
	
	public function hasBoolean()
	{
		self::$_definitions[$this->definitionIndex()]['booleanFields'] = func_get_args();
		return $this;
	}
	
	public function hasDate()
	{
		self::$_definitions[$this->definitionIndex()]['dateFields'] = func_get_args();
		return $this;
	}
	
	public function isHtmlContent($column)
	{
		return $this->adminFieldType($column) == 'html';
	}

	public function adminFieldType($field)
	{
		$types = array('file', 'content', 'html', 'date', 'boolean');
		foreach($types as $type)
		{
			if(in_array($field, self::$_definitions[$this->definitionIndex()]["{$type}Fields"])) return $type;
		}
	
		return 'text';
	}
	
	public function dataColumns()
	{
		return self::$_definitions[$this->definitionIndex()]['dataFields'];
	}
	
	protected function hasAdminColumns()
	{
		self::$_definitions[$this->definitionIndex()]['adminColumns'] = func_get_args();
		return $this;
	}
	
	public function adminColumns()
	{
		return self::$_definitions[$this->definitionIndex()]['adminColumns'];
	}
	
	public function displayName()
	{
		$column = self::$_definitions[$this->definitionIndex()]['displayColumn'];
		return $this->$column;
	}
	
	public function __toString()
	{
		return $this->displayName();
	}
	
	public function ownerFields()
	{
		$def = $this->getDefinition();
		
		$owners = $def['owners'];
		
		return $owners;
	}
	
	public function subObjectFields()
	{
		$def = $this->getDefinition();
		return $def['subObjects'];
	}
	
	public function relatedObjectFields()
	{
		$def = $this->getDefinition();
		return $def['relatedObjects'];
	}
	
	protected function isOrderedBy($order)
	{
		self::$_definitions[$this->definitionIndex()]['orderBy'] = $order;
		return $this;
	}
	
	public function __call($func, $args)
	{
		if(substr($func, 0, 9) == "belongsTo")
		{
			$def = substr($func, 9);
			$parts = explode("As", $def);
		
			$table = $parts[0];
			$column = count($parts) == 1 ? Inflector::singularize($parts[0]) : $parts[1];
		
			return $this->_belongsTo($table, $column);
		}
		
		if(substr($func, 0, 7) == "hasMany")
		{
			$def = substr($func, 7);
			$parts = explode("Via", $def);
			$mappingTable = Inflector::underscore($parts[1]);
			$parts = explode("As", $parts[0]);
			
			$table = $parts[0];
			$column = count($parts) == 1 ? $parts[0] : $parts[1];
			
			if(count($args) > 0)
			{
				$columnName = $args[0];
				$relatedObjectColumnName = $args[1];
			}
			else
			{
				$columnName = Inflector::singularize($this->modelType());
				$relatedObjectColumnName = Inflector::underscore(Inflector::singularize($table));
			}
			
			return $this->_isRelatedTo($table, $column, $mappingTable, $columnName, $relatedObjectColumnName);
		}
		
		if(substr($func, 0, 3) == "has")
		{
			$def = substr($func, 3);
			$parts = explode("As", $def);
			if(count($parts) == 1)
			{
				$parts[1] = Inflector::singularize($parts[0]);
			}
		
			$table = $parts[0];
			$column = count($parts) == 1 ? Inflector::singularize($parts[0]) : $parts[1];
		
			return $this->_has($table, $column);
		}
		
		if(substr($func, 0, 13) == "isDisplayedAs")
		{
			$column = substr($func, 13);
			return $this->_displayedAs($column);
		}
	}
	
	public function __set($name, $value)
	{
		//Parse the name of the column
		$name = ucfirst($name);
		$nameUnderscore = Inflector::underscore($name);
		
		//Process value before insertion
		$func = "filter{$name}";
		if(method_exists($this, $func)) $value = $this->$func($value);
		
		switch($this->_fieldType($name))
		{
			case 'owner':
				$this->_data[$nameUnderscore] = $value;
				$this->_data["{$nameUnderscore}_id"] = $value->id;
				break;
			case 'subObjects':
			case 'relatedObjects':
			case 'data':
				$this->_data[$nameUnderscore] = $value;
				break;
		}
	}
	
	public function __get($name)
	{
		//Parse the name of the column
		$name = ucfirst($name);
		$nameUnderscore = Inflector::underscore($name);
	
		//Lazy load all related objects and related object lists
		if(!isset($this->_data[$nameUnderscore]))
		{
			switch($this->_fieldType($name))
			{
				case 'owner':			$this->_loadOwner($name);				break;
				case 'subObjects':		$this->_loadSubObjects($name);			break;
				case 'relatedObjects':	$this->_loadRelatedObjects($name);		break;
				default:				$this->_data[$nameUnderscore] = null;	break;
			}
		}
		
		$value = $this->_data[$nameUnderscore];
		
		$func = "get{$name}";		
		return method_exists($this, $func) ? $this->$func($value) : $value;
	}
	
	private function _loadOwner($field)
	{
		$def = $this->getDefinition();
		
		$name = ucfirst($field);
		$nameUnderscore = Inflector::underscore($field);

		$column = "{$nameUnderscore}_id";
		
		$classNameBase = Inflector::singularize($def['owners'][$name]);
		$classNameBasePlural = Inflector::pluralize($classNameBase);
		$className = \Zend_Registry::get('config')->models->$classNameBasePlural->class;
		
		$id = isset($this->_data[$column]) ? $this->_data[$column] : null;
		$this->_data[$nameUnderscore] = new $className($id);
	}
	
	private function _loadSubObjects($field)
	{
		$def = $this->getDefinition();
		
		$name = ucfirst($field);
		$nameUnderscore = Inflector::underscore($field);

		$className = $def['namespace'] . "\\" . substr($name, 0, -4);
		$obj = new $className();
		$this->_data[$nameUnderscore] = new \ATP\ActiveRecord\ModelList($obj->modelType());
		foreach($className::loadMultiple("{$def['name']}_id={$this->id}") as $obj)
		{
			$this->_data[$nameUnderscore][$obj->identity()] = $obj;
		}
	}
	
	private function _loadRelatedObjects($field)
	{
		$def = $this->getDefinition();

		$name = ucfirst($field);
		$nameUnderscore = Inflector::underscore($field);

		$rawName = substr($name, 0, -4);
		
		$mappingTable = $def['relatedObjects'][$rawName]['mappingTable'];
		
		$otherEntityClass = $def['namespace'] . "\\" . $def['relatedObjects'][$rawName]['class'];
		$otherEntityField = "{$def['relatedObjects'][$rawName]['relatedObjectColumnName']}_id";
		
		$idField = "{$def['relatedObjects'][$rawName]['columnName']}_id";
		
		$joins = "left join {$mappingTable} m ON m.{$otherEntityField}=t.id";
		$where = "m.{$idField}=?";
		$data = array($this->id);
		$obj = new $otherEntityClass();
		$this->_data[$nameUnderscore] = new \ATP\ActiveRecord\ModelList($obj->modelType());
		foreach($otherEntityClass::loadMultiple($where, $data, $joins) as $obj)
		{
			$this->_data[$nameUnderscore][$obj->identity()] = $obj;
		}
	}
	
	private function _fieldType($name)
	{
		//Get this model's definition
		$def = $this->getDefinition();

		//Parse the name of the column
		$name = ucfirst($name);
		$nameUnderscore = Inflector::underscore($name);
	
		//Determin data type
		if(in_array($name, array_keys($def['owners']))) return 'owner';
		if(in_array(substr($name, 0, -4), array_keys($def['subObjects'])) && substr($name, -4) == "List") return "subObjects";
		if(in_array(substr($name, 0, -4), array_keys($def['relatedObjects'])) && substr($name, -4) == "List") return "relatedObjects";
		return "data";
	}

	private function _belongsTo($table, $column)
	{
		self::$_definitions[$this->definitionIndex()]['owners'][$column] = $table;
		return $this;
	}
	
	private function _has($table, $column)
	{
		self::$_definitions[$this->definitionIndex()]['subObjects'][$column] = $table;
		return $this;
	}
	
	private function _displayedAs($column)
	{
		self::$_definitions[$this->definitionIndex()]['displayColumn'] = $column;
		return $this;
	}
	
	private function _isRelatedTo($table, $column, $mappingTable, $columnName, $relatedObjectColumnName)
	{
		self::$_definitions[$this->definitionIndex()]['relatedObjects'][Inflector::singularize($column)] = array(
			'class' => Inflector::singularize($table),
			'mappingTable' => $mappingTable,
			'columnName' => $columnName,
			'relatedObjectColumnName' => $relatedObjectColumnName
		);
		return $this;
	}
}