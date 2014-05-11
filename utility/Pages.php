<?php
Class Pages{
	public $url = '';
	public $nowpage = 1;
	public $maxpage = 1;
	public $params = array();

	public $page = '';

	public function __construct($nowpage, $maxpage, $url, $extraParams = Array()){
		$this->nowpage = $nowpage;
		$this->maxpage = $maxpage;
		$this->url = $url;

		foreach($extraParams as $key => $value){
			$url = str_replace("($key)", $value, $url);
		}

		for($i = 1; $i < $maxpage + 1; $i++){
			if($nowpage+5<=$i || $nowpage > $i+5){
				if($i != 1)	continue;
			}
			
			$turl = str_replace("(page)", $i, $url);
			if($i == $nowpage){
				$this->page .= "<a href='$turl' class='current'>$i</a>";
			}else{
				$this->page .= "<a href='$turl'>$i</a>";
			}
		}

		if($maxpage > 5){
			$turl = str_replace("(page)", $maxpage, $url);
			$this->page .= "<a href='$turl'>&raquo;</a>";
		}

		return $this;
	}

	public function addURL($key, $value){
		if(!array_key_exists($key, $this->params)){
			$this->url .= "&$key=($key)";
		}
		$this->params[$key] = $value;
	}

	public function getHTML(){
		return $this->page;
	}
}