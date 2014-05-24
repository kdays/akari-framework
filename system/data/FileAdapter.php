<?php
!defined("AKARI_PATH") && exit;

Class FileAdapter extends BaseCacheAdapter{
	private $indexPath;
	private $fileIndex = Array();

	public function __construct(){
		$options = $this->getOptions("file", Array(
			"path" => Context::$appBasePath."/data/cache/",
			"index" => "data.json",
			"prefix" => ''
		));

		$this->indexPath = $options['path'].$options['index'];
		$this->options = $options;
		if(!file_exists($this->indexPath)){
			file_put_contents($this->indexPath, json_encode($this->fileIndex));
		}else{
			$this->fileIndex = json_decode(file_get_contents($this->indexPath), TRUE);
		}

		// 处理超时的缓存
		$isFoundRemove = false;
		foreach($this->fileIndex as $key => $value){
			if($value['expire'] > 0 && $value['expire'] < time()){
				$isFoundRemove = true;
				$this->remove($key, false);
			}
		}

		if($isFoundRemove)	$this->update();
	}

	public function getPath($key = ''){
		$hash = md5(uniqid());
		return $key."_".substr($hash, 6, 11);
	}

	/**
	 * 删除文件缓存中的某个键
	 * 
	 * @param string $key
	 * @param bool $oper 是否立即生效，不立即生效的话后面需要用update()处理
	 **/
	public function remove($key, $oper = true){
		$key = $this->options['prefix'].$key;
		if(!isset($this->fileIndex[$key]))	return false;

		$data = $this->fileIndex[$key];
		unlink($this->options['path'].$data['f']);
		unset($this->fileIndex[$key]);

		if($oper)	$this->update();
		return true;
	}

	public function set($key, $value, $expire = -1, $oper = true){
		$key = $this->options['prefix'].$key;
		if(isset($this->fileIndex[$key]))	$this->remove($key);

		$fileName = $this->getPath($key);
		$this->fileIndex[$key] = Array(
			'f' => $fileName,
			'expire' => ($expire>0) ? (time()+$expire) : $expire
		);

		file_put_contents($this->options['path'].$fileName, serialize($value));
		if($oper)	$this->update();
	}

	public function get($key, $defaultValue = false){
		$key = $this->options['prefix'].$key;
		
		if(isset($this->fileIndex[$key])){
			$now = $this->fileIndex[$key];
			return unserialize(file_get_contents($this->options['path'].$now['f']));
		}
		
		return $defaultValue;
	}

	public function update(){
		file_put_contents($this->indexPath, json_encode($this->fileIndex));
	}
}