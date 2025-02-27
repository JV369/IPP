<?php
	/**
	* Projekt IPP - test.php
	* Jmeno: Jan Vavra
	* Login: xvavra20
	*/

	/**
	* Trída na vygenerovani vystupniho html
	* @param $stream - soubor pro zapis
	* @param $countAll - pocet vsech testu
	* @param $countPass - pocet uspesnych testu
	*/
	class HTMLgenerator
	{
		protected $stream;
		protected $countAll;
		protected $countPass;
		/**
		* Zapise nemenne tagy do souboru
		*/
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
		/**
		* Vygeneruje vystup pro jeden test 
		* @param $filename - nazev souboru stestem
		* @param $parseRet - navratova hodnota parseru
		* @param $intrRet - navratova hodnota interpretu
		* @param $diffRetOut - navratova hodnota diff *.out 
		* @param $diffRetRc - navratova hodnota diff *.rc 
		*/
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

		/**
		* Zapise ukoncujici tagy a zavre soubor
		*/
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
	* Trida pro testovani 
	* @param $directory - adresar, ve kterem se budou hledat testy
	* @param $htmlGen - instance tridy HTMLgenerator
	* @param $recursive - False pokud nehledame rekurzivne v adresarich, True v opacnem pripade
	* @param $parse - cesta k souboru parse.php
	* @param $interpret - cesta k souboru interpret.py
	*/	
	class Tests
	{
		public $directory = './';
		public $htmlGen;
		protected $recursive = False;
		protected $parse = './parse.php';
		protected $interpret = './interpret.py';

		/**
		* Zpracuje vstupni argumenty
		*/
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
				}
				elseif (preg_match("/\-\-int\-script\=.*/", $arg)) {
					$argInt = explode("=", $arg,2);
					$this->interpret = $argInt[1];
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
			if(!is_readable($this->parse) || !is_readable($this->interpret) || !is_readable($this->directory)){
				fprintf(STDERR,"Problem se soubory/adresarem\n");
				exit(11);
			}
		}

		/**
		* Vygeneruje soubury .in .out .rc pokud chybí
		* @param $dir - adresar, kde se nachazi .src
		* @param $filename - nazev souboru
		*/
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

		/**
		* Provede test
		* @param dir - adresar, ve kterem provadime testy
		* @param filename - nazev souboru s testem
		*/
		public function runTest($dir,$filename){
			$this->genFiles($dir,$filename);
			if(!is_readable($dir.$filename.".out") || !is_readable($dir.$filename.".rc") || !is_readable($dir.$filename.".in")){
				fprintf(STDERR,"Problem se soubory/adresarem\n");
				exit(11);
			}
			$time = time();
			$returnDiffOut = -1;
			$returnDiffRc = -1;
			$returnParse = -1;
			$returnIntr = 0;
			exec("php5.6 \"".$this->parse."\" < \"".$dir.$filename.".src\" > \"".$dir.$time.".xml\"",$dead,$returnParse);
			if($returnParse == 0){
				exec("timeout 10s python3.6 \"".$this->interpret."\" --source=\"".$dir.$time.".xml\" < \"".$dir.$filename.".in\" > \"".$dir.$time.".out\"",$dead,$returnIntr);
				exec("printf ".$returnIntr." > \"".$dir.$time.".rc\"");
				exec("diff \"".$dir.$time.".out\" \"".$dir.$filename.".out\"",$dead,$returnDiffOut);
				exec("diff \"".$dir.$time.".rc\" \"".$dir.$filename.".rc\"",$dead,$returnDiffRc);
				if ($returnDiffRc != 0){
					exec("echo >> \"".$dir.$time.".rc\"");
					exec("diff \"".$dir.$time.".rc\" \"".$dir.$filename.".rc\"",$dead,$returnDiffRc);
				}
				exec("rm \"".$dir.$time.".rc\"");
				exec("rm \"".$dir.$time.".out\"");
			}
			else{
				exec("printf ".$returnParse." > \"".$dir.$time.".rc\"");
				exec("diff \"".$dir.$time.".rc\" \"".$dir.$filename.".rc\"",$dead,$returnDiffRc);
				if ($returnDiffRc != 0){
					exec("echo >> \"".$dir.$time.".rc\"");
					exec("diff \"".$dir.$time.".rc\" \"".$dir.$filename.".rc\"",$dead,$returnDiffRc);
				}
				exec("rm \"".$dir.$time.".rc\"");
			}
			exec("rm \"".$dir.$time.".xml\"");
			$this->htmlGen->generateQuery($dir.$filename,$returnParse,$returnIntr,$returnDiffOut,$returnDiffRc);
		}

		/**
		* Najde soubor s testem 
		* @param dir - korenovy adresar
		*/
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
				 	if(preg_match("/.*\.src$/", "$file")){
				 		$actDir = $dir.$dirAtribs->getSubPath()."/";
				 		$fileName = preg_replace("/.*\//", "", "$file");
				 		$fileArr = array_map('strrev',explode(".", strrev($fileName),2));
				 		if(!is_readable($actDir) || !is_writable($actDir)){
				 			fprintf(STDERR,"Problem se soubory/adresarem\n");
							exit(11);
						}
						$this->runTest($actDir,$fileArr[1]);
					}
				 }
			}
			
		}
	}


	$test = new Tests($argv);
	$test->findTest($test->directory);
	$test->htmlGen->close();
?>
