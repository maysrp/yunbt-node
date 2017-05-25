<?php
	class multi{
		public $aria2;
		public $db;
		public $key;
		public $redis;
		public function __construct($data){
			$this->redis->connect('127.0.0.1', 6379);//redis验证
			$this->db = new medoo([
    				'database_type' => 'mysql',
 				    'database_name' => 'db',
 				    'server' => 'localhost',
 			        'username' => 'root',
  				    'password' => '',
			        'charset' => 'utf8',
   			 		'port' => 3306,
    				'prefix' => 'tp_',
			]);
			$this->key="";//key
			$this->aria2=new Aria2('http://127.0.0.1:6800/jsonrpc');//Aria2.php必须先require
			//对接来话JSON: KEY,TYPE,DO
			//去  JSON:KEY,TYPE,DO
			$json=json_decode($data);
			if($json['key']==$this->key){
				switch ($json['type']) {
					case 'add_aria2':
						$this->add_aria2($json['do']);
						break;
					case 'remove_aria2':
						$this->remove_aria2($json['do']);
						break;
					case 'stop_aria2':
						$this->pause_aria2($json['do']);
						break;
					case 'start_aria2':
						$this->unpause_aria2($json['do']);
						break;
					case 'status_aria2':
						$this->status_aria2($json['do']);
						break;
					case 'stop_all_aria2':
						$this->stop_all_aria2($json['do']);
						break;
					case 'start_all_aria2':
						$this->start_all_aria2($json['do']);
						break;
					case 'remove':
						$this->remove($json['do']);
						break;
					case 'dir':
						$this->dir($json['do']);
						break;
					case 'achieve':
						$this->achieve($json['do']);
						break;
					default:
						$this->info();
						break;
				}

			}else{
				$re['status']=false;
				$re['con']="错误KEY";
				$this->json($re);
			}
		}
		//传如下载地址+下载链接
		public function stop_all_aria2(){
			$re=$aria2->pauseAll();
			$this->json($re);
		}
		public function start_all_aria2(){
			$re=$aria2->unpauseAll();
			$this->json($re);
		}
		public function status_aria2(){
			$active=$aria2->tellActive();
			$waiting=$aria2->tellWaiting(0,100);
			$re['active']=$active['result'];
			$re['waiting']=$waiting['result'];
			$this->json($re);
		}
		public function add_aria2($data){
			$dir=$data['dir'];
			$url=$data['uri'];//magnet
			$re=$aria2->addUri(array($url),array('dir'=>$dir,));
			$this->json($re);
		}
		public function remove_aria2($data){
			$gid=$data['gid'];
			$re=$aria2->remove($gid);
			$this->json($re);
		}
		public function pause_aria2($data){//停止
			$pause=$data['pause'];
			$re=$aria2->pause($pause);
			$this->json($re);
		}
		public function unpause_aria2($data){//启动
			$unpause=$data['unpause'];
			$re=$aria2->unpause($unpause);
			$this->json($re);
		}
		public function info(){
			$disk['free']=@disk_free_space(".");//disk 
			$disk['total']=@disk_total_space(".");

			$aria2=$aria2->getGlobalStat();
			$info['server']=$_SERVER;//可以修改
			$info['disk']=$disk;
			$info['aria2']=$aria2


			$re['status']=true;
			$re['con']=$info;
			$this->json($re);
		}
		public function size($file){
			$fz=filesize($file);
			if ($fz>(1024*1024*1024)) {
				return sprintf("%.2f",$fz/(1024*1024*1024))."GB";
			}elseif ($fz>(1024*1024)) {
				return sprintf("%.2f",$fz/(1024*1024))."MB";
			}elseif($fz>1024){
				return sprintf("%.2f",$fz/1024)."KB";
			}else{
				return $fz."B";
			}
		}
		public function mtime($file){
			return date("Y-m-d H:i:s",filemtime($file));
		}
		public function atime($file){
			return date("Y-m-d H:i:s",fileatime($file));
		}
		public function ctime($file){
			return date("Y-m-d H:i:s",filectime($file));
			
		}
		public function json($info){
			header('Content-type:text/json');
			echo json_encode($info);
		}
		public function dir($info){//相对目录
			$dir=dirname(__FILE__);
			$dir=$dir.$info;
			$info=scandir($dir);
			foreach ($info as $key => $value) {
				$file['atime']=$this->atime($dir."/".$value);
				$file['ctime']=$this->ctime($dir."/".$value);
				$file['mtime']=$this->mtime($dir."/".$value);
				if(!is_dir($dir."/".$value)){
					$file['ex']=$this->ex($value);
					$file['size']=$this->size($dir."/".$value);
					$file['dir']=0;
				}else{
					$file['dir']=1;
				}
				$inx[]=$file;
			}
			$re['con']=$inx;
			$this->json($re);
		}
		public function ex($info){
			$info=strtolower($info);
			$info_a=explode(".", $info);
			return array_pop($info_a);
		}
		public function rd(){
			$rnum_1=mt_rand(0,99999);
			$rnum_2=mt_rand(0,99999);
			$rx=$rnum_2*$rnum_1;
			$key=sha1($rx);//不验证
			return $key;
		}
		public function achieve($data){//给redis传入地址用于判断,传入相对地址
			$dir=dirname(__FILE__);
			$key=$this->rd();
			$this->redis->setex($key,7200,$dir.$data['dir']);
		}
	}