<?php
Class Pages{
	public $url = '';
	public $nowpage = 1;
	public $maxpage = 1;
	public $params = array();

	public $page = '';

	protected static $m;
	public static function getInstance(){
		if(!isset(self::$m)){
			self::$m = new self();
		}

		return self::$m;
	}
    
	/**
	 * 初始化页码组件
	 * 
	 * @param string $url URL
	 * @param number $nowPage 当前页
	 * @param number $maxPage 最大页
	 * @param array $extra 额外扩展用参数
	 * @todo url使用(key)作为占位符，如页码则是用(page)进行替换
	 * extra中的内容会类似page那样被替换，举例extra中["a" => 123]。
	 * 那么url中的(a)就会被替换成123。 此外extra中的内容在输出前，也可以使用addParam添加
	 */
	public function init($url, $nowPage = 1, $maxPage, $extra = array()){
		$this->nowpage = $nowPage;
		$this->maxpage = $maxPage;
		$this->url = $url;
		$this->params = $extra;
		$this->page = '';
	}
    
	/**
	 * 准备输出时调用，对参数进行替换
	 */
	public function reload(){
		$url = $this->url;
		$extra = $this->params;
		$nowPage = $this->nowpage;
		$maxPage = $this->maxpage;

		foreach($extra as $key => $value){
			$url = str_replace("($key)", $value, $url);
		}

		$this->page = "";
		for($i = 1; $i < $maxPage + 1; $i++){
			if($nowPage+5<=$i || $nowPage > $i+5){
				if($i != 1)	continue;
			}
			
			$turl = str_replace("(page)", $i, $url);
			if($i == $nowPage){
				$this->page .= "<a href='$turl' class='current'>$i</a>";
			}else{
				$this->page .= "<a href='$turl'>$i</a>";
			}
		}

		if($maxPage > 5){
			$turl = str_replace("(page)", $maxpage, $url);
			$this->page .= "<a href='$turl'>&raquo;</a>";
		}
	}
    
	/**
	 * 添加URL参数，这个不会进行检查，请用addParam
	 * 
	 * @param string $key 键名
	 * @param string $value 值
	 */
	public function addURL($key, $value){
		if(!array_key_exists($key, $this->params)){
			$this->url .=  in_string($this->url, "?") ? "&" : "?";
			$this->url .= "$key=($key)";
		}

		$this->params[$key] = $value;
	}
    
	/**
	 * 添加URL参数
	 * 
	 * @param string $key 键名
	 * @param string $value 值
	 */
	public function addParam($key, $value = NULL){
		if($value !== NULL && $value !== FALSE){
			$this->addURL($key, $value);
		}
	}

	/**
	 * 根据数目和页面大小设置最大页
	 * 
	 * @param int $count 统计值
	 * @param int $pageSize 大小
	 */
	public function setSize($count, $pageSize){
		$this->maxPage = ceil($count / $pageSize);
	}
    
	/**
	 * 获得页面HTML
	 * 
	 * @return string
	 */
	public function getHTML(){
		$this->page = '';
		$this->reload();

		return $this->page;
	}
}