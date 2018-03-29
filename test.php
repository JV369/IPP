<?php
	/**
	* 
	*/
	class HTMLgenerator
	{
		protected $stream;
		protected $countAll;
		protected $countPass;
		function __construct()
		{
			$this->stream = fopen("test_results.html", "w");
			fwrite($this->stream, "<html>\n");
			fwrite($this->stream, "<head>\n");
			fwrite($this->stream, "<meta charset=\"UTF-8\">\n");
			fwrite($this->stream, "</head>\n");
			fwrite($this->stream, "<style>\n");
			fwrite($this->stream, "th, td {\n");
			fwrite($this->stream, "text-align: center;\n");
			fwrite($this->stream, "border: 1px solid #595959;\n");
			fwrite($this->stream, "padding: 8px;\n");
			fwrite($this->stream, "}\n");
			fwrite($this->stream, "tr:nth-child(even) {\n");
			fwrite($this->stream, "background-color: #cecece;\n");
			fwrite($this->stream, "}\n");
			fwrite($this->stream, "</style>\n");
			fwrite($this->stream, "<body>\n");
			fwrite($this->stream, "<h1 align=\"center\">IPPcode18 Test</h1>\n");
			fwrite($this->stream, "<h3 align=\"center\">Jan Vávra (xvavra20)</h3>\n");
			fwrite($this->stream, "<table align=\"center\" style=\"border-collapse: collapse;\">\n");
			fwrite($this->stream, "<tr>\n");
			fwrite($this->stream, "<th>Název souboru</th>\n");
			fwrite($this->stream, "<th>Parse.php návratová hodnota</th>\n");
			fwrite($this->stream, "<th>Interpret.py návratová hodnota</th>\n");
			fwrite($this->stream, "<th>Výsledek diff .out</th>\n");
			fwrite($this->stream, "<th>Výsledek diff .rc</th>\n");
			fwrite($this->stream, "</tr>\n");
			$this->countPass = 0;
			$this->countAll = 0;
		}

		public function generateQuery($filename,$parseRet,$intrRet,$diffRetOut,$diffRetRc){
			fwrite($this->stream, "<tr>\n");
			fwrite($this->stream, "<td>".$filename."</td>\n");
			fwrite($this->stream, "<td>".$parseRet."</td>\n");
			fwrite($this->stream, "<td>".$intrRet."</td>\n");
			$this->countAll += 1;
			$passed = True;
			if ($parseRet != 0){
				fwrite($this->stream, "<td style=\"background-color: gray;\">Chybí XML</td>\n");
			}
			elseif($diffRetOut != 0){
				fwrite($this->stream, "<td style=\"background-color: red;\">Fail</td>\n");
				$passed = False;
			}
			else{
				fwrite($this->stream, "<td style=\"background-color: green;\">OK</td>\n");
			}

			if($diffRetRc != 0){
				fwrite($this->stream, "<td style=\"background-color: red;\">Fail</td>\n");
				$passed = False;
			}
			else{
				fwrite($this->stream, "<td style=\"background-color: green;\">OK</td>\n");
			}
			if($passed){
				$this->countPass += 1;
			}
			fwrite($this->stream, "</tr>\n");

		}

		public function close(){
			fwrite($this->stream, "<tr>\n");
			fwrite($this->stream, "<th colspan=\"5\" style=\"text-align: right;\">Shrnutí: ".$this->countPass."/".$this->countAll."</th>\n");
			fwrite($this->stream, "</tr>\n");
			fwrite($this->stream, "</table>\n");
			fwrite($this->stream, "</body>\n");
			fwrite($this->stream, "</html>\n");
			fclose($this->stream);
		}
	}

/**
* 
*/
	class Tests
	{
		public $directory = './';
		public $htmlGen;
		protected $recursive = False;
		protected $parse = './parse.php';
		protected $interpret = './interpret.py';

		function __construct($arguments)
		{
			$this->htmlGen = new HTMLgenerator();
			foreach ($arguments as $arg) {
				if (preg_match("/\-\-help/", $arg)){
					printf("Help by mel byt napsan zde");
					exit(0);
				}
				elseif (preg_match("/\-\-directory\=.*/", $arg)) {
					$agrDir = explode("=", $arg,2);
					$this->directory = $agrDir[1];
					if (!preg_match("/\/$/", $agrDir[1])){
						$this->directory .= "/";
					}
				}
				elseif (preg_match("/\-\-parse\-script\=.*/", $arg)) {
					$argParse = explode("=", $arg,2);
					$this->parse = $argParse[1];
					if (!preg_match("/\/$/", $argParse[1])){
						$this->parse .= "/";
					}
				}
				elseif (preg_match("/\-\-int\-script\=.*/", $arg)) {
					$argInt = explode("=", $arg,2);
					$this->interpret = $argInt[1];
					if (!preg_match("/\/$/", $argInt[1])){
						$this->interpret .= "/";
					}
				}
				elseif (preg_match("/\-\-recursive/", $arg)) {
					$this->recursive = True;
				}
				else{
					if($arg == "test.php")
						continue;
					fprintf(STDERR,"Nespravny argument, koncim\n");
					exit(10);
				}
			}
			if(!file_exists($this->parse) || !file_exists($this->interpret) || !file_exists($this->directory)){
				fprintf(STDERR,"Problem se soubory/adresarem\n");
				exit(11);
			}
		}

		public function genFiles($dir,$filename){
			$dirAtribs = scandir($dir);
			$inFile = False;
			$outFile = False;
			$rcFile = False;
			foreach ($dirAtribs as $file){
				if(preg_match("/".$filename.".in/", $file)){
					$inFile = True;
				}
				elseif (preg_match("/".$filename.".out/", $file)) {
					$outFile = True;
				}
				elseif (preg_match("/".$filename.".rc/", $file)) {
					$rcFile = True;
				}
			}
			if(!$inFile){
				$in = fopen( $dir.$filename.".in", "w");
				fclose($in);
			}
			if(!$outFile){
				$out = fopen( $dir.$filename.".out", "w");
				fclose($out);
			}
			if(!$rcFile){
				$rc = fopen( $dir.$filename.".rc", "w");
				fwrite($rc, "0");
				fclose($rc);
			}
		}

		public function runTest($dir,$filename){
			$this->genFiles($dir,$filename);
			$time = time();
			$returnDiffOut = -1;
			$returnDiffRc = -1;
			$returnParse = -1;
			$returnIntr = 0;
			exec("php5.6 ".$this->parse." < ".$dir.$filename.".src > ".$dir.$time.".xml",$dead,$returnParse);
			if($returnParse == 0){
				exec("timeout 10s python3.6 ".$this->interpret." --source=".$dir.$time.".xml < ".$dir.$filename.".in > ".$dir.$time.".out",$dead,$returnIntr);
				exec("printf ".$returnIntr." > ".$dir.$time.".rc");
				exec("diff ".$dir.$time.".out ".$dir.$filename.".out",$dead,$returnDiffOut);
				exec("diff ".$dir.$time.".rc ".$dir.$filename.".rc",$dead,$returnDiffRc);
				if ($returnDiffRc != 0){
					exec("echo >> ".$dir.$time.".rc");
					exec("diff ".$dir.$time.".rc ".$dir.$filename.".rc",$dead,$returnDiffRc);
				}
				exec("rm ".$dir.$time.".rc");
				exec("rm ".$dir.$time.".out");
			}
			else{
				exec("printf ".$returnParse." > ".$dir.$time.".rc");
				exec("diff ".$dir.$time.".rc ".$dir.$filename.".rc",$dead,$returnDiffRc);
				if ($returnDiffRc != 0){
					exec("echo >> ".$dir.$time.".rc");
					exec("diff ".$dir.$time.".rc ".$dir.$filename.".rc",$dead,$returnDiffRc);
				}
				exec("rm ".$dir.$time.".rc");
			}
			exec("rm ".$dir.$time.".xml");
			$this->htmlGen->generateQuery($dir.$filename,$returnParse,$returnIntr,$returnDiffOut,$returnDiffRc);
		}

		public function findTest($dir){
			if(!$this->recursive){
				$dirAtribs = scandir($dir);
				foreach ($dirAtribs as $file) {
					if(preg_match("/.*\.src$/", $file)){
						$fileArr = explode(".", $file,2);
						$this->runTest($dir,$fileArr[0]);
					}
				}
			}
			else{
				 $dirAtribs =  new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->directory));
				 foreach ($dirAtribs as $iteration) {
				 	$file = $dirAtribs->getSubPathName();
				 	if(preg_match("/.*\.src$/", $file)){
				 		$actDir = $dir.$dirAtribs->getSubPath()."/";
				 		$fileName = preg_replace("/.*\//", "", $file);
				 		$fileArr = explode(".", $fileName,2);
						$this->runTest($actDir,$fileArr[0]);
					}
				 }
			}
			
		}
	}


	$test = new Tests($argv);
	$test->findTest($test->directory);
	$test->htmlGen->close();
?>