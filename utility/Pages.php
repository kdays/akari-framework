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

	public function init($nowPage = 1, $maxPage, $url, $extra = array()){
		$this->nowpage = $nowPage;
		$this->maxpage = $maxPage;
		$this->url = $url;
		$this->params = $extra;
		$this->page = '';
	}

	public function reload(){
		$url = $this->url;
		$extra = $this->params;
		$nowPage = $this->nowpage;
		$maxPage = $this->maxpage;

		foreach($extra as $key => $value){
			$url = str_replace("($key)", $value, $url);
		}

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

	public function addURL($key, $value){
		if(!array_key_exists($key, $this->params)){
			$this->url .=  in_string($this->url, "?") ? "&" : "?";
			$this->url .= "$key=($key)";
		}

		$this->params[$key] = $value;
	}

	public function addParam($key, $value = NULL){
		if($value !== NULL && $value !== FALSE){
			$this->addURL($key, $value);
		}
	}

	public function setSize($count, $pageSize){
		$this->maxPage = ceil($count / $pageSize);
	}

	public function getHTML(){
		$this->page = '';
		$this->reload();

		return $this->page;
	}
}