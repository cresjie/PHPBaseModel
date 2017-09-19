<?php

define('HOST', 'localhost');
define('USER', 'cres');
define('PASSWORD','cres12345123');
define('DBNAME', 'cres_test');
define('DBPORT', 3306);
define('DBCHARSET', 'utf8');



class Collection implements ArrayAccess, IteratorAggregate
{

	protected $items = [];

	public function __construct($items = [])
	{
		$this->items = $items;
	}

	public function first()
	{
		foreach($this->items as $key => $value) {
			return $value;
		}
	}

	public function last()
	{
		foreach(array_reverse($this->items) as $key => $value) {
			return $value;
		}
	}

	public function count()
	{
		return count($this->items);
	}

	public function all()
	{
		return $this->items;
	}

	public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->items[$offset]);
    }

    public function offsetGet($offset) {

        return isset($this->items[$offset]) ? $this->items[$offset] : null;
    }

    public function __toString()
    {
    	return $this->toJson();
    }

    public function toJson()
    {
    	return json_encode($this->items);
    }
}





class Model implements JsonSerializable
{
	protected $table = '';

	protected $attributes = [];

	protected $primaryKey = 'id';

	public function __construct($attributes = [])
	{
		$this->fill($attributes);
	}
	
	public function __set($key, $value)
	{
		$this->attributes[$key] = $value;
		return $this;
	}

	public function __get($key)
	{
		return isset( $this->attributes[$key] ) ?  $this->attributes[$key] : null;
	}

	public function fill($attributes = [])
	{
		$this->attributes = $attributes;
	}

	public function getAttributes()
	{
		return $this->attributes;
	}

	public function save()
	{
		$pdo = new PDO(sprintf("mysql:host=%s;dbname=%s;port=%d;charset=%s;", HOST, DBNAME, DBPORT, DBCHARSET), USER, PASSWORD);
		$_keys = array_keys($this->attributes);
		$keys = implode(', ', $_keys);
		$values = implode(',', array_values($this->attributes));

		$_placeholder = array();

		foreach($_keys as $_key) {
			$_placeholder[] = '?';
		}

		$placeholder = implode(', ', $_placeholder);
        
        

		$stmt = $pdo->prepare("INSERT INTO {$this->table} (firstname, lastname) VALUES ($placeholder)");
		
		
		
		$i = 1;
		foreach($this->attributes as $key => $value) {
			
			$stmt->bindParam($i, $this->attributes[$key]);
			$i++;
		}
		
		
		$success =  $stmt->execute();
		
		if($success) {
			$this->attributes[$this->primaryKey] = $pdo->lastInsertId() ;
		}

		return $success;
	}

	public function jsonSerialize()
	{
		return $this->attributes;
	}

	public function __toString()
	{
		return $this->toJson();
	}

	public function toJson()
	{
		return json_encode($this->attributes);
	}

	
}

class User extends Model
{
	protected $table = 'users';
}


function query($q) {
	$pdo = new PDO(sprintf("mysql:host=%s;dbname=%s;port=%d;charset=%s;", HOST, DBNAME, DBPORT, DBCHARSET), USER, PASSWORD);
	$stmt = $pdo->query($q);
	$stmt->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'Model');
	
	
	return new Collection($stmt->fetchAll());
}

?>
