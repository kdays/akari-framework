<?php
/**
 * 参考Medoo的数据库解析
 **/

Class DBParser{
	public $pdo;
	protected static $d;
	public static function getInstance($pdo){
		if(!isset(self::$d)){
			self::$d = new self();
			self::$d->pdo = $pdo;
		}

		return self::$d;
	}

	protected function __contruct(){
	}

	public function parseValue($value){
		return $this->pdo->quote($value);
	}

	public function parseColumn($value){
		return '"' . str_replace('.', '"."', $value) . '"';
	}

	public function parseField($columns){
		if($columns == '*')	return '*';

		if(is_string($columns))	$columns = array($columns);

		$stack = array();

		foreach ($columns as $key => $value){
			preg_match('/([a-zA-Z0-9_\-\.]*)\s*\(([a-zA-Z0-9_\-]*)\)/i', $value, $match);

			if (isset($match[1], $match[2])){
				array_push($stack, $this->parseColumn( $match[1] ) . ' AS ' . $this->parseColumn( $match[2] ));
			}else{
				array_push($stack, $this->parseColumn( $value ));
			}
		}

		return implode(',', $stack);
	}

	public function parseArray($array){
		$temp = array();
		foreach($array as $value){
			$temp[] = is_numeric($value) ? $value : $this->pdo->parseValue($value);
		}

		return implode(",", $temp);
	}

	public function innerIn($data, $flag, $outer){
		$haystack = array();
		foreach($data as $value){
			$haystack[] = "(".$this->parseData($value, $flag).")";
		}

		return implode($outer." ", $haystack);
	}

	public function parseData($data, $flag){
		$wheres = array();
		foreach($data as $key => $value){
			$type = gettype($value);
			if($type == 'array' && preg_match("/^(AND|OR)\s*#?/i", $key, $relation_match)){
				$wheres[] = 0 !== count(array_diff_key($value, array_keys(array_keys($value)))) ?
					'(' . $this->parseData($value, ' ' . $relation_match[1]) . ')' :
					'(' . $this->innerIn($value, ' ' . $relation_match[1], $flag) . ')';
			}else{
				// 匹配运算符用
				preg_match('/([\w\.]+)(\[(\>|\>\=|\<|\<\=|\!|\<\>|\>\<)\])?/i', $key, $match);
				$column = $this->parseColumn($match[1]);

				if(isset($match[3])){
					if($match[3] == ""){
						$wheres[] = $column . ' ' . $match[3] . '= ' . $this->parseValue($value);
					}elseif($match[3] == "!"){
						switch($type){
							case 'NULL':
								$wheres[] = "$column IS NOT NULL";break;
							case 'array':
								$wheres[] = "$column NOT IN (".$this->parseArray($value).")";
								break;

							case 'integer':
							case 'double':
								$wheres[] = "$column != $value";break;

							case 'boolean':
								$wheres[] = "$column != ".($value ? '1' : '0');
								break;

							case 'string':
								$wheres[] = "$column != ".$this->parseValue($value);
								break;
						}
					}elseif($match[3] == '<>' || $match[3] == '><'){
						if($type == "array"){
							if($match[3])	$column .= ' NOT';

							if(is_numeric($value[0]) && is_numeric($value[1])){
								$wheres[] = '(' . $column . ' BETWEEN ' . $value[0] . ' AND ' . $value[1] . ')';
							}else{
								$wheres[] = '(' . $column . ' BETWEEN ' . $this->parseValue($value[0]) . ' AND ' . $this->parseValue($value[1]) . ')';
							}
						}
					}else{
						//都不是那就是日期
						if(is_numeric($value)){
							$wheres[] = "$column $match[3] $value";
						}else{
							$datetime = strtotime($value);
							if($datetime){
								$wheres[] = "$column $match[3] ".date('Y-m-d H:i:s', $datetime);
							}
						}
					}
				}else{
					// 没有特殊运算时 那就是＝了
					switch($type){
						case 'NULL':
							$wheres[] = "$column IS NULL";
							break;

						case 'array':
							$wheres[] = "$column IN (".$this->parseArray($value).")";
							break;

						case 'integer':
						case 'double':
							$wheres[] = "$column = $value";
							break;

						case 'boolean':
							$wheres[] = "$column = ".($value ? '1' : '0');
							break;

						case 'string':
							$wheres[] = "$column = ".$this->parseValue($value);
							break;
					}
				}
			}
		}

		return implode($flag." ", $wheres);
	}

	public function parseWhere($where){
		$str = '';
		if(is_array($where)){
			$whereKeys = array_keys($where);
			$whereAND = preg_grep("/^AND\s*#?$/i", $whereKeys);
			$whereOR = preg_grep("/^OR\s*#?$/i", $whereKeys);

			$singleOper = array_diff_key($where, array_flip(
				explode(' ', 'AND OR GROUP ORDER HAVING LIMIT LIKE MATCH')
			));

			//如果找到操作符 那么后面的可以无视了
			if($singleOper != array()){
				$str = $this->parseData($singleOper, '');
			}

			if (!empty($whereAND)){
				$value = array_values($whereAND);
				$str = $this->parseData($where[ $value[0] ], ' AND');
			}

			if (!empty($whereOR)){
				$value = array_values($whereOR);
				$str = $this->parseData($where[ $value[0] ], ' OR');
			}

			if(isset($where['LIKE'])){
				$likeTmpStr = ''; //@todo
				$likes = $where['LIKE'];

				if(is_array($likes)){
					$isOR = isset($likes['OR']); 
					$wrap = array();

					if($isOR || isset($likes['AND'])){
						$connector = $isOR ? 'OR' : 'AND';
						$likes = $isOR ? $likes['OR'] : $likes['AND'];
					}else{
						$connector = 'AND';
					}

					foreach($likes as $column => $keyword){
						if(is_array($keyword)){
							foreach($keyword as $key){
								$wrap[] = $this->parseColumn($column)." LIKE ".$this->parseValue('%'.$value.'%');
							}
						}else{
							$wrap[] = $this->parseColumn($column)." LIKE ".$this->parseValue('%'.$keyword.'%');
						}
					}

					$likeTmpStr .= '(' . implode($wrap, ' ' . $connector . ' ') . ')';
					$str =  $str=="" ? $likeTmpStr : " AND $likeTmpStr";

					unset($likeTmpStr);
				}
			}

			if(isset($where['MATCH'])){
				$matchTmpStr = '';
				$matchQuery = $where['MATCH'];

				if (is_array($match_query) && isset($matchQuery['columns'], $matchQuery['keyword'])){
					$str =  ($str=="" ? "" : ' AND'). ' MATCH ("' . str_replace('.', '"."', implode($matchQuery['columns'], '", "')) . '") AGAINST (' . $this->quote($matchQuery['keyword']) . ')';
				}
			}
		}else{
			if($where != NULL)	$str = ' '.$where;
		}

		return $str != "" ? " WHERE $str " : "";
	}

	public function parseJoin($join){
		$joinKey = is_array($join) ? array_keys($join) : null;

		if(isset($joinKey[0]) && $joinKey[0][0] == '['){
			$tableJoin = Array();
			$joinArray = Array(
				'>' => 'LEFT', '<' => 'RIGHT', 
				'<>' => 'FULL', '><' => 'INNER'
			);

			foreach($join as $subTable => $relation){
				preg_match('/(\[(\<|\>|\>\<|\<\>)\])?([a-zA-Z0-9_\-]*)/', $subTable, $match);
			
				if($match[2] != '' && $match[3] != ''){
					if(is_string($relation)){
						$relation = "USING (\"$relation\")";
					}elseif(is_array($relation)){
						if(isset($relation[0])){
							// ['table1', 'table2']
							$relation = 'USING ('.implode('", "', $relation). '")';
						}else{
							// ['table1' => 'table2']
							$relation = 'ON ' . $table . '."' . key($relation) . '" = "' . $match[3] . '"."' . current($relation) . '"';
						}
					}

					$tableJoin[] = $joinArray[ $match[2] ] . ' JOIN "' . $match[3] . '" ' . $relation;
				}
			}

			return implode(' ', $tableJoin);
		}

		return '';
	}

	public function parseLimit($limit){
		if(is_numeric($limit)){
			return " LIMIT $limit";
		}elseif(is_array($limit) && is_numeric($limit[0]) && is_numeric($limit[1])){
			return " LIMIT $limit[0],$limit[1]";
		}

		return "";
	}

	public function parseOrder($order){
		if(is_array($order)){
			return ' ORDER BY FIELD(' . $this->parseColumn($order[0]) . ', ' . $this->parseArray($order[1]) . ')';
		}else{
			preg_match('/(^[a-zA-Z0-9_\-\.]*)(\s*(DESC|ASC))?/', $order, $match);
			return ' ORDER BY "' . str_replace('.', '"."', $match[1]) . '" ' . (isset($match[3]) ? $match[3] : '');
		}

		return '';
	}

	public function parseHaving($having){
		return empty($having) ? '' : ' HAVING ' . $this->parseData($having, '');
	}

	public function parseGroup($group){
		return empty($group) ? '' : ' GROUP BY '.$this->parseColumn($group);
	}

	public function parseSet($data){
		$values = array();

		foreach($data as $key => $value){
			switch(gettype($value)){
				case 'NULL':
					$value[$key] = 'NULL';
					break;

				case 'array':
					preg_match("/\(JSON\)\s*([\w]+)/i", $key, $columnMatch);
					if(isset($columnMatch[0])){
						$values[ $columnMatch[1] ] = $this->parseValue(json_encode($value));
					}else{
						$values[ $key ] = $this->parseValue(serialize($value));
					}
					break;

				case 'boolean':
					$values[$key] = ($value ? '1' : '0');
					break;

				case 'integer':
				case 'double':
				case 'string':
					$values[$key] = $this->parseValue($value);
					break;
			}
		}

		return $values;
	}

	public function parseDistinct(){
		return '';
	}
}